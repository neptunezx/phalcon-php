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
         */
        protected function mustTakeAction($eventName)
        {
            if(!is_string($eventName)) {
                throw new Exception('Invalid parameter type.');
            }
            return isset($this->_options[$eventName]);
        }

    /**
     * Returns the behavior options related to an event
     *
     * @param string $eventName
     * @return array
     */
    protected function getOptions($eventName = null)
	{
        if(!is_string($eventName) && !is_null($eventName)) {
            throw new Exception('Invalid parameter type.');
        }
		$options = $this->_options;
		if ($eventName !== null) {
		    if(isset($options[$eventName])) {
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
     */
	public function notify($type, CollectionInterface $model)
	{
        if(!is_string($type)) {
            throw new Exception('Invalid parameter type.');
        }
        return null;
    }

	/**
     * Acts as fallbacks when a missing method is called on the collection
     *
     * @param string $method
     */
	public function missingMethod(CollectionInterface $model, $method, $arguments = null)
	{
        if(!is_string($method)) {
            throw new Exception('Invalid parameter type.');
        }
        return null;
    }

}
