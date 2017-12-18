<?php

/**
 * Dependency Injector
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon;

use Phalcon\Config;
use Phalcon\Di\Service;
use Phalcon\DiInterface;
use Phalcon\Di\Exception;
use Phalcon\Config\Adapter\Php;
use Phalcon\Config\Adapter\Yaml;
use Phalcon\Di\ServiceInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Di\ServiceProviderInterface;

/**
 * Phalcon\Di
 *
 * Phalcon\Di is a component that implements Dependency Injection/Service Location
 * of services and it's itself a container for them.
 *
 * Since Phalcon is highly decoupled, Phalcon\Di is essential to integrate the different
 * components of the framework. The developer can also use this component to inject dependencies
 * and manage global instances of the different classes used in the application.
 *
 * Basically, this component implements the `Inversion of Control` pattern. Applying this,
 * the objects do not receive their dependencies using setters or constructors, but requesting
 * a service dependency injector. This reduces the overall complexity, since there is only one
 * way to get the required dependencies within a component.
 *
 * Additionally, this pattern increases testability in the code, thus making it less prone to errors.
 *
 * <code>
 * use Phalcon\Di;
 * use Phalcon\Http\Request;
 *
 * $di = new Di();
 *
 * // Using a string definition
 * $di->set("request", Request::class, true);
 *
 * // Using an anonymous function
 * $di->setShared(
 *     "request",
 *     function () {
 *         return new Request();
 *     }
 * );
 *
 * $request = $di->getRequest();
 * </code>
 */
class DI implements DiInterface
{

    /**
     * Services
     *
     * @var array
     * @access protected
     */
    protected $_services = [];

    /**
     * Shared Instances
     *
     * @var array
     * @access protected
     */
    protected $_sharedInstances = [];

    /**
     * Fresh Instance
     *
     * @var boolean
     * @access protected
     */
    protected $_freshInstance = false;

    /**
     * Events Manager
     *
     * @var \Phalcon\Events\ManagerInterface
     */
    protected $_eventsManager = null;

    /**
     * Default Instance
     *
     * @var null|\Phalcon\Di
     * @access protected
     */
    protected static $_default = null;

    /**
     * \Phalcon\Di constructor
     */
    public function __construct()
    {
        if (is_null(self::$_default)) {
            self::$_default = $this;
        }
    }

    /**
     * Sets the internal event manager
     * 
     * @param ManagerInterface $eventsManager
     */
    public function setInternalEventsManager(ManagerInterface $eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     * 
     * @return  ManagerInterface
     */
    public function getInternalEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * Registers a service in the services container
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean $shared
     * @return \Phalcon\Di\ServiceInterface|null
     * @throws DiException
     */
    public function set($name, $definition, $shared = false)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (is_bool($shared) === false) {
            throw new DiException('Invalid parameter type.');
        }

        try {
            $this->_services[$name] = new Service($name, $definition, $shared);
        } catch (\Exception $e) {
            $this->_services[$name] = null;
        }
        return $this->_services[$name];
    }

    /**
     * Registers an "always shared" service in the services container
     *
     * @param string $name
     * @param mixed $definition
     * @return \Phalcon\Di\ServiceInterface|null
     */
    public function setShared($name, $definition)
    {
        return $this->set($name, $definition, true);
    }

    /**
     * Removes a service in the services container
     *
     * @param string $name
     * @throws DiException
     */
    public function remove($name)
    {
        if ($name == null) {
            return;
        }

        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        unset($this->_services[$name]);

        //This is missing is the c++ source but logically required
        unset($this->_sharedInstances[$name]);
    }

    /**
     * Attempts to register a service in the services container
     * Only is successful if a service hasn't been registered previously
     * with the same name
     *
     * @param string $name
     * @param mixed $definition
     * @param boolean $shared
     * @return \Phalcon\Di\ServiceInterface|false
     * @throws DiException
     */
    public function attempt($name, $definition, $shared = false)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (isset($this->_services[$name]) === false) {
            $this->_services[$name] = new Service($name, $definition, $shared);
            return $this->_services[$name];
        }

