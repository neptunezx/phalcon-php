<?php

namespace Phalcon;

use function GuzzleHttp\Psr7\uri_for;
use Phalcon\Mvc\Model\Binder;
use \ReflectionMethod;
use \Phalcon\DispatcherInterface;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\Text;
use \Phalcon\Exception;
use \Phalcon\FilterInterface;
use \Phalcon\Events\ManagerInterface;
use Phalcon\Mvc\Model\BinderInterface;

/**
 * Phalcon\Dispatcher
 *
 * This is the base class for Phalcon\Mvc\Dispatcher and Phalcon\Cli\Dispatcher.
 * This class can't be instantiated directly, you can use it to create your own dispatchers
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/dispatcher.c
 */
abstract class Dispatcher implements DispatcherInterface, InjectionAwareInterface, EventsAwareInterface
{

    protected $_dependencyInjector;

    protected $_eventsManager;

    protected $_activeHandler;

    protected $_finished = false;

    protected $_forwarded = false;

    protected $_moduleName = null;

    protected $_namespaceName = null;

    protected $_handlerName = null;

    protected $_actionName = null;

    protected $_params = [];

    protected $_returnedValue = null;

    protected $_lastHandler = null;

    protected $_defaultNamespace = null;

    protected $_defaultHandler = null;

    protected $_defaultAction = "";

    protected $_handlerSuffix = "";

    protected $_actionSuffix = "Action";

    protected $_previousNamespaceName = null;

    protected $_previousHandlerName = null;

    protected $_previousActionName = null;

    protected $_modelBinding = false;

    protected $_modelBinder = null;

    protected $_isControllerInitialize = false;

    const EXCEPTION_NO_DI = 0;

    const EXCEPTION_CYCLIC_ROUTING = 1;

    const EXCEPTION_HANDLER_NOT_FOUND = 2;

    const EXCEPTION_INVALID_HANDLER = 3;

    const EXCEPTION_INVALID_PARAMS = 4;

    const EXCEPTION_ACTION_NOT_FOUND = 5;


    /**
     * \Phalcon\Dispatcher constructor
     */
    public function __construct()
    {
        $this->_params = array();
    }

    /**
     * Sets the dependency injector
     *
     * @param DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            //    throw new Exception('Invalid parameter type.');
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
     * Sets the events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws Exception
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        if (is_object($eventsManager) === false ||
            $eventsManager instanceof ManagerInterface === false) {
            //   throw new Exception('Invalid parameter type.');
        }

        $this->_eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     *
     * @return \Phalcon\Events\ManagerInterface|null
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * Sets the default action suffix
     *
     * @param string $actionSuffix
     * @throws Exception
     */
    public function setActionSuffix($actionSuffix)
    {
        if (is_string($actionSuffix) === false) {
            //   throw new Exception('Invalid parameter type.');
        }

        $this->_actionSuffix = $actionSuffix;
    }

    /**
     * @return string
     */
    public function getActionSuffix()
    {
        return $this->_actionSuffix;
    }

