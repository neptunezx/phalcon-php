<?php

namespace Phalcon\Mvc\Micro;

use Phalcon\Mvc\Model\BinderInterface;

/**
 * Phalcon\Mvc\Micro\LazyLoader
 *
 * Lazy-Load of handlers for Mvc\Micro using auto-loading
 */
class LazyLoader
{

    protected $_handler;
    protected $_modelBinder;
    protected $_definition;

    /**
     * Phalcon\Mvc\Micro\LazyLoader constructor
     */
    public function __construct($definition)
    {
        if (!is_string($definition)) {
            throw new Exception('Parameter definition should be a string type');
        }
        $this->_definition = $definition;
    }

    /**
     * Initializes the internal handler, calling functions on it
     *
     * @param  string method
     * @param  array arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (!is_string($method)) {
            throw new Exception('Parameter \'method\' should be a string type');
        }

        $handler = $this->_handler;

        $definition = $this->_definition;

        if (!is_object($handler)) {
            $handler        = new $definition();
            $this->_handler = $handler;
        }

        $modelBinder = $this->_modelBinder;

        if ($modelBinder != null) {
            $bindCacheKey = "_PHMB_" . $definition . "_" . $method;
            $arguments    = $modelBinder->bindToHandler($handler, $arguments, $bindCacheKey, $method);
        }

        /**
         * Call the handler
         */
        return call_user_func_array([$handler, $method], $arguments);
    }

    /**
     * Calling __call method
     *
     * @param  string method
     * @param  array arguments
     * @return mixed
     */
    public function callMethod($method, $arguments, BinderInterface $modelBinder = null)
    {
        $this->_modelBinder = $modelBinder;

        return $this->__call($method, $arguments);
    }

    public function getDifinition()
    {
        return $this->_definition;
    }

}
