<?php

namespace Phalcon\Mvc\Router;

use Phalcon\DiInterface;
use Phalcon\Mvc\Router;
use Phalcon\Text;
use Phalcon\Annotations\Annotation;
use Phalcon\Mvc\Router\Exception;

/**
 * Phalcon\Mvc\Router\Annotations
 *
 * A router that reads routes annotations from classes/resources
 *
 * <code>
 * use Phalcon\Mvc\Router\Annotations;
 *
 * $di->setShared(
 *     "router",
 *     function() {
 *         // Use the annotations router
 *         $router = new Annotations(false);
 *
 *         // This will do the same as above but only if the handled uri starts with /robots
 *         $router->addResource("Robots", "/robots");
 *
 *         return $router;
 *     }
 * );
 * </code>
 */
class Annotations extends Router
{

    protected $_handlers         = [];
    protected $_controllerSuffix = "Controller";
    protected $_actionSuffix     = "Action";
    protected $_routePrefix;

    /**
     * Adds a resource to the annotations handler
     * A resource is a class that contains routing annotations
     *
     * @string string $handler
     * @string string $prefix
     * @return Annotations
     */
    public function addResource($handler, $prefix = null)
    {
        if (!is_string($handler) || (!is_string($prefix) && !is_null($prefix))) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_handlers[] = [$prefix, $handler];

        return $this;
    }

    /**
     * Adds a resource to the annotations handler
     * A resource is a class that contains routing annotations
     * The class is located in a module
     *
     * @param string $module
     * @param string $handler
     * @param string|null $prefix
     * @return Annotations
     */
    public function addModuleResource($module, $handler, $prefix = null)
    {
        if (!is_string($module) || !is_string($handler) || (!is_string($prefix) && !is_null($prefix))) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_handlers[] = [$prefix, $handler, $module];

        return $this;
    }

