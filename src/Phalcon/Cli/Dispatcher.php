<?php

namespace Phalcon\Cli;


use Phalcon\Cli\Dispatcher\Exception;
use Phalcon\Dispatcher as CliDispatcher;

/**
 * Phalcon\Cli\Dispatcher
 *
 * Dispatching is the process of taking the command-line arguments, extracting the module name,
 * task name, action name, and optional parameters contained in it, and then
 * instantiating a task and calling an action on it.
 *
 * <code>

 *  $di = new Phalcon\Di();
 *
 *  $dispatcher = new Phalcon\Cli\Dispatcher();
 *
 *  $dispatcher->setDI($di);
 *
 *  $dispatcher->setTaskName('posts');
 *  $dispatcher->setActionName('index');
 *  $dispatcher->setParams(array());
 *
 *  $handle = $dispatcher->dispatch();
 *
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/cli/dispatcher.c
 */
class Dispatcher extends CliDispatcher implements DispatcherInterface
{

    /**
     * Exception: No Dependency Injector
     *
     * @var int
     */
    const EXCEPTION_NO_DI = 0;

    /**
     * Exception: Cyclic Routing
     *
     * @var int
     */
    const EXCEPTION_CYCLIC_ROUTING = 1;

    /**
     * Exception: Handler Not Found
     *
     * @var int
     */
    const EXCEPTION_HANDLER_NOT_FOUND = 2;

    /**
     * Exception: Invalid Handler
     *
     * @var int
     */
    const EXCEPTION_INVALID_HANDLER = 3;

    /**
     * Exception: Invalid Params
     *
     * @var int
     */
    const EXCEPTION_INVALID_PARAMS = 4;

    /**
     * Exception: Action Not Found
     *
     * @var int
     */
    const EXCEPTION_ACTION_NOT_FOUND = 5;

    /**
     * Handler Suffix
     *
     * @var string
     * @access protected
     */
    protected $_handlerSuffix = 'Task';

    protected $_options = [];

    /**
     * Default Handler
     *
     * @var string
     * @access protected
     */
    protected $_defaultHandler = 'main';

    /**
     * Default Action
     *
     * @var string
     * @access protected
     */
    protected $_defaultAction = 'main';

    /**
     * Sets the default task suffix
     *
     * @param string $taskSuffix
     * @throws Exception
     */
    public function setTaskSuffix($taskSuffix)
    {
        if (is_string($taskSuffix) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_handlerSuffix = $taskSuffix;
    }

    /**
     * Sets the default task name
     *
     * @param string $taskName
     * @throws Exception
     */
    public function setDefaultTask($taskName)
    {
        if (is_string($taskName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultHandler = $taskName;
    }

    /**
     * Sets the task name to be dispatched
     *
     * @param string|null $taskName
     * @throws Exception
     */
    public function setTaskName($taskName)
    {
        if (is_string($taskName) === false && is_null($taskName) === false) {
        //    throw new Exception('Invalid parameter type.');
        }

        //@see \Phalcon\Dispatcher::_handlerName
        $this->_handlerName = $taskName;
    }

    /**
     * Gets last dispatched task name
     *
     * @return string|null
     */
    public function getTaskName()
    {
        return $this->_handlerName;
    }

    /**
     * Throws an internal exception
     *
     * @param string $message
     * @param int $exceptionCode
     * @throws Exception
     * @return boolean|null
     */
    protected function _throwDispatchException($message, $exceptionCode = 0)
    {
        if (is_string($message) === false || is_int($exceptionCode) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $exception = new Exception($message, $exceptionCode);

        if ($this->_handleException($exception) === false) {
            return false;
        }

        //Throw the exception if it wasn't handled
        throw $exception;
    }

    /**
     * Handles a user exception
     *
     * @param \Exception $exception
     * @return boolean|null
     * @throws Exception
     */
    protected function _handleException($exception)
    {
        if (is_object($exception) === false ||
            !$exception instanceof Exception) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_object($this->_eventsManager) === true) {
            if ($this->_eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                return false;
            }
        }
    }


    /**
     * Returns the lastest dispatched controller
     *
     * @return TaskInterface|Object
     */
    public function getLastTask()
    {
        return $this->_lastHandler;
    }

    /**
     * Returns the active task in the dispatcher
     *
     * @return TaskInterface|Object
     */
    public function getActiveTask()
    {
        return $this->_activeHandler;
    }


    /**
     * Set the options to be dispatched
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->_options = $options;
    }

    /**
     * Get dispatched options
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Gets an option by its name or numeric index
     *
     * @param  mixed $option
     * @param  mixed|null $filters
     * @param  mixed|null $defaultValue
     * @return mixed
     */
    public function getOption($option, $filters = null, $defaultValue = null)
    {
        $optionValue = '';
        $options = $this->_options;
        if (!isset($options[$option])) {
            $optionValue = $options[$option];
            return $defaultValue;
        }
        if ($filters === null) {
            return $optionValue;
        }
        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) === false) {
            $this->{'_throeDispatchException'}("A dependency injection object is required 
            to access the 'filter' service", CliDispatcher::EXCEPTION_NO_DI);
        }
        $filter = $dependencyInjector->getShared('filter');
        return $filter->sanitize($optionValue, $filters);
    }

    /**
     * Check if an option exists
     * @param mixed $option
     * @return boolean
     */
    public function hasOption($option)
    {
        return isset($this->_options[$option]);
    }

    /**
     * Calls the action method.
     * @param mixed $handler
     * @param string $actionMethod
     * @param  array $params
     * @return mixed
     */
    public function callActionMethod($handler, $actionMethod, $params)
    {
        $options = $this->_options;

        return call_user_func_array([$handler, $actionMethod], [$params, $options]);
    }

}
