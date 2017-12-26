<?php

namespace Phalcon\Events;


/**
 * Phalcon\Events\Event
 *
 * This class offers contextual information of a fired event in the EventsManager
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/events/event.c
 */
class Event implements EventInterface
{

    /**
     * Type
     *
     * @var string|null
     * @access protected
     */
    protected $_type;

    /**
     * Source
     *
     * @var object|null
     * @access protected
     */
    protected $_source;

    /**
     * Data
     *
     * @var mixed
     * @access protected
     */
    protected $_data;

    /**
     * Stopped
     *
     * @var boolean
     * @access protected
     */
    protected $_stopped = false;

    /**
     * Cancelable
     *
     * @var boolean
     * @access protected
     */
    protected $_cancelable = true;

    /**
     * \Phalcon\Events\Event constructor
     *
     * @param string $type
     * @param object $source
     * @param mixed $data
     * @param boolean|null $cancelable
     * @throws Exception
     */
    public function __construct($type, $source, $data = null, $cancelable = true)
    {
        if (is_string($type) === false || is_object($source) === false || is_bool($cancelable) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_type = $type;
        $this->_source = $source;
        if ($data!==null){
            $this->_data = $data;
        }
        if ($cancelable!==true){
            $this->_cancelable = $cancelable;
        }
    }

    /**
     * Set the event's type
     *
     * @param string $type
     * @throws Exception
     * @return $this
     */
    public function setType($type)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_type = $type;
        return $this;
    }

    /**
     * (没用了)
     * Returns the event's type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * (没用了)
     * Returns the event's source
     *
     * @return object
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Set the event's data
     *
     * @param mixed $data
     * @return $this
     */
    public function setData($data = null)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * (没用了)
     * Returns the event's data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * (没用了)
     * Sets if the event is cancelable
     *
     * @param boolean $cancelable
     * @throws Exception
     */
    public function setCancelable($cancelable)
    {
        if (is_bool($cancelable) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_cancelable = $cancelable;
    }

    /**
     * (没用了)
     * Check whether the event is cancelable
     *
     * @return boolean
     */
    public function getCancelable()
    {
        return $this->_cancelable;
    }

    /**
     * Stops the event preventing propagation
     *
     * @throws Exception
     * @return $this
     */
    public function stop()
    {
        if (!$this->_cancelable){
            throw new Exception('Trying to cancel a non-cancelable event');
        }
        $this->_stopped =true;
        return $this;
    }

    /**
     * Check whether the event is currently stopped
     *
     * @return boolean
     */
    public function isStopped()
    {
        return $this->_stopped;
    }

    /**
     * Check whether the event is cancelable.
     * @return boolean
     */
    public function isCancelable()
    {
        return $this->_cancelable;
    }

}
