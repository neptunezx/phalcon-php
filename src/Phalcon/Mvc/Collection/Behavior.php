<?php

namespace Phalcon\Mvc\Collection;

use Phalcon\Mvc\CollectionInterface;

/**
 * Phalcon\Mvc\Collection\Behavior
 *
 * This is an optional base class for ORM behaviors
 */
abstract class Behavior implements BehaviorInterface
{

    protected $_options;

    /**
     * Phalcon\Mvc\Collection\Behavior
     *
     * @param array $options
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
        if (!is_string($eventName)) {
            throw new Exception('Invalid parameter type.');
        }
        return isset($this->_options[$eventName]);
    }

    /**
     * Returns the behavior options related to an event
     *
     * @param string $eventName
     * @return array
     * @throws Exception
     */
    protected function getOptions($eventName = null)
    {
        if (!is_string($eventName)) {
            throw new Exception('Invalid parameter type.');
        }
        $options = $this->_options;
        if ($eventName !== null) {
            if (isset($options[$eventName])) {
                $eventOptions = $options[$eventName];
                return $eventOptions;
            }
            return null;
        }
        return $options;
    }

    /**
     * This method receives the notifications from the EventsManager
     *
     * @param string $type
     * @throws Exception
     */
    public function notify($type, CollectionInterface $model)
    {
        return null;
    }

    /**
     * Acts as fallbacks when a missing method is called on the collection
     *
     * @param string $method
     * @throws Exception
     */
    public function missingMethod(CollectionInterface $model, $method, $arguments = null)
    {
        return null;
    }

}
