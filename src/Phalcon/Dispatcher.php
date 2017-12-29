<?php

namespace Phalcon;

use Exception;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DispatcherInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Exception as PhalconException;
use Phalcon\FilterInterface;
use Phalcon\Mvc\Model\Binder;
use Phalcon\Mvc\Model\BinderInterface;
use Phalcon\Text;

/**
 * Phalcon\Dispatcher
 *
 * This is the base class for Phalcon\Mvc\Dispatcher and Phalcon\Cli\Dispatcher.
 * This class can't be instantiated directly, you can use it to create your own dispatchers.
 */
abstract class Dispatcher implements DispatcherInterface, InjectionAwareInterface, EventsAwareInterface
{

    protected $_dependencyInjector;
    protected $_eventsManager;
    protected $_activeHandler;
    protected $_finished               = false;
    protected $_forwarded              = false;
    protected $_moduleName             = null;
    protected $_namespaceName          = null;
    protected $_handlerName            = null;
    protected $_actionName             = null;
    protected $_params                 = [];
    protected $_returnedValue          = null;
    protected $_lastHandler            = null;
    protected $_defaultNamespace       = null;
    protected $_defaultHandler         = null;
    protected $_defaultAction          = "";
    protected $_handlerSuffix          = "";
    protected $_actionSuffix           = "Action";
    protected $_previousNamespaceName  = null;
    protected $_previousHandlerName    = null;
    protected $_previousActionName     = null;
    protected $_modelBinding           = false;
    protected $_modelBinder            = null;
    protected $_isControllerInitialize = false;

