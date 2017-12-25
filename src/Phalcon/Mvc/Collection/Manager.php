<?php

namespace Phalcon\Mvc\Collection;

use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\Events\EventsAwareInterface;
use \Phalcon\Mvc\Collection\Exception;
use \Phalcon\DiInterface;
use \Phalcon\Events\ManagerInterface;
use \Phalcon\Mvc\CollectionInterface;

/**
 * Phalcon\Mvc\Collection\Manager
 *
 * This components controls the initialization of models, keeping record of relations
 * between the different models of the application.
 *
 * A CollectionManager is injected to a model via a Dependency Injector Container such as Phalcon\Di.
 *
 * <code>
 * $di = new Phalcon\Di();
 *
 * $di->set('collectionManager', function(){
 *      return new Phalcon\Mvc\Collection\Manager();
 * });
 *
 * $robot = new Robots($di);
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/collection/manager.c
 */
class Manager implements InjectionAwareInterface, EventsAwareInterface
{

    /**
     * Dependency Injector
     *
     * @var \Phalcon\DiInterface|null
     * @access protected
     */
    protected $_dependencyInjector;

    /**
     * Initialized
     *
     * @var null|array
     * @access protected
     */
    protected $_initialized;

    /**
     * Last Initialized
     *
     * @var null|\Phalcon\Mvc\CollectionInterface
     * @access protected
     */
    protected $_lastInitialized;

    /**
     * Events Manager
     *
     * @var \Phalcon\Events\ManagerInterface|null
     * @access protected
     */
    protected $_eventsManager;

    /**
     * Custom Events Manager
     *
     * @var array|null
     * @access protected
     */
    protected $_customEventsManager;

    /**
     * Connection Services
     *
     * @var null|array
     * @access protected
     */
    protected $_connectionServices;

    /**
     * Implicit Object Ids
     *
     * @var null|array
     * @access protected
     */
    protected $_implicitObjectsIds;

    protected $_behaviors;

    protected $_serviceName = 'mongo';

    /**
     * Sets the DependencyInjector container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('The dependency injector is invalid');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the DependencyInjector container
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets the event manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws Exception
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        if (is_object($eventsManager) === false ||
            $eventsManager instanceof ManagerInterface === false) {
            throw new Exception('Invalid parameter type.');
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
     * Sets a custom events manager for a specific model
     *
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setCustomEventsManager(CollectionInterface $model,ManagerInterface $eventsManager)
    {
        $this->_customEventsManager[strtolower(get_class($model))] = $eventsManager;
    }

    /**
     * Returns a custom events manager related to a model
     *
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @return \Phalcon\Events\ManagerInterface|null
     * @throws Exception
     */
    public function getCustomEventsManager(CollectionInterface $model)
    {
        $customEventsManager = $this->_customEventsManager;
        if (is_array($customEventsManager)) {
            $className = strtolower(get_class($model));
            if( isset($customEventsManager[$className])) {
                return $customEventsManager[$className];
            }
        }
        return null;
    }

    /**
     * Initializes a model in the models manager
     *
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @throws Exception
     */
    public function initialize(CollectionInterface $model)
    {
        $className = strtolower(get_class($model));
        $initialized = $this->_initialized;

        /**
         * Models are just initialized once per request
         */
        if (!isset($initialized[$className])) {

            /**
             * Call the 'initialize' method if it's implemented
             */
            if (method_exists($model, "initialize")) {
               $model->{"initialize"}();
            }

            /**
             * If an EventsManager is available we pass to it every initialized model
             */
            $eventsManager = $this->_eventsManager;
			if (is_object($eventsManager)) {
                $eventsManager->fire("collectionManager:afterInitialize", $model);
			}

			$this->_initialized[$className] = $model;
            $this->_lastInitialized = $model;
		}
    }