    /**
     * Sets the module where the controller is (only informative)
     *
     * @param string|null $moduleName
     * @throws Exception
     */
    public function setModuleName($moduleName)
    {
        if (is_string($moduleName) === false &&
            is_null($moduleName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_moduleName = $moduleName;
    }

    /**
     * Gets the module where the controller class is
     *
     * @return string|null
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }

    /**
     * Sets the namespace where the controller class is
     *
     * @param string|null $namespaceName
     * @throws Exception
     */
    public function setNamespaceName($namespaceName)
    {
        if (is_string($namespaceName) === false &&
            is_null($namespaceName) === false) {
            //   throw new Exception('Invalid parameter type.');
        }

        $this->_namespaceName = $namespaceName;
    }

    /**
     * Gets a namespace to be prepended to the current handler name
     *
     * @return string|null
     */
    public function getNamespaceName()
    {
        return $this->_namespaceName;
    }

    /**
     * Sets the default namespace
     *
     * @param string $namespace
     * @throws Exception
     */
    public function setDefaultNamespace($namespace)
    {
        if (is_string($namespace) === false) {
            //    throw new Exception('Invalid parameter type.');
        }

        $this->_defaultNamespace = $namespace;
    }

    /**
     * Returns the default namespace
     *
     * @return string|null
     */
    public function getDefaultNamespace()
    {
        return $this->_defaultNamespace;
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
            //    throw new Exception('Invalid parameter type.');
        }

        $this->_defaultAction = $actionName;
    }

    /**
     * @param string $actionName
     * @throws \Phalcon\Exception
     */
    public function setActionName($actionName)
    {
        if (is_string($actionName) === false &&
            is_null($actionName) === false) {
            //   throw new Exception('Invalid parameter type.');
        }

        $this->_actionName = $actionName;
    }

    /**
     * Gets the lastest dispatched action name
     *
     * @return string|null
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * Sets action params to be dispatched
     *
     * @param array $params
     * @throws Exception
     */
    public function setParams($params)
    {
        if (is_array($params) === false) {
            //    throw new Exception('Invalid parameter type.');
        }

        $this->_params = $params;
    }

    /**
     * Gets action params
     *
     * @return array|null
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Set a param by its name or numeric index
     *
     * @param mixed $param
     * @param mixed $value
     */
    public function setParam($param, $value)
    {
        $this->_params[$param] = $value;
    }

    /**
     * Gets a param by its name or numeric index
     *
     * @param $param
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @throws Exception
     * @return mixed
     */
    public function getParam($param, $filters = null, $defaultValue = null)
    {
        $params = $this->_params;
        if (isset($params[$param])) {
            $paramValue = $params[$param];
        } else {
            return $defaultValue;
        }
        if ($filters === null) {
            return $paramValue;
        }
        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) === false) {
            $this->{"_throwDispatchException"}("A dependency injection object is require to access the 'filter' service", self::EXCEPTION_NO_DI);
        }
        if (is_object($dependencyInjector) === false ||
            !$dependencyInjector instanceof FilterInterface) {
            //    throw new Exception('Invalid parameter type.');
        }
        $filter = $dependencyInjector->getShared('filter');
        return $filter->sanitize($paramValue, $filters);
    }

    /**
     * @param $param
     * @return bool
     */
    public function hasParam($param)
    {
        return isset($this->_params[$param]);
    }

    /**
     * Returns the current method to be/executed in the dispatcher
     *
     * @return string
     */
    public function getActiveMethod()
    {
        return $this->_actionName . $this->_actionSuffix;
    }

    /**
     * Checks if the dispatch loop is finished or has more pendent controllers/tasks to disptach
     *
     * @return boolean|null
     */
    public function isFinished()
    {
        return $this->_finished;
    }

    /**
     * Sets the latest returned value by an action manually
     *
     * @param mixed $value
     */
    public function setReturnedValue($value)
    {
        $this->_returnedValue = $value;
    }

    /**
     * Returns value returned by the lastest dispatched action
     *
     * @return mixed
     */
    public function getReturnedValue()
    {
        return $this->_returnedValue;
    }

    /**
     * @param $value
     * @param null $cache
     * @return $this
     */
    public function setModelBinding($value, $cache = null)
    {
        if (is_bool($value) === false) {
            //   throw new Exception('Invalid parameter type.');
        }
        if (is_string($cache)) {
            $dependencyInjector = $this->_dependencyInjector;
            $cache = $dependencyInjector->get($cache);
        }
        $this->_modelBinding = $value;
        if ($value) {
            $this->modelBinder = new Binder($cache);
        }
        return $this;
    }

    /**
     * @param $modelBinder
     * @param null $cache
     * @return $this
     */
    public function setModelBinder($modelBinder, $cache = null)
    {
        if (is_object($modelBinder) === false ||
            !$modelBinder instanceof BinderInterface) {
            //    throw new Exception('Invalid parameter type.');
        }
        if (is_string($cache)) {
            $dependencyInjector = $this->_dependencyInjector;
            $cache = $dependencyInjector->get($cache);
        }
        if ($cache != null) {
            $modelBinder->setCache($cache);
        }
        $this->_modelBinding = true;
        $this->_modelBinder = $modelBinder;
        return $this;
    }

    /**
     * Gets model binder
     * @return  BinderInterface|null
     */
    public function getModelBinder()
    {
        return $this->_modelBinder;
    }

    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @return object|boolean
     */
    public function dispatch()
    {
        if (is_object($this->_dependencyInjector) === false ||
            !$this->_dependencyInjector instanceof DiInterface) {
            //   throw new Exception('Invalid parameter type.');
        }
        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) === false) {
            $this->{"_throwDispatchException"}("e", self::EXCEPTION_NO_DI);
            return false;
        }
        if (is_object($this->_eventsManager) === false ||
            !$this->_eventsManager instanceof DiInterface) {
            //   throw new Exception('Invalid parameter type.');
        }
        $eventsManager = $this->_eventsManager;
        $hasEventsManager = is_object($eventsManager);
        $this->_finished = true;
        if ($hasEventsManager) {
            try {
                if ($eventsManager->fire('dispatch:beforeDispatchLoop', $this) === false && $this->_finished !== false) {
                    return false;
                }
            } catch (Exception $e) {
                $status = $this->{'_handelException'}($e);
                if ($this->_finished !== false) {
                    if ($status === false) {
                        return false;
                    }
                    throw $e;
                }
            }
        }
        $value = null;
        $handler = null;
        $numberDispatches = 0;
        $actionSuffix = $this->_actionSuffix;
        $this->_finished = false;
        while (!$this->_finished) {
            $numberDispatches++;
            if ($numberDispatches == 256) {
                $this->{"_throwDispatchException"}("Dispatcher has detected a cyclic routing causing stability problems", self::EXCEPTION_CYCLIC_ROUTING);
                break;
            }
            $this->_finished = true;
            $this->_resolveEmptyProperties();
            if ($hasEventsManager) {
                try {
                    if ($eventsManager->fire('dispatch:beforeDispatch', $this) === false || $this->_finished === false) {
                        continue;
                    }
                } catch (Exception $e) {
                    if ($this->{'_handelException'}($e) === false || $this->_finished === false) {
                        continue;
                    }
                    throw $e;
                }
            }
            $handlerClass = $this->getHandlerClass();
            $hasService = (bool)$dependencyInjector->has($handlerClass);
            if (!$hasService) {
                $hasService = (bool)class_exists($handlerClass);
            }
            if (!$hasService) {
                $status = $this->{'-throwDispatchException'}($handlerClass . 'handler class cannot be loaded', self::EXCEPTION_HANDLER_NOT_FOUND);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }
            $handler = $dependencyInjector->getShared($handlerClass);
            $wasFresh = $dependencyInjector->wasFreshInstance();
            if (is_object($handler) === false) {
                $status = $this->{'_throwDispatchException'}('Invalid handler returned from the services contaioner', self::EXCEPTION_INVALID_HANDLER);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }
            $this->_activeHandler = $handler;
            $namespaceName = $this->_namespaceName;
            $handlerName = $this->_handlerName;
            $actionName = $this->_actionName;
            $params = $this->_params;
            if (is_array($params) === false) {
                $status = $this->{'_throwDispatchException'}('Action parameters must be an Array', self::EXCEPTION_INVALID_PARAMS);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }
            $actionMethod = $this->getActiveMethod();
            if (!is_callable($handler, $actionMethod)) {
                if ($hasEventsManager) {
                    if ($eventsManager->fire('dispatch:beforeNotFoundAction', $this) === false) {
                        continue;
                    }
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                $status = $this->{'throwDispatchException'}("Action'" . $actionName . "'was not found on handler'" . $handlerName . "'", self::EXCEPTION_ACTION_NOT_FOUND);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }
            if ($hasEventsManager) {
                try {
                    if ($eventsManager->fire('dispatch:beforeExecuteRoute', $this) === false || $this->_finished === false) {
                        $dependencyInjector->remove($handlerClass);
                        continue;
                    }
                } catch (Exception $e) {
                    if ($this->{'_handlerException'}($e) === false || $this->_finished === false) {
                        $dependencyInjector->remove($handlerClass);
                        continue;
                    }
                    throw  $e;
                }
            }
            if ($wasFresh === true) {
                if (method_exists($handler, 'initialize')) {
                    try {
                        $this->_isControllerInitialize = true;
                        $handler->initialize();
                    } catch (Exception $e) {
                        $this->_isControllerInitialize = false;
                        if ($this->{'_handleException'}($e) === false || $this->_finished === false) {
                            continue;
                        }
                        throw $e;
                    }
                }
                $this->_isControllerInitialize = false;
                if ($eventsManager) {
                    try {
                        if ($eventsManager->fire('dispatch:afterInitialize', $this) === false || $this->_finished === false) {
                            continue;
                        }
                    } catch (Exception $e) {
                        if ($this->{'_handeleException'}($e) === false || $this->_finished === false) {
                            continue;
                        }
                        throw  $e;
                    }
                }
            }
            if ($this->_modelBinding) {
                $modelBinder = $this->_modelBinder;
                $bindCacheKey = '_PHMB_' . $handlerClass . '_' . $actionMethod;
                $params = $modelBinder->bindToHandler($handler, $params, $bindCacheKey, $actionMethod);
            }
            if ($hasEventsManager) {
                if ($eventsManager) {
                    if ($eventsManager->fire('dispatch:afterBinding', $this) === false) {
                        continue;
                    }
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                if (method_exists($handler, 'afterBinding')) {
                    if ($handler->afterBinding($this) === false) {
                        continue;
                    }
                    if ($this->_finished === false) {
                        continue;
                    }
                }
                $this->_lastHandler = $handler;
                try {
                    $this->_returnedValue = $this->callActionMethod($handler, $actionMethod, $params);
                    if ($this->_finished === false) {
                        continue;
                    }
                } catch (Exception $e) {
                    if ($this->{'_handleException'}($e) === false || $this->_finished === false) {
                        continue;
                    }
                    throw $e;
                }
            }
            if (method_exists($handler, 'afterExcuteRoute')) {
                try {
                    if ($handler->afterExcuteRoute($this, $value) === false || $this->_finished === false) {
                        continue;
                    }
                } catch (Exception $e) {
                    if ($this->{'_handleException'}($e) === false || $this->_finished === false) {
                        continue;
                    }
                    throw e;
                }
            }
            if ($hasEventsManager) {
                try {
                    $eventsManager->fire('dispatch:afterDispatch', $this, $value);
                } catch (Exception $e) {
                    if ($this->{'_handleException'}($e) === false || $this->_finished === false) {
                        continue;
                    }
                    throw $e;
                }
            }
        }
        if ($hasEventsManager) {
            try {
                $eventsManager->fire('dispatch:afterDispatchLoop', $this);
            } catch (Exception $e) {
                if ($this->{'_handleException'}($e) === false) {
                    return false;
                }
                throw $e;
            }
        }
        return $handler;
    }

    /**
     * am array $forward
     */
    public function forward($forward)
    {
        if ($this->_isControllerInitialize === true) {
            throw new Exception("Forward parameter must be an Array");
        }
        if (is_array($forward) === false) {
            throw new Exception("Forward parameter must be an Array");
        }
        $this->_previousNamespaceName = $this->_namespaceName;
        $this->_previousHandlerName = $this->_handlerName;
        $this->_previousActionName = $this->_actionName;

        //Check if we need to forward to another namespace
        if (isset($forward['namespace'])) {
            $this->_namespaceName = $forward['namespace'];
        }

        //Check if we need to forward to another controller
        if (isset($forward['controller'])) {
            $this->_handlerName = $forward['controller'];
        } else {
            if (isset($forward['task'])) {
                $this->_handlerName = $forward['task'];
            }
        }

        //Check if we need to forward to another action
        if (isset($forward['action']) === true) {
            $this->_actionName = $forward['action'];
        }

        //Check if we need to forward changing the current parameters

        if (isset($forward['params'])) {
            //@note Changed "fetch_string" to "fetch_array", since the parameters are passed
            //as an array
            $this->_params = $forward['params'];
        }

        $this->_finished = false;
        $this->_forwarded = true;
    }

    /**
     * Check if the current executed action was forwarded by another one
     *
     * @return boolean
     */
    public function wasForwarded()
    {
        return $this->_forwarded;
    }

    /**
     * Possible class name that will be located to dispatch the request
     *
     * @return string
     */
    public function getHandlerClass()
    {
        $this->_resolveEmptyProperties();
        $handlerSuffix = $this->_handlerSuffix;
        $handlerName = $this->_handlerName;
        $namespaceName = $this->_namespaceName;
        if (!Text::memstr($handlerName, '\\')) {
            $camelizedClass = Text::camelize($handlerName);
        } else {
            $camelizedClass = $handlerName;
        }
        if ($namespaceName) {
            if (substr($namespaceName, -1) === '\\') {
                $handlerClass = $namespaceName . $camelizedClass . $handlerSuffix;
            } else {
                $handlerClass = $namespaceName . '\\' . $camelizedClass . $handlerSuffix;
            }
        } else {
            $handlerClass = $camelizedClass . $handlerSuffix;
        }
        return $handlerClass;
    }

    /**
     * @param $handler
     * @param $actionMethod
     * @param array $params
     * @return mixed
     * @throws \Phalcon\Exception
     */
    public function callActionMethod($handler, $actionMethod, array $params = [])
    {
        if (is_string($actionMethod) === false) {
            throw new Exception('Invalid parameter type.');
        }
        return call_user_func_array([$handler, $actionMethod], $params);
    }


    /**
     * @return array
     */
    public function getBoundModels()
    {
        $modelBinder = $this->_modelBinder;
        if ($modelBinder != null) {
            return $modelBinder->getBoundModels();
        }
        return [];
    }


    /**
     *
     */
    protected function _resolveEmptyProperties()
    {
        if (!$this->_namespaceName) {
            $this->_namespaceName = $this->_defaultNamespace;
        }
        if (!$this->_handlerName) {
            $this->_handlerName = $this->_defaultHandler;
        }
        if (!$this->_actionName) {
            $this->_actionName = $this->_defaultAction;
        }
    }

}
