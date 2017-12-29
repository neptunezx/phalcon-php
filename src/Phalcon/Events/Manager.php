<?php

namespace Phalcon\Events;

use Phalcon\Events\Event;
use SplPriorityQueue;
use Phalcon\Text;
use Phalcon\Events\Exception;

/**
 * Phalcon\Events\Manager
 *
 * Phalcon Events Manager, offers an easy way to intercept and manipulate, if needed,
 * the normal flow of operation. With the EventsManager the developer can create hooks or
 * plugins that will offer monitoring of data, manipulation, conditional execution and much more.
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/events/manager.c
 */
class Manager implements ManagerInterface
{

    /**
     * Events
     *
     * @var array|null
     * @access protected
     */
    protected $_events;

    /**
     * Collect
     *
     * @var boolean
     * @access protected
     */
    protected $_collect = false;

    /**
     * Enable Priorities
     *
     * @var boolean
     * @access protected
     */
    protected $_enablePriorities = false;

    /**
     * Responses
     *
     * @var array|null
     * @access protected
     */
    protected $_responses;

    /**
     * Attach a listener to the events manager
     *
     * @param string $eventType
     * @param object|callable $handler
     * @param int|null $priority
     * @throws Exception
     */
    public function attach($eventType, $handler, $priority = 100)
    {
        if (is_string($eventType) === false || is_int($priority) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (!is_object($handler)) {
            throw new Exception('Event handler must be an Object');
        }

        $priorityQueue = [];
        if (!isset($this->_events[$eventType])) {
            if ($this->_enablePriorities) {
                // Create a SplPriorityQueue to store the events with priorities
                $priorityQueue = new SplPriorityQueue();

                // Extract only the Data // Set extraction flags
                $priorityQueue->setExtractFlags(SplPriorityQueue::EXTR_DATA);

                // Append the events to the queue
                $this->_events[$eventType] = $priorityQueue;
            } else {
                $priorityQueue = [];
            }
        } else {
            $priorityQueue = $this->_events[$eventType];
        }
        if (is_object($priorityQueue)) {
            $priorityQueue->insert($handler, $priority);
        } else {
            $priorityQueue[]           = $handler;
            $this->_events[$eventType] = $priorityQueue;
        }
    }

    /**
     * Detach the listener from the events manager
     *
     * @param string $eventType
     * @param object $handler
     * @throws Exception
     */
    public function detach($eventType, $handler)
    {
        if (is_string($eventType) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (!is_object($handler)) {
            throw new Exception('Event handler must be an Object');
        }

        if (isset($this->_events[$eventType])) {
            $priorityQueue = $this->_events[$eventType];
            if (is_object($priorityQueue)) {
                $newPriorityQueue = new SplPriorityQueue();
                $newPriorityQueue->setExtractFlags(SplPriorityQueue::EXTR_DATA);

                $priorityQueue->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
                $priorityQueue->top();

                while ($priorityQueue->valid()) {
                    $data = $priorityQueue->current();
                    $priorityQueue->next();
                    if ($data['data'] !== $handler) {
                        $newPriorityQueue->insert($data['data'], $data['priority']);
                    }
                }

                $this->_events[$eventType] = $newPriorityQueue;
            } else {
                $key = array_search($handler, $priorityQueue, true);
                if ($key !== false) {
                    unset($priorityQueue[$key]);
                }
                $this->_events[$eventType] = $priorityQueue;
            }
        }
    }

    /**
     * Set if priorities are enabled in the EventsManager
     *
     * @param boolean $enablePriorities
     * @throws Exception
     */
    public function enablePriorities($enablePriorities)
    {
        $this->_enablePriorities = (bool) $enablePriorities;
    }

    /**
     * Returns if priorities are enabled
     *
     * @return boolean
     */
    public function arePrioritiesEnabled()
    {
        return $this->_enablePriorities;
    }

    /**
     * Tells the event manager if it needs to collect all the responses returned by every
     * registered listener in a single fire
     *
     * @param boolean $collect
     * @throws Exception
     */
    public function collectResponses($collect)
    {
        $this->_collect = (bool) $collect;
    }

    /**
     * Check if the events manager is collecting all all the responses returned by every
     * registered listener in a single fire
     *
     * @return boolean
     */
    public function isCollecting()
    {
        return $this->_collect;
    }

    /**
     * Returns all the responses returned by every handler executed by the last 'fire' executed
     *
     * @return array|null
     */
    public function getResponses()
    {
        return $this->_responses;
    }

    /**
     * Removes all events from the EventsManager
     *
     * @param string|null $type
     * @throws Exception
     */
    public function detachAll($type = null)
    {
        if ($type === null) {
            $this->_events = null;
        } else {
            if (is_string($type) === false) {
                throw new Exception('Invalid parameter type.');
            }
            if (isset($this->_events[$type])) {
                unset($this->_events[$type]);
            }
        }
    }

    /**
     * (没用)
     * Removes all events from the EventsManager; alias of detachAll
     *
     * @deprecated
     * @param string|null $type
     */
    public function dettachAll($type = null)
    {
        $this->detachAll($type);
    }

    /**
     * Internal handler to call a queue of events
     *
     * @param \SplPriorityQueue|array queue
     * @param \Phalcon\Events\Event event
     * @return mixed
     */
    public function fireQueue($queue, EventInterface $event)
    {
        if (!is_array($queue)) {
            if (is_object($queue)) {
                if (!($queue instanceof SplPriorityQueue)) {
                    throw new Exception(
                    sprintf(
                        "Unexpected value type: expected object of type SplPriorityQueue, %s given", get_class($queue)
                    )
                    );
                }
            } else {
                throw new Exception('The queue is not valid');
            }
        }

        $status    = null;
        $arguments = null;
        $eventName = $event->getType();

        if (!is_string($eventName)) {
            throw new Exception('The event type not valid');
        }
        // Get the object who triggered the event
        $source = $event->getSource();

        // Get extra data passed to the event
        $data = $event->getData();

        // Tell if the event is cancelable
        $cancelable = (boolean) $event->isCancelable();

        // Responses need to be traced?
        $collect = (boolean) $this->_collect;
        if (is_object($queue)) {
            $iterator = clone $queue;
            $iterator->top();
            while ($iterator->valid()) {
                $handler = $iterator->current();
                $iterator->next();
                if (is_object($handler)) {
                    if ($handler instanceof \Closure) {
                        if ($arguments === null) {
                            $arguments = [$event, $source, $data];
                        }
                        $status = call_user_func_array($handler, $arguments);
                        if ($collect) {
                            $this->_responses[] = $status;
                        }
                        if ($cancelable) {
                            if ($event->isStopped()) {
                                break;
                            }
                        }
                    } else {

                        // Check if the listener has implemented an event with the same name
                        if (method_exists($handler, $eventName)) {

                            // Call the function in the PHP userland
                            $status = $handler->{$eventName}($event, $source, $data);

                            // Collect the response
                            if ($collect) {
                                $this->_responses[] = $status;
                            }

                            if ($cancelable) {
                                // Check if the event was stopped by the user
                                if ($event->isStopped()) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($queue as $handler) {
                if (is_object($handler)) {
                    if ($handler instanceof \Closure) {
                        // Create the closure arguments
                        if ($arguments === null) {
                            $arguments = [$event, $source, $data];
                        }

                        // Call the function in the PHP userland
                        $status = call_user_func_array($handler, $arguments);

                        // Trace the response
                        if ($collect) {
                            $this->_responses[] = $status;
                        }
                        if ($cancelable) {
                            if ($event->isStopped()) {
                                break;
                            }
                        }
                    } else {
                        if (method_exists($handler, $eventName)) {
                            $status = $handler->{$eventName}($event, $source, $data);
                            if ($collect) {
                                $this->_responses[] = $status;
                            }

                            if ($cancelable) {
                                // Check if the event was stopped by the user
                                if ($event->isStopped()) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $status;
    }

    /**
     * Fires an event in the events manager causing that active listeners be notified about it
     *
     * <code>
     *  $eventsManager->fire('db', $connection);
     * </code>
     *
     * @param string $eventType
     * @param object $source
     * @param mixed $data
     * @param boolean|null $cancelable
     * @return mixed
     * @throws Exception
     */
    public function fire($eventType, $source, $data = null, $cancelable = true)
    {
        if (is_string($eventType) === false || is_bool($cancelable) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $events = $this->_events;

        if (!is_array($events)) {
            return null;
        }
        if (!Text::memstr($eventType, ':')) {
            throw new Exception("Invalid event type " . $eventType);
        }

        $eventParts = explode(':', $eventType);
        $type       = $eventParts[0];
        $eventName  = $eventParts[1];

        $status = null;
        if ($this->_collect) {
            $this->_responses = null;
        }

        $event = null;
        if (isset($events[$type])) {
            $fireEvents = $events[$type];
            if (is_object($fireEvents) || is_array($fireEvents)) {
                $event  = new Event($eventName, $source, $data, $cancelable);
                $status = $this->fireQueue($fireEvents, $event);
            }
        }

        if (isset($events[$eventType])) {
            $fireEvents = $events[$eventType];
            if (is_object($fireEvents) || is_array($fireEvents)) {
                // Create the event if it wasn't created before
                if ($event === null) {
                    $event = new Event($eventName, $source, $data, $cancelable);
                }

                // Call the events queue
                $status = $this->fireQueue($fireEvents, $event);
            }
        }

        return $status;
    }

    /**
     * Check whether certain type of event has listeners
     *
     * @param string $type
     * @return boolean
     * @throws Exception
     */
    public function hasListeners($type)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }
        return isset($this->_events[$type]);
    }

    /**
     * Returns all the attached listeners of a certain type
     *
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function getListeners($type)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $events = $this->_events;
        if (is_array($events)) {
            if (isset($events[$type])) {
                return $events[$type];
            }
        }
        return [];
    }

}