    /**
     * Check whether a model is already initialized
     *
     * @param string $modelName
     * @return bool
     * @throws Exception
     */
    public function isInitialized($modelName)
    {
        if (is_string($modelName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->_initialized[strtolower($modelName)]);
    }

    /**
     * Get the latest initialized model
     *
     * @return \Phalcon\Mvc\CollectionInterface|null
     */
    public function getLastInitialized()
    {
        return $this->_lastInitialized;
    }

    /**
     * Sets a connection service for a specific model
     *
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @param string $connectionService
     * @throws Exception
     */
    public function setConnectionService(CollectionInterface $model, $connectionService)
    {
        if (is_string($connectionService) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_connectionServices[strtolower(get_class($model))] = $connectionService;
    }

    /**
     * Gets a connection service for a specific model
     */
    public function getConnectionService(CollectionInterface $model)
	{
		$service = $this->_serviceName;
		$entityName = get_class($model);
		if (isset($this->_connectionServices[$entityName])) {
			$service = $this->_connectionServices[$entityName];
		}
        return $service;
    }

    /**
     * Sets if a model must use implicit objects ids
     *
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @param boolean $useImplicitObjectIds
     * @throws Exception
     */
    public function useImplicitObjectIds(CollectionInterface $model, $useImplicitObjectIds)
    {
        if (is_bool($useImplicitObjectIds) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_implicitObjectsIds[strtolower(get_class($model))] = $useImplicitObjectIds;
    }

    /**
     * Checks if a model is using implicit object ids
     *
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @return boolean
     * @throws Exception
     */
    public function isUsingImplicitObjectIds(CollectionInterface $model)
    {

        $entityName = strtolower(get_class($model));

        if (is_array($this->_implicitObjectsIds) === false) {
            $this->_implicitObjectsIds = array();
        }

        //All collections use by default implicit object ids
        return isset($this->_implicitObjectsIds[$entityName]) ? $this->_implicitObjectsIds[$entityName] : true;
    }

    /**
     * Returns the connection related to a model
     *
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @return \Phalcon\Db\AdapterInterface
     * @throws Exception
     */
    public function getConnection(CollectionInterface $model)
    {
        $service = $this->_serviceName;
		$connectionService = $this->_connectionServices;
		if (isset($connectionService)) {
            $entityName = get_class($model);

			/**
             * Check if the model has a custom connection service
             */
			if (isset($connectionService[$entityName])) {
                $service = $connectionService[$entityName];
			}
		}

		$dependencyInjector = $this->_dependencyInjector;
		if (! is_object($dependencyInjector)) {
            throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
        }

		/**
         * Request the connection service from the DI
         */
		$connection = $dependencyInjector->getShared($service);
		if (!is_object($connection)) {
            throw new Exception("Invalid injected connection service");
        }

		return $connection;
    }

    /**
     * Receives events generated in the models and dispatches them to a events-manager if available
     * Notify the behaviors that are listening in the model
     *
     * @param string $eventName
     * @param \Phalcon\Mvc\CollectionInterface $model
     * @return mixed
     * @throws Exception
     */
    public function notifyEvent($eventName,CollectionInterface $model)
    {
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $status = null;
        $behaviors = $this->_behaviors;
		if (is_array($behaviors)) {
		    $className = strtolower(get_class($model));
		    if(isset($behaviors[$className])) {
		        $modelsBehaviors = $behaviors[$className];
		        foreach ($modelsBehaviors as $behavior) {
                    $status = $behavior->notify($eventName, $model);
					if ($status === false) {
                        return false;
                    }
                }
            }
		}

		/**
         * Dispatch events to the global events manager
         */
		$eventsManager = $this->_eventsManager;
		if (is_object($eventsManager)) {
            $status = $eventsManager->fire( "collection:". $eventName, $model);
			if (!$status) {
                return $status;
            }
		}

		/**
         * A model can has a specific events manager for it
         */
		$customEventsManager = $this->_customEventsManager;
		if (is_array($customEventsManager)) {
            if (isset($customEventsManager[strtolower(get_class($model))])) {
                $status = $customEventsManager->fire("collection:" . $eventName, $model);
                if (!$status) {
                    return $status;
                }
            }
        }
		return $status;
    }

    /**
     * Dispatch an event to the listeners and behaviors
     * This method expects that the endpoint listeners/behaviors returns true
     * meaning that at least one was implemented
     */
    public function missingMethod(CollectionInterface $model, $eventName, $data)
	{
        if(!is_string($eventName)) {
            throw new Exception('Invalid parameter type.');
        }

		/**
         * Dispatch events to the global events manager
         */
		$behaviors = $this->_behaviors;
		if (is_array($behaviors)) {
            $className = strtolower(get_class($model));
            if(isset($behaviors[$className])) {
		        $modelsBehaviors = $behaviors[$className];

                /**
                 * Notify all the events on the behavior
                 */
                foreach ($modelsBehaviors as $behavior) {
                    $result = $behavior->missingMethod($model, $eventName, $data);
					if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        /**
         * Dispatch events to the global events manager
         */
        $eventsManager = $this->_eventsManager;
		if (is_object($eventsManager)) {
            return $eventsManager->fire("model:" . $eventName, $model, $data);
		}

		return false;
	}

    /**
     * Binds a behavior to a model
     */
    public function addBehavior(CollectionInterface $model, BehaviorInterface $behavior)
	{
		$entityName = strtolower(get_class($model));

		/**
         * Get the current behaviors
         */
		if(!isset($this->_behaviors[$entityName])) {
            $modelsBehaviors = [];
        }
        else {
            $modelsBehaviors = $this->_behaviors[$entityName];
        }

        /**
         * Append the behavior to the list of behaviors
         */
        $modelsBehaviors[] = $behavior;

		/**
         * Update the behaviors list
         */
		$this->_behaviors[$entityName] = $modelsBehaviors;
	}

    /**
     * @param $serviceName
     */
    public function setServiceName($serviceName) {
        $this->_serviceName = $serviceName;
    }

    /**
     * @return string
     */
    public function getServiceName() {
        return $this->_serviceName;
    }

}