        return false;
    }

    /**
     * Sets a service using a raw \Phalcon\Di\Service definition
     *
     * @param string $name
     * @param \Phalcon\Di\ServiceInterface $rawDefinition
     * @return \Phalcon\Di\ServiceInterface
     * @throws DiException
     */
    public function setRaw($name, ServiceInterface $rawDefinition)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        $this->_services[$name] = $rawDefinition;
        return $rawDefinition;
    }

    /**
     * Returns a service definition without resolving
     *
     * @param string $name
     * @return mixed
     * @throws DiException
     */
    public function getRaw($name)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (isset($this->_services[$name]) === true) {
            return $this->_services[$name]->getDefinition();
        }

        throw new DiException('Service \'' . $name . '\' wasn\'t found in the dependency injection container');
    }

    /**
     * Returns a \Phalcon\Di\Service instance
     *
     * @param string $name
     * @return \Phalcon\Di\ServiceInterface
     * @throws DiException
     */
    public function getService($name)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        if (isset($this->_services[$name]) === true) {
            return $this->_services[$name];
        }

        throw new DiException('Service \'' . $name . '\' wasn\'t found in the dependency injection container');
    }

    /**
     * Create Instance
     *
     * @param string $className
     * @param array|null $params
     * @return object
     * @throws DiException
     */
    private static function createInstance($className, $params = null)
    {
        if (is_string($className) === false) {
            throw new DiException('Invalid class name');
        }

        if (is_array($params) === false || empty($params) === true) {
            return new $className;
        } else {
            $reflection = new \ReflectionClass($className);
            return $reflection->newInstanceArgs($params);
        }
    }

    /**
     * Resolves the service based on its configuration
     *
     * @param string $name
     * @param array|null $parameters
     * @return mixed
     * @throws DiException
     */
    public function get($name, $parameters = null)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        $instance      = null;
        $eventsManager = $this->_eventsManager;
        if (!$eventsManager instanceof ManagerInterface) {
            $eventsManager = null;
        }
        if (is_object($eventsManager)) {
            $instance = $eventsManager->fire(
                "di:beforeServiceResolve", $this, ["name" => $name, "parameters" => $parameters]
            );
        }

        if (!$instance) {
            if (isset($this->_services[$name])) {
                $service  = $this->_services[$name];
                /**
                 * The service is registered in the DI
                 */
                $instance = $service->resolve($parameters, $this);
            } else {
                /**
                 * The DI also acts as builder for any class even if it isn't defined in the DI
                 */
                if (!class_exists($name)) {
                    throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
                }

                if (is_array($parameters) && count($parameters)) {
                    $instance = self::createInstance($name, $parameters);
                } else {
                    $instance = self::createInstance($name);
                }
            }
        }

        if (is_object($instance) === true &&
            $instance instanceof InjectionAwareInterface) {
            $instance->setDI($this);
        }

        if (is_object($eventsManager)) {
            $eventsManager->fire(
                "di:afterServiceResolve", $this, [
                "name"       => $name,
                "parameters" => $parameters,
                "instance"   => $instance
                ]
            );
        }

        return $instance;
    }

    /**
     * Resolves a service, the resolved service is stored in the DI, subsequent requests for this service will return the same instance
     *
     * @param string $name
     * @param array|null $parameters
     * @return mixed
     * @throws DiException
     */
    public function getShared($name, $parameters = null)
    {
        if (is_string($name) === false) {
            throw new DiException('The service alias must be a string');
        }

        if (isset($this->_sharedInstances[$name]) === true) {
            $instance             = $this->_sharedInstances[$name];
            $this->_freshInstance = false;
        } else {
            //Resolve
            $instance = $this->get($name, $parameters);

            //Save
            $this->_sharedInstances[$name] = $instance;
            $this->_freshInstance          = true;
        }

        return $instance;
    }

    /**
     * Check whether the DI contains a service by a name
     *
     * @param string $name
     * @return boolean
     * @throws DiException
     */
    public function has($name)
    {
        if (is_string($name) === false) {
            throw new DiException('The service name must be a string');
        }

        return isset($this->_services[$name]);
    }

    /**
     * Check whether the last service obtained via getShared produced a fresh instance or an existing one
     *
     * @return boolean
     */
    public function wasFreshInstance()
    {
        return $this->_freshInstance;
    }

    /**
     * Return the services registered in the DI
     *
     * @return \Phalcon\Di\Service[]
     */
    public function getServices()
    {
        return $this->_services;
    }

    /**
     * Check if a service is registered using the array syntax.
     * Alias for \Phalcon\Di::has()
     *
     * @param string $name
     * @return boolean
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Allows to register a shared service using the array syntax.
     * Alias for \Phalcon\Di::setShared()
     *
     * <code>
     *  $di['request'] = new \Phalcon\Http\Request();
     * </code>
     *
     * @param string $name
     * @param mixed $definition
     */
    public function offsetSet($name, $definition)
    {
        $this->setShared($name, $definition);
    }

    /**
     * Allows to obtain a shared service using the array syntax.
     * Alias for \Phalcon\Di::getShared()
     *
     * <code>
     *  var_dump($di['request']);
     * </code>
     *
     * @param string $name
     * @return mixed
     */
    public function offsetGet($name)
    {
        return $this->getShared($name, null);
    }

    /**
     * Removes a service from the services container using the array syntax.
     * Alias for \Phalcon\Di::remove()
     *
     * @param string $name
     */
    public function offsetUnset($name)
    {
        return false;
    }

    /**
     * Magic method to get or set services using setters/getters
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws DiException
     */
    public function __call($method, $arguments = null)
    {
        if (strpos($method, 'get') === 0) {
            $serviceName = substr($method, 3);

            $possibleService = lcfirst($serviceName);
            if (isset($this->_services[$possibleService]) === true) {
                if (empty($arguments) === false) {
                    return $this->get($possibleService, $arguments);
                }
                return $this->get($possibleService);
            }
        }

        if (strpos($method, 'set') === 0) {
            if (isset($arguments[0]) === true) {
                $serviceName = substr($method, 3);

                $this->set(lcfirst($serviceName), $arguments[0]);
                return null;
            }
        }

        throw new DiException('Call to undefined method or service \'' . $method . "'");
    }

    /**
     * Registers a service provider.
     *
     * <code>
     * use Phalcon\DiInterface;
     * use Phalcon\Di\ServiceProviderInterface;
     *
     * class SomeServiceProvider implements ServiceProviderInterface
     * {
     *     public function register(DiInterface $di)
     *     {
     *         $di->setShared('service', function () {
     *             // ...
     *         });
     *     }
     * }
     * </code>
     * 
     * @param \Phalcon\Di\ServiceProviderInterface $provider
     * @return void
     */
    public function register(ServiceProviderInterface $provider)
    {
        $provider->register($this);
    }

    /**
     * Set a default dependency injection container to be obtained into static methods
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public static function setDefault(DiInterface $dependencyInjector)
    {
        self::$_default = $dependencyInjector;
    }

    /**
     * Return the lastest DI created
     *
     * @return \Phalcon\DiInterface
     */
    public static function getDefault()
    {
        return self::$_default;
    }

    /**
     * Resets the internal default DI
     */
    public static function reset()
    {
        self::$_default = null;
    }

    /**
     * Loads services from a yaml file.
     *
     * <code>
     * $di->loadFromYaml(
     *     "path/services.yaml",
     *     [
     *         "!approot" => function ($value) {
     *             return dirname(__DIR__) . $value;
     *         }
     *     ]
     * );
     * </code>
     *
     * And the services can be specified in the file as:
     *
     * <code>
     * myComponent:
     *     className: \Acme\Components\MyComponent
     *     shared: true
     *
     * group:
     *     className: \Acme\Group
     *     arguments:
     *         - type: service
     *           name: myComponent
     *
     * user:
     *    className: \Acme\User
     * </code>
     *
     * @link https://docs.phalconphp.com/en/latest/reference/di.html
     * @param string $filePath
     * @param array $callbacks
     * @return void
     */
    public function loadFromYaml($filePath, array $callbacks = null)
    {

        $services = new Yaml($filePath, $callbacks);

        $this->loadFromConfig($services);
    }

    /**
     * Loads services from a php config file.
     *
     * <code>
     * $di->loadFromPhp("path/services.php");
     * </code>
     *
     * And the services can be specified in the file as:
     *
     * <code>
     * return [
     *      'myComponent' => [
     *          'className' => '\Acme\Components\MyComponent',
     *          'shared' => true,
     *      ],
     *      'group' => [
     *          'className' => '\Acme\Group',
     *          'arguments' => [
     *              [
     *                  'type' => 'service',
     *                  'service' => 'myComponent',
     *              ],
     *          ],
     *      ],
     *      'user' => [
     *          'className' => '\Acme\User',
     *      ],
     * ];
     * </code>
     *
     * @link https://docs.phalconphp.com/en/latest/reference/di.html
     * 
     *  * @param string $filePath
     * @return void
     */
    public function loadFromPhp($filePath)
    {
        $this->loadFromConfig(new Php($filePath));
    }

    /**
     * Loads services from a Config object.
     * 
     * @param Config $config
     * @return void
     */
    protected function loadFromConfig(Config $config)
    {
        $services = $config->toArray();

        foreach ($services as $name => $service) {
            $this->set($name, $service, isset($service["shared"]) && $service["shared"]);
        }
    }

}
