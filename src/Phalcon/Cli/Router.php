<?php

namespace Phalcon\Cli;

use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\Cli\Router\Exception;
use \Phalcon\DiInterface;
use \Phalcon\Cli\Router\Route;
use Phalcon\Text;

/**
 * Phalcon\Cli\Router
 *
 * <p>Phalcon\Cli\Router is the standard framework router. Routing is the
 * process of taking a command-line arguments and
 * decomposing it into parameters to determine which module, task, and
 * action of that task should receive the request</p>
 *
 * <code>
 *  $router = new Phalcon\Cli\Router();
 *  $router->handle(array(
 *      'module' => 'main',
 *      'task' => 'videos',
 *      'action' => 'process'
 *  ));
 *  echo $router->getTaskName();
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cli/router.c
 */
class Router implements InjectionAwareInterface
{

    protected $_dependencyInjector;

    protected $_module;

    protected $_task;

    protected $_action;

    protected $_params = [];

    protected $_defaultModule = null;

    protected $_defaultTask = null;

    protected $_defaultAction = null;

    protected $_defaultParams = [];

    protected $_routes;

    protected $_matchedRoute;

    protected $_matches;

    protected $_wasMatched = false;

    /**
     * \Phalcon\Cli\Router constructor
     * @param boolean $defaultRoutes
     */
    public function __construct($defaultRoutes = true)
    {
        $routes = [];
        if ($defaultRoutes === true) {
            $routes[] = new Route("#^(?::delimiter)?([a-zA-Z0-9\\_\\-]+)[:delimiter]{0,1}$#", ['task' => 1]);
            $routes[] = new Route("#^(?::delimiter)?([a-zA-Z0-9\\_\\-]+):delimiter([a-zA-Z0-9\\.\\_]+)(:delimiter.*)*$#", ["task" => 1, "action" => 2, "params" => 3]);
        }
        $this->_routes = $routes;
    }

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets the name of the default module
     *
     * @param string $moduleName
     * @throws Exception
     */
    public function setDefaultModule($moduleName)
    {
        if (is_string($moduleName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultModule = $moduleName;
    }

    /**
     * Sets the default controller name
     *
     * @param string $taskName
     * @throws Exception
     */
    public function setDefaultTask($taskName)
    {
        if (is_string($taskName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultTask = $taskName;
    }

    /**
     * Sets the default action name
     *
     * @param string $actionName
     * @throws Exception
     */
    public function setDefaultAction($actionName)
    {
        if (is_string($actionName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultAction = $actionName;
    }

    /**
     * Sets an array of default paths. If a route is missing a path the router will use the defined here
     * This method must not be used to set a 404 route
     * @param array $defaults
     * @return Router
     * @throws Exception
     */
    public function setDefaults($defaults)
    {
        if (is_array($defaults) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (isset($defaults['module'])) {
            $this->_defaultModule = $defaults['module'];
        }
        if (isset($defaults['task'])) {
            $this->_defaultTask = $defaults['task'];
        }
        if (isset($defaults['action'])) {
            $this->_defaultAction = $defaults['action'];
        }
        if (isset($defaults['params'])) {
            $this->_defaultParams = $defaults['params'];
        }
        return $this;
    }

    /**
     * Handles routing information received from command-line arguments
     *
     * @param array|null $arguments
     * @return $this
     * @throws Exception
     */
    public function handle($arguments = null)
    {
        $routeFound = false;
        $parts = [];
        $params = [];
        $matches = null;
        $this->_wasMatched = false;
        $this->_matchedRoute = null;
        if (!is_array($arguments)) {
            if (!is_string($arguments) && is_null($arguments)) {
                throw new Exception('Arguments must be an array or string');
            }
            foreach ($this->_routes as $route) {
                $pattern = $route->getCompiledPattern();
                if (Text::memstr($pattern, '^')) {
                    $routeFound = preg_match($pattern, $arguments, $matches);
                } else {
                    $routeFound = $pattern == $arguments;
                }
                if ($routeFound) {
                    $beforeMatch = $route->getBeforeMatch();
                    if ($beforeMatch !== null) {
                        if (!is_callable($beforeMatch)) {
                            throw new Exception('Before-Match callback is not callable in matched route');
                        }
                        $routeFound = call_user_func_array($beforeMatch, [$arguments, $route, $this]);
                    }
                }
                if ($routeFound) {
                    $path = $route->getPaths();
                    $parts = $path;
                    if (is_array($matches)) {
                        $converters = $route->getConverters();
                        foreach ($path as $part => $position) {
                            if (isset($matches[$position])) {
                                $matchPosition = $matches[$position];
                                if (is_array($converters)) {
                                    if (isset($converters[$part])) {
                                        $converter = $converters[$part];
                                        $parts[$part] = call_user_func_array($converter, [$matchPosition]);
                                        continue;
                                    }
                                }
                                $parts[$part] = $matchPosition;
                            } else {
                                if (is_array($converters)) {
                                    if (isset($converters[$part])) {
                                        $converter = $converters[$part];
                                        $parts[$part] = call_user_func_array($converter, [$position]);
                                    }
                                }
                            }
                        }
                        $this->_matches = $matches;
                    }
                    $this->_matchedRoute = $route;
                    break;
                }
            }
            if ($routeFound) {
                $this->_wasMatched = true;
            } else {
                $this->_wasMatched = false;
                $this->_module = $this->_defaultModule;
                $this->_task = $this->_defaultTask;
                $this->_action = $this->_defaultAction;
                $this->_params = $this->_defaultParams;
				return $this;
            }
        } else {
            $parts = $arguments;
        }
        $moduleName = null;
        $taskName = null;
        $actionName = null;
        if (isset($parts['module'])) {
            $moduleName = $parts['module'];
            unset($parts['module']);
        } else {
            $moduleName = $this->_defaultModule;
        }
        if (isset($parts['task'])) {
            $taskName = $parts['task'];
            unset($parts['task']);
        } else {
            $taskName = $this->_defaultTask;
        }
        if (isset($parts['action'])) {
            $actionName = $parts['action'];
            unset($parts['action']);
        } else {
            $actionName = $this->_defaultAction;
        }
        if (isset($parts['params'])) {
            $params = $parts['params'];
            if (!is_array($params)) {
                $strParams = substr((string)$params, 1);
                if ($strParams) {
                    $params = explode(Route::getDelimiter(), $strParams);
                } else {
                    $params = [];
                }
            }
            unset($parts['params']);
        }
        if (count($params)) {
            $params = array_merge($params, $parts);
        } else {
            $params = $parts;
        }
        $this->_module = $moduleName;
        $this->_task = $taskName;
        $this->_action = $actionName;
        $this->_params = $params;
        return $this;
    }

    /**
     * Adds a route to the router
     *
     *<code>
     * $router->add("/about", "About::main");
     *</code>
     *
     * @param string $pattern
     * @param mixed /array $paths
     * @return Route
     * @throws Exception
     */
    public function add($pattern, $paths = null)
    {
        if (is_string($pattern) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $route = new Route($pattern, $paths);
        $this->_routes[] = $route;
        return $route;
    }

    /**
     * Returns proccesed module name
     *
     * @return string|null
     */
    public function getModuleName()
    {
        return $this->_module;
    }

    /**
     * Returns proccesed task name
     *
     * @return string|null
     */
    public function getTaskName()
    {
        return $this->_task;
    }

    /**
     * Returns proccesed action name
     *
     * @return string|null
     */
    public function getActionName()
    {
        return $this->_action;
    }

    /**
     * Returns proccesed extra params
     *
     * @return array|null
     */
    public function getParams()
    {
        return $this->_params;
    }

    public function getMatchedRoute()
    {
        return $this->_matchedRoute;
    }

    /**
     * Returns the sub expressions in the regular expression matched
     *
     * @return array
     */
    public function getMatches()
    {
        return $this->_matches;
    }

    /**
     * Checks if the router matches any of the defined routes
     * @return boolean
     */
    public function wasMatched()
    {
        return $this->_wasMatched;
    }

    /**
     * Returns all the routes defined in the router
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Returns a route object by its id
     *
     * @param int $id
     * @return \Phalcon\Cli\Router\Route |boolean
     */
    public function getRouteById($id)
    {
        foreach ($this->_routes as $route) {
            if ($route->getRouteId() == $id) {
                return $route;
            }
        }
        return false;
    }

    /**
     * Returns a route object by its name
     * @param string $name
     * @return Route | boolean
     */
    public function getRouteByName($name)
    {
        foreach ($this->_routes as $route) {
            if ($route->getName() == $name) {
                return $route;
            }
        }
        return false;
    }

}
