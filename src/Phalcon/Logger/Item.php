<?php

namespace Phalcon\Logger;

/**
 * Phalcon\Logger\Item
 *
 * Represents each item in a logging transaction
 *
 */
class Item
{

    /**
     * Type
     *
     * @var int
     * @access protected
     */
    protected $_type;

    /**
     * Message
     *
     * @var string
     * @access protected
     */
    protected $_message;

    /**
     * Time
     *
     * @var int
     * @access protected
     */
    protected $_time;

    /**
     * Context
     *
     * @var array
     * @access protected
     */
    protected $_context;

    /**
     * \Phalcon\Logger\Item constructor
     *
     * @param $message string
     * @param $type int
     * @param $time int
     * @param $context array|null
     * @throws Exception
     */
    public function __construct($message, $type, $time = null, $context = null)
    {
        if (is_string($message) === false || is_int($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($time) === true) {
            $time = 0;
        } elseif (is_int($time) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_message = $message;
        $this->_type    = $type;
        $this->_time    = $time;

        if (is_array($context)) {
            $this->_context = $context;
        }
    }

    /**
     * Returns the message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * Returns the log type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Returns log timestamp
     *
     * @return integer
     */
    public function getTime()
    {
        return $this->_time;
    }

    /**
     * Returns log context
     *
     */
    public function getContext()
    {
        return $this->_context;
    }

}
