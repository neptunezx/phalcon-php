<?php

namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\BehaviorInterface;

/**
 * Phalcon\Mvc\Model\Behavior
 *
 * This is an optional base class for ORM behaviors
 */
abstract class Behavior implements BehaviorInterface
{

    /**
     * Options
     *
     * @var null|array
     * @access protected
     */
    protected $_options;

    /**
     * \Phalcon\Mvc\Model\Behavior
     *
     * @param array|null $options
     * @throws Exception
     */
    public function __construct($options = null)
    {
        $this->_options = $options;
    }

    /**
     * Checks whether the behavior must take action on certain event
     *
     * @param string $eventName
     * @return boolean
     * @throws Exception
     */
    protected function mustTakeAction($eventName)
    {
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->_options[$eventName]);
    }

    /**
     * Returns the behavior options related to an event
     *
     * @param string|null $eventName
     * @return array
     * @throws Exception
     */
    protected function getOptions($eventName = null)
    {
        if (is_string($eventName) === false &&
            is_null($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($eventName) === false) {
            if (isset($this->_options[$eventName]) === true) {
                return $this->_options[$eventName];
            }

            return null;
        }

        return $this->_options;
    }

    /**
     * This method receives the notifications from the EventsManager
     *
     * @param string $type
     * @param \Phalcon\Mvc\ModelInterface $model
     */
    public function notify($type, ModelInterface $model)
    {
        return null;
    }

    /**
     * Acts as fallbacks when a missing method is called on the model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $method
     * @param array|null $arguments
     */
    public function missingMethod(ModelInterface $model, $method, $arguments = null)
    {
        return null;
    }

}