    /**
     * Produce the routing parameters from the rewrite information
     *
     * @param string|null $uri
     */
    public function handle($uri = null)
    {
        $namespaceName = '';
        if (!is_string($uri) && !is_null($uri)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!$uri) {
            /**
             * If 'uri' isn't passed as parameter it reads $_GET["_url"]
             */
            $realUri = $this->getRewriteUri();
        } else {
            $realUri = $uri;
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (!is_object($dependencyInjector)) {
            throw new Exception("A dependency injection container is required to access the 'annotations' service");
        }

        $annotationsService = $dependencyInjector->getShared("annotations");

        $handlers = $this->_handlers;

        $controllerSuffix = $this->_controllerSuffix;

        foreach ($handlers as $scope) {
            if (!is_array($scope)) {
                continue;
            }

            /**
             * A prefix (if any) must be in position 0
             */
            $prefix = $scope[0];

            if (!empty($prefix) && !Text::startsWith($realUri, $prefix)) {
                continue;
            }

            /**
             * The controller must be in position 1
             */
            $handler = $scope[1];

            if (strpos($handler, "\\")) {

                /**
                 * TODO
                 * Extract the real class name from the namespaced class
                 * The lowercased class name is used as controller
                 * Extract the namespace from the namespaced class
                 */
                $controllerName = \Phalcon\Kernel::getClassNamespace($handler);
                $namespaceName  = \Phalcon\Kernel::getNamespaceOfclass($handler);
            } else {
                $controllerName = $handler;
                if (isset($this->_defaultNamespace)) {
                    $namespaceName = $this->_defaultNamespace;
                }
            }

            $this->_routePrefix = null;

            /**
             * Check if the scope has a module associated
             */
            if (isset($scope[2])) {
                $moduleName = $scope[2];
            }
            $sufixed = $controllerName . $controllerSuffix;

            /**
             * Add namespace to class if one is set
             */
            if ($namespaceName !== null) {
                $sufixed = $namespaceName . "\\" . $sufixed;
            }

            /**
             * Get the annotations from the class
             */
            $handlerAnnotations = $annotationsService->get($sufixed);

            if (!is_object($handlerAnnotations)) {
                continue;
            }

            /**
             * Process class annotations
             */
            $classAnnotations = $handlerAnnotations->getClassAnnotations();
            if (is_object($classAnnotations)) {
                $annotations = $classAnnotations->getAnnotations();
                if (is_array($annotations)) {
                    foreach ($annotations as $annotation) {
                        $this->processControllerAnnotation($controllerName, $annotation);
                    }
                }
            }

            /**
             * Process method annotations
             */
            $methodAnnotations = $handlerAnnotations->getMethodsAnnotations();
            if (is_array($methodAnnotations)) {
                $lowerControllerName = uncamelize($controllerName);

                foreach ($methodAnnotations as $method => $collection) {
                    if (is_object($collection)) {
                        foreach ($collection->getAnnotations() as $annotation) {
                            $this->processActionAnnotation($moduleName, $namespaceName, $lowerControllerName, $method, $annotation);
                        }
                    }
                }
            }
        }
        /**
         * Call the parent handle method()
         */
        parent::handle($realUri);
    }

    /**
     * Checks for annotations in the controller docblock
     *
     * @param string $handler
     * @param Annotation $annotation
     */
    public function processControllerAnnotation($handler, Annotation $annotation)
    {
        /**
         * @RoutePrefix add a prefix for all the routes defined in the model
         */
        if (!is_string($handler)) {
            throw new Exception('Invalid parameter type.');
        }

        if ($annotation->getName() == "RoutePrefix") {
            $this->_routePrefix = $annotation->getArgument(0);
        }
    }

    /**
     * Checks for annotations in the public methods of the controller
     * @param string $module
     * @param string $namespaceName
     * @param string $controller
     * @param string $action
     * @param Annotation $annotation
     * @return boolean
     * @throws Exception
     */
    public function processActionAnnotation($module, $namespaceName, $controller, $action, Annotation $annotation)
    {
        if (!is_string($module) || !is_string($namespaceName) || !is_string($controller) || !is_string($action)) {
            throw new Exception('Invalid parameter type.');
        }
        $isRoute = false;
        $methods = null;
        $name    = $annotation->getName();

        /**
         * Find if the route is for adding routes
         */
        switch ($name) {

            case "Route":
                $isRoute = true;
                break;

            case "Get":
                $isRoute = true;
                $methods = "GET";
                break;

            case "Post":
                $isRoute = true;
                $methods = "POST";
                break;

            case "Put":
                $isRoute = true;
                $methods = "PUT";
                break;

            case "Patch":
                $isRoute = true;
                $methods = "PATCH";
                break;

            case "Delete":
                $isRoute = true;
                $methods = "DELETE";
                break;

            case "Options":
                $isRoute = true;
                $methods = "OPTIONS";
                break;
        }

        if ($isRoute === true) {

            $actionName  = strtolower(str_replace($this->_actionSuffix, "", $action));
            $routePrefix = $this->_routePrefix;

            /**
             * Check for existing paths in the annotation
             */
            $paths = $annotation->getNamedArgument("paths");
            if (!is_array($paths)) {
                $paths = [];
            }

            /**
             * Update the module if any
             */
            if (!empty($module)) {
                $paths["module"] = $module;
            }

            /**
             * Update the namespace if any
             */
            if (!empty($namespaceName)) {
                $paths["namespace"] = $namespaceName;
            }

            $paths["controller"] = $controller;
            $paths["action"]     = $actionName;

            $value = $annotation->getArgument(0);

            /**
             * Create the route using the prefix
             */
            if (!is_null($value)) {
                if ($value != "/") {
                    $uri = $routePrefix . $value;
                } else {
                    if (!is_null($routePrefix)) {
                        $uri = $routePrefix;
                    } else {
                        $uri = $value;
                    }
                }
            } else {
                $uri = $routePrefix . $actionName;
            }

            /**
             * Add the route to the router
             */
            $route = $this->add($uri, $paths);

            /**
             * Add HTTP constraint methods
             */
            if ($methods !== null) {
                $route->via($methods);
            } else {
                $methods = $annotation->getNamedArgument("methods");
                if (is_array($methods) || is_string($methods)) {
                    $route->via($methods);
                }
            }

            /**
             * Add the converters
             */
            $converts = $annotation->getNamedArgument("converts");
            if (is_array($converts)) {
                foreach ($converts as $param => $convert) {
                    $route->convert($param, $convert);
                }
            }

            /**
             * Add the conversors
             */
            $converts = $annotation->getNamedArgument("conversors");
            if (is_array($converts)) {
                foreach ($converts as $conversorParam => $convert) {
                    $route->convert($conversorParam, $convert);
                }
            }

            /**
             * Add the conversors
             */
            $beforeMatch = $annotation->getNamedArgument("beforeMatch");
            if (is_array($beforeMatch) || is_string($beforeMatch)) {
                $route->beforeMatch($beforeMatch);
            }

            $routeName = $annotation->getNamedArgument("name");
            if (is_string($routeName)) {
                $route->setName($routeName);
            }
            return true;
        }
    }

    /**
     * Changes the controller class suffix
     *
     * @param string $controllerSuffix
     * @throws Exception
     */
    public function setControllerSuffix($controllerSuffix)
    {
        if (!is_string($controllerSuffix)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_controllerSuffix = $controllerSuffix;
    }

    /**
     * Changes the action method suffix
     *
     * @param string  $actionSuffix
     * @throws Exception
     */
    public function setActionSuffix($actionSuffix)
    {
        if (!is_string($actionSuffix)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_actionSuffix = $actionSuffix;
    }

    /**
     * Return the registered resources
     *
     * @return array
     */
    public function getResources()
    {
        return $this->_handlers;
    }

}