    const EXCEPTION_NO_DI             = 0;
    const EXCEPTION_CYCLIC_ROUTING    = 1;
    const EXCEPTION_HANDLER_NOT_FOUND = 2;
    const EXCEPTION_INVALID_HANDLER   = 3;
    const EXCEPTION_INVALID_PARAMS    = 4;
    const EXCEPTION_ACTION_NOT_FOUND  = 5;

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
     * Sets the events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws Exception
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
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
            throw new Exception('Invalid parameter type.');
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
            throw new Exception('Invalid parameter type.');
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
            throw new Exception('Invalid parameter type.');
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
            throw new Exception('Invalid parameter type.');
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
            throw new Exception('Invalid parameter type.');
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
    public function setParams(array $params)
    {
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
     * @param string $param
     * @param mixed $value
     */
    public function setParam($param, $value)
    {
        if (!is_string($param)) {
            throw new Exception('Invalid parameter type.');
        }
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
        if (!$filter instanceof FilterInterface) {
            throw new Exception('Wrong filter service');
        }
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
     * Enable/Disable model binding during dispatch
     *
     * <code>
     * $di->set('dispatcher', function() {
     *     $dispatcher = new Dispatcher();
     *
     *     $dispatcher->setModelBinding(true, 'cache');
     *     return $dispatcher;
     * });
     * </code>
     *
     * @deprecated 3.1.0 Use setModelBinder method
     * @see Phalcon\Dispatcher::setModelBinder()
     */
    public function setModelBinding($value, $cache = null)
    {
        if (is_bool($value) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_string($cache)) {
            $dependencyInjector = $this->_dependencyInjector;
            $cache              = $dependencyInjector->get($cache);
        }
        $this->_modelBinding = $value;
        if ($value) {
            $this->modelBinder = new Binder($cache);
        }
        return $this;
    }

    /**
     * Enable model binding during dispatch
     *
     * <code>
     * $di->set('dispatcher', function() {
     *     $dispatcher = new Dispatcher();
     *
     *     $dispatcher->setModelBinder(new Binder(), 'cache');
     *     return $dispatcher;
     * });
     * </code>
     */
    public function setModelBinder(BinderInterface $modelBinder, $cache = null)
    {
        if (is_string($cache)) {
            $dependencyInjector = $this->_dependencyInjector;
            $cache              = $dependencyInjector->get($cache);
        }
        if ($cache != null) {
            $modelBinder->setCache($cache);
        }
        $this->_modelBinding = true;
        $this->_modelBinder  = $modelBinder;
        return $this;
    }

    /**
     * Gets model binder
     */
    public function getModelBinder()
    {
        return $this->_modelBinder;
    }

    /**
     * Process the results of the router by calling into the appropriate controller action(s)
     * including any routing data or injected parameters.
     *
     * @return object|false Returns the dispatched handler class (the Controller for Mvc dispatching or a Task
     *                      for CLI dispatching) or <tt>false</tt> if an exception occurred and the operation was
     *                      stopped by returning <tt>false</tt> in the exception handler.
     *
     * @throws \Exception if any uncaught or unhandled exception occurs during the dispatcher process.
     */
    public function dispatch()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if (!$dependencyInjector instanceof DiInterface) {
            $this->{"_throwDispatchException"}("A dependency injection container is required to access related dispatching services", self::EXCEPTION_NO_DI);
            return false;
        }
        
        if (is_object($this->_eventsManager) && !$this->_eventsManager instanceof ManagerInterface) {
            throw new Exception('Wrong EventManager service');
        }

        $eventsManager    = $this->_eventsManager;
        $hasEventsManager = false;
        if ($eventsManager && $eventsManager instanceof ManagerInterface) {
            $hasEventsManager = true;
        }
        $this->_finished = true;

        if ($hasEventsManager) {
            try {
                // Calling beforeDispatchLoop event
                // Note: Allow user to forward in the beforeDispatchLoop.
                if ($eventsManager->fire('dispatch:beforeDispatchLoop', $this) === false && $this->_finished !== false) {
                    return false;
                }
            } catch (Exception $e) {
                // Exception occurred in beforeDispatchLoop.
                // The user can optionally forward now in the `dispatch:beforeException` event or
                // return <tt>false</tt> to handle the exception and prevent it from bubbling. In
                // the event the user does forward but does or does not return false, we assume the forward
                // takes precedence. The returning false intuitively makes more sense when inside the
                // dispatch loop and technically we are not here. Therefore, returning false only impacts
                // whether non-forwarded exceptions are silently handled or bubbled up the stack. Note that
                // this behavior is slightly different than other subsequent events handled inside the
                // dispatch loop.

                $status = $this->{'_handelException'}($e);
                if ($this->_finished !== false) {
                    if ($status === false) {
                        return false;
                    }
                    // Otherwise, bubble Exception
                    throw $e;
                }

                // Otherwise, user forwarded, continue
            }
        }
        $value            = null;
        $handler          = null;
        $numberDispatches = 0;
        $actionSuffix     = $this->_actionSuffix;
        $this->_finished  = false;
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
            $hasService   = (bool) $dependencyInjector->has($handlerClass);
            if (!$hasService) {
                $hasService = (bool) class_exists($handlerClass);
            }
            if (!$hasService) {
                $status = $this->{'_throwDispatchException'}($handlerClass . 'handler class cannot be loaded', self::EXCEPTION_HANDLER_NOT_FOUND);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }
            $handler  = $dependencyInjector->getShared($handlerClass);
            $wasFresh = $dependencyInjector->wasFreshInstance();
            if (is_object($handler) === false) {
                $status = $this->{'_throwDispatchException'}('Invalid handler returned from the services contaioner', self::EXCEPTION_INVALID_HANDLER);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }
            $this->_activeHandler = $handler;
            $namespaceName        = $this->_namespaceName;
            $handlerName          = $this->_handlerName;
            $actionName           = $this->_actionName;
            $params               = $this->_params;
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
                // Try to throw an exception when an action isn't defined on the object
                $status = $this->{'_throwDispatchException'}("Action'" . $actionName . "'was not found on handler'" . $handlerName . "'", self::EXCEPTION_ACTION_NOT_FOUND);
                if ($status === false && $this->_finished === false) {
                    continue;
                }
                break;
            }

            // In order to ensure that the initialize() gets called we'll destroy the current handlerClass
            // from the DI container in the event that an error occurs and we continue out of this block. This
            // is necessary because there is a disjoin between retrieval of the instance and the execution
            // of the initialize() event. From a coding perspective, it would have made more sense to probably
            // put the initialize() prior to the beforeExecuteRoute which would have solved this. However, for
            // posterity, and to remain consistency, we'll ensure the default and documented behavior works correctly.
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
                    throw $e;
                }
            }


            if (method_exists($handler, "beforeExecuteRoute")) {
                try {
                    // Calling "beforeExecuteRoute" as direct method
                    if ($handler->beforeExecuteRoute($this) === false || $this->_finished === false) {
                        $dependencyInjector->remove($handlerClass);
                        continue;
                    }
                } catch (Exception $e) {
                    if ($this->{"_handleException"}($e) === false || $this->_finished === false) {
                        $dependencyInjector->remove($handlerClass);
                        continue;
                    }

                    throw $e;
                }
            }

            // Call the "initialize" method just once per request
            //
			// Note: The `dispatch:afterInitialize` event is called regardless of the presence of an `initialize`
            //       method. The naming is poor; however, the intent is for a more global "constructor is ready
            //       to go" or similarly "__onConstruct()" methodology.
            //
			// Note: In Phalcon 4.0, the initialize() and `dispatch:afterInitialize` event will be handled
            // prior to the `beforeExecuteRoute` event/method blocks. This was a bug in the original design
            // that was not able to change due to widespread implementation. With proper documentation change
            // and blog posts for 4.0, this change will happen.
            //
			// @see https://github.com/phalcon/cphalcon/pull/13112
            if ($wasFresh === true) {
                if (method_exists($handler, 'initialize')) {
                    try {
                        $this->_isControllerInitialize = true;
                        $handler->initialize();
                    } catch (Exception $e) {
                        $this->_isControllerInitialize = false;

                        // If this is a dispatch exception (e.g. From forwarding) ensure we don't handle this twice. In
                        // order to ensure this doesn't happen all other exceptions thrown outside this method
                        // in this class should not call "_throwDispatchException" but instead throw a normal Exception.
                        if ($this->{'_handleException'}($e) === false || $this->_finished === false) {
                            continue;
                        }
                        throw $e;
                    }
                }

                $this->_isControllerInitialize = false;

                // Calling "dispatch:afterInitialize" event
                if ($eventsManager) {
                    try {
                        if ($eventsManager->fire('dispatch:afterInitialize', $this) === false || $this->_finished === false) {
                            continue;
                        }
                    } catch (Exception $e) {
                        if ($this->{'_handeleException'}($e) === false || $this->_finished === false) {
                            continue;
                        }
                        throw $e;
                    }
                }
            }
            if ($this->_modelBinding) {
                $modelBinder  = $this->_modelBinder;
                $bindCacheKey = '_PHMB_' . $handlerClass . '_' . $actionMethod;
                $params       = $modelBinder->bindToHandler($handler, $params, $bindCacheKey, $actionMethod);
            }

            // Calling afterBinding
            if ($hasEventsManager) {
                if ($eventsManager) {
                    if ($eventsManager->fire('dispatch:afterBinding', $this) === false) {
                        continue;
                    }

                    // Check if the user made a forward in the listener
                    if ($this->_finished === false) {
                        continue;
                    }
                }

                // Calling afterBinding as callback and event
                if (method_exists($handler, 'afterBinding')) {
                    if ($handler->afterBinding($this) === false) {
                        continue;
                    }

                    // Check if the user made a forward in the listener
                    if ($this->_finished === false) {
                        continue;
                    }
                }

                // Save the current handler
                $this->_lastHandler = $handler;

                try {
                    // We update the latest value produced by the latest handler
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

            // Calling "dispatch:afterExecuteRoute" event
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

            // Calling "afterExecuteRoute" as direct method
            if ($hasEventsManager) {
                try {
                    $eventsManager->fire('dispatch:afterDispatch', $this, $value);
                } catch (Exception $e) {
                    // Still check for finished here as we want to prioritize forwarding() calls
                    if ($this->{'_handleException'}($e) === false || $this->_finished === false) {
                        continue;
                    }
                    throw $e;
                }
            }
        }

        if ($hasEventsManager) {
            try {
                // Calling "dispatch:afterDispatchLoop" event
                // Note: We don't worry about forwarding in after dispatch loop.
                $eventsManager->fire('dispatch:afterDispatchLoop', $this);
            } catch (Exception $e) {
                // Exception occurred in afterDispatchLoop.
                if ($this->{'_handleException'}($e) === false) {
                    return false;
                }

                // Otherwise, bubble Exception
                throw $e;
            }
        }

        return $handler;
    }

    /**
     * Forwards the execution flow to another controller/action.
     *
     * <code>
     * $this->dispatcher->forward(
     *     [
     *         "controller" => "posts",
     *         "action"     => "index",
     *     ]
     * );
     * </code>
     *
     * @param array forward
     *
     * @throws \Phalcon\Exception
     */
    public function forward(array $forward)
    {
        if ($this->_isControllerInitialize === true) {
            // Note: Important that we do not throw a "_throwDispatchException" call here. This is important
            // because it would allow the application to break out of the defined logic inside the dispatcher
            // which handles all dispatch exceptions.
            throw new PhalconException("Forwarding inside a controller's initialize() method is forbidden");
        }

        $this->_previousNamespaceName = $this->_namespaceName;
        $this->_previousHandlerName   = $this->_handlerName;
        $this->_previousActionName    = $this->_actionName;

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
            $this->_params = $forward['params'];
        }

        $this->_finished  = false;
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
        $handlerName   = $this->_handlerName;
        $namespaceName = $this->_namespaceName;

        // We don't camelize the classes if they are in namespaces
        if (!Text::memstr($handlerName, '\\')) {
            $camelizedClass = Text::camelize($handlerName);
        } else {
            $camelizedClass = $handlerName;
        }

        // Create the complete controller class name prepending the namespace
        if ($namespaceName) {
            if (Text::endsWith($namespaceName, "\\")) {
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
     * @param $action Method
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
     * Returns bound models from binder instance
     *
     * <code>
     * class UserController extends Controller
     * {
     *     public function showAction(User $user)
     *     {
     *         $boundModels = $this->dispatcher->getBoundModels(); // return array with $user
     *     }
     * }
     * </code>
     * 
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
     * Set empty properties to their defaults (where defaults are available)
     */
    protected function _resolveEmptyProperties()
    {
        // If the current namespace is null we used the set in this->_defaultNamespace
        if (!$this->_namespaceName) {
            $this->_namespaceName = $this->_defaultNamespace;
        }

        // If the handler is null we use the set in this->_defaultHandler
        if (!$this->_handlerName) {
            $this->_handlerName = $this->_defaultHandler;
        }

        // If the action is null we use the set in this->_defaultAction
        if (!$this->_actionName) {
            $this->_actionName = $this->_defaultAction;
        }
    }

}
