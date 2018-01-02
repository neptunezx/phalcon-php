<?php

namespace Phalcon\Mvc;

use function GuzzleHttp\Promise\all;
use Phalcon\Di;
use Phalcon\Db\Column;
use Phalcon\Db\RawValue;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Message;
use Phalcon\Mvc\Model\ResultInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Db\AdapterInterface;
use Phalcon\Db\DialectInterface;
use Phalcon\Mvc\Model\CriteriaInterface;
use Phalcon\Mvc\Model\TransactionInterface;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\MessageInterface;
//use Phalcon\Mvc\Model\Message;
use Phalcon\Text;
use Phalcon\ValidationInterface;
use Phalcon\Mvc\Model\ValidationFailed;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;
use Phalcon\Kernel;
use function Symfony\Component\DependencyInjection\Loader\Configurator\iterator;

/**
 * Phalcon\Mvc\Model
 *
 * <p>Phalcon\Mvc\Model connects business objects and database tables to create
 * a persistable domain model where logic and data are presented in one wrapping.
 * It‘s an implementation of the object-relational mapping (ORM).</p>
 *
 * <p>A model represents the information (data) of the application and the rules to manipulate that data.
 * Models are primarily used for managing the rules of interaction with a corresponding database table.
 * In most cases, each table in your database will correspond to one model in your application.
 * The bulk of your application’s business logic will be concentrated in the models.</p>
 *
 * <p>Phalcon\Mvc\Model is the first ORM written in C-language for PHP, giving to developers high performance
 * when interacting with databases while is also easy to use.</p>
 *
 * <code>
 *
 * $robot = new Robots();
 * $robot->type = 'mechanical';
 * $robot->name = 'Astro Boy';
 * $robot->year = 1952;
 * if ($robot->save() == false) {
 *  echo "Umh, We can store robots: ";
 *  foreach ($robot->getMessages() as $message) {
 *    echo $message;
 *  }
 * } else {
 *  echo "Great, a new robot was saved successfully!";
 * }
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model.c
 */
abstract class Model implements ModelInterface, ResultInterface, InjectionAwareInterface, \Serializable, \JsonSerializable
{

    /**
     * Operation: None
     *
     * @var int
     */
    const OP_NONE = 0;

    /**
     * Operation: Create
     *
     * @var int
     */
    const OP_CREATE = 1;

    /**
     * Operation: Update
     *
     * @var int
     */
    const OP_UPDATE = 2;

    /**
     * Operation: Delete
     *
     * @var int
     */
    const OP_DELETE = 3;

    /**
     * Dirty State: Persistent
     *
     * @var int
     */
    const DIRTY_STATE_PERSISTENT = 0;

    /**
     * Dirty State: Transient
     *
     * @var int
     */
    const DIRTY_STATE_TRANSIENT = 1;

    /**
     * Dirty State: Detached
     *
     * @var int
     */
    const DIRTY_STATE_DETACHED = 2;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
     */
    protected $_dependencyInjector;

    /**
     * Models Manager
     *
     * @var null|\Phalcon\Mvc\Model\ManagerInterface
     * @access protected
     */
    protected $_modelsManager;

    /**
     * Models Metadata
     *
     * @var null|\Phalcon\Mvc\Model\MetaDataInterface
     * @access protected
     */
    protected $_modelsMetaData;

    /**
     * Error Messages
     *
     * @var null|array
     * @access protected
     */
    protected $_errorMessages;

    /**
     * Operation Made
     *
     * @var int
     * @access protected
     */
    protected $_operationMade = 0;

    /**
     * Dirty State
     *
     * @var int
     * @access protected
     */
    protected $_dirtyState = 1;

    /**
     * Transaction
     *
     * @var null|\Phalcon\Mvc\Model\TransactionInterface
     * @access protected
     */
    protected $_transaction;

    /**
     * Unique Key
     *
     * @var null|string
     * @access protected
     */
    protected $_uniqueKey;

    /**
     * Unique Params
     *
     * @var null|array
     * @access protected
     */
    protected $_uniqueParams;

    /**
     * Unique Types
     *
     * @var null|array
     * @access protected
     */
    protected $_uniqueTypes;

    /**
     * Skipped
     *
     * @var null|boolean
     * @access protected
     */
    protected $_skipped;

    /**
     * Related
     *
     * @var null
     * @access protected
     */
    protected $_related;

    /**
     * Snapshot
     *
     * @var null|array
     * @access protected
     */
    protected $_snapshot;
    protected $_oldSnapshot = [];

    /**
     * \Phalcon\Mvc\Model constructor
     *
     * @param $data
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @param \Phalcon\Mvc\Model\ManagerInterface|null $modelsManager
     * @throws Exception
     */
    final public function __construct($data = null, DiInterface $dependencyInjector = null, ManagerInterface $modelsManager = null)
    {
        /**
         * We use a default DI if the user doesn't define one
         */
        if (!is_object($dependencyInjector)) {
            $dependencyInjector = Di::getDefault();
        }

        if (!is_object($dependencyInjector)) {
            throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
        }

        $this->_dependencyInjector = $dependencyInjector;

        /**
         * Inject the manager service from the DI
         */
        if (!is_object($modelsManager)) {
            $modelsManager = $dependencyInjector->getShared("modelsManager");
            if (!is_object($modelsManager)) {
                throw new Exception("The injected service 'modelsManager' is not valid");
            }
        }

        /**
         * Update the models-manager
         */
        $this->_modelsManager = $modelsManager;

        /**
         * The manager always initializes the object
         */
        $modelsManager->initialize($this);

        /**
         * This allows the developer to execute initialization stuff every time an instance is created
         */
        if (method_exists($this, "onConstruct")) {
            $this->{"onConstruct"}($data);
        }

        if (is_array($data)) {
            $this->assign($data);
        }
    }

    /**
     * Sets the dependency injection container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (!is_object($dependencyInjector ||
            !$dependencyInjector instanceof DiInterface)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the dependency injection container
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets a custom events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    protected function setEventsManager(EventsManagerInterface $eventsManager)
    {
        $this->_modelsManager->setCustomEventsManager($this, $eventsManager);
    }

    /**
     * Returns the custom events manager
     *
     * @return \Phalcon\Events\ManagerInterface
     */
    protected function getEventsManager()
    {
        return $this->_modelsManager->getCustomEventsManager($this);
    }

    /**
     * Returns the models meta-data service related to the entity instance
     *
     * @return \Phalcon\Mvc\Model\MetaDataInterface
     * @throws Exception
     */
    public function getModelsMetaData()
    {
        if (is_object($this->_modelsMetaData) === false) {
            /*
              @see __construct

              if(is_object($this->_dependencyInjector) === false) {
              throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
              }
             */
            $metaData = $this->_dependencyInjector->getShared('modelsMetadata');
            if (is_object($metaData) === false) {
                //@note no interface validation
                throw new Exception("The injected service 'modelsMetadata' is not valid");
            }

            $this->_modelsMetaData = $metaData;
        }

        return $this->_modelsMetaData;
    }

    /**
     * Returns the models manager related to the entity instance
     *
     * @return \Phalcon\Mvc\Model\ManagerInterface
     */
    public function getModelsManager()
    {
        return $this->_modelsManager;
    }

    /**
     * Sets a transaction related to the Model instance
     *
     * <code>
     * use \Phalcon\Mvc\Model\Transaction\Manager as TxManager;
     * use \Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
     *
     * try {
     *
     *  $txManager = new TxManager();
     *
     *  $transaction = $txManager->get();
     *
     *  $robot = new Robots();
     *  $robot->setTransaction($transaction);
     *  $robot->name = 'WALL·E';
     *  $robot->created_at = date('Y-m-d');
     *  if ($robot->save() == false) {
     *    $transaction->rollback("Can't save robot");
     *  }
     *
     *  $robotPart = new RobotParts();
     *  $robotPart->setTransaction($transaction);
     *  $robotPart->type = 'head';
     *  if ($robotPart->save() == false) {
     *    $transaction->rollback("Robot part cannot be saved");
     *  }
     *
     *  $transaction->commit();
     *
     * } catch (TxFailed $e) {
     *  echo 'Failed, reason: ', $e->getMessage();
     * }
     *
     * </code>
     *
     * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
     * @return \Phalcon\Mvc\Model
     */
    public function setTransaction(TransactionInterface $transaction)
    {
        $this->_transaction = $transaction;
        return $this;
    }

    /**
     * Sets table name which model should be mapped
     *
     * @param string $source
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    protected function setSource($source)
    {
        if (is_string($source) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setModelSource($this, $source);
        return $this;
    }

    /**
     * Returns table name mapped in the model
     *
     * @return string
     */
    public function getSource()
    {
        return $this->_modelsManager->getModelSource($this);
    }

    /**
     * Sets schema name where table mapped is located
     *
     * @param string $schema
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    protected function setSchema($schema)
    {
        if (is_string($schema) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setModelSchema($this, $schema);

        return $this;
    }

    /**
     * Returns schema name where table mapped is located
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->_modelsManager->getModelSchema($this);
    }

    /**
     * Sets the DependencyInjection connection service name
     *
     * @param string $connectionService
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setConnectionService($connectionService)
    {
        if (is_string($connectionService) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Sets the DependencyInjection connection service name used to read data
     *
     * @param string $connectionService
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setReadConnectionService($connectionService)
    {
        if (is_string($connectionService) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setReadConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Sets the DependencyInjection connection service name used to write data
     *
     * @param string $connectionService
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setWriteConnectionService($connectionService)
    {
        if (is_string($connectionService) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_modelsManager->setWriteConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Returns the DependencyInjection connection service name used to read data related the model
     *
     * @return string
     */
    public function getReadConnectionService()
    {
        return $this->_modelsManager->getReadConnectionService($this);
    }

    /**
     * Returns the DependencyInjection connection service name used to write data related to the model
     *
     * @return string
     */
    public function getWriteConnectionService()
    {
        return $this->_modelsManager->getWriteConnectionService($this);
    }

    /**
     * Sets the dirty state of the object using one of the DIRTY_STATE_* constants
     *
     * @param int $dirtyState
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function setDirtyState($dirtyState)
    {
        if (is_int($dirtyState) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dirtyState = $dirtyState;

        return $this;
    }

    /**
     * Returns one of the DIRTY_STATE_* constants telling if the record exists in the database or not
     *
     * @return int
     */
    public function getDirtyState()
    {
        return $this->_dirtyState;
    }

    /**
     * Gets the connection used to read data for the model
     *
     * @return \Phalcon\Db\AdapterInterface|string
     */
    public function getReadConnection()
    {
        $transaction = $this->_transaction;
        if (is_object($transaction)) {
            return $transaction->getConnection();
        }

        return $this->_modelsManager->getReadConnection($this);
    }

    /**
     * Gets the connection used to write data to the model
     *
     * @return \Phalcon\Db\AdapterInterface|string
     */
    public function getWriteConnection()
    {
        if (is_object($this->_transaction) === true) {
            return $this->_transaction->getConnection();
        }

        return $this->_modelsManager->getWriteConnection($this);
    }

    /**
     * Assigns values to a model from an array
     *
     * <code>
     * $robot->assign(array(
     *  'type' => 'mechanical',
     *  'name' => 'Astro Boy',
     *  'year' => 1952
     * ));
     * </code>
     *
     * @param array $data
     * @param array|null $dataColumnMap
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function assign($data, $dataColumnMap = null, $whiteList = null)
    {
        $disableAssignSetters = Kernel::getGlobals("orm.disable_assign_setters");

        // apply column map for data, if exist
        if (is_array($dataColumnMap)) {
            $dataMapped = [];
            foreach ($data as $key => $value) {
                if (isset($dataColumnMap[$key])) {
                    $keyMapped = $dataColumnMap[$key];
                    $dataMapped[$keyMapped] = $value;
                }
            }
        } else {
            $dataMapped = $data;
        }

        if (count($dataMapped) == 0) {
            return $this;
        }

        $metaData = $this->getModelsMetaData();

        if (Kernel::getGlobals("orm.column_renaming")) {
            $columnMap = $metaData->getColumnMap($this);
        } else {
            $columnMap = null;
        }

        foreach ($metaData->getAttributes($this) as $attribute) {
            // Check if we need to rename the field
            if (is_array($columnMap)) {
                if (isset($columnMap[$attribute])) {
                    $attributeField = $columnMap[$attribute];
                } else {
                    if (!Kernel::getGlobals('orm.ignore_unknown_columns')) {
                        throw new Exception("Column '" . $attribute . "' doesn\'t make part of the column map");
                    } else {
                        continue;
                    }
                }
            } else {
                $attributeField = $attribute;
            }

            // The value in the array passed
            // Check if we there is data for the field

            if (isset($dataMapped[$attributeField])) {
                $value = $dataMapped[$attributeField];
                // If white-list exists check if the attribute is on that list
                if (is_array($whiteList)) {
                    if (!in_array($attributeField, $whiteList)) {
                        continue;
                    }
                }

                // Try to find a possible getter
                if ($disableAssignSetters || !$this->_possibleSetter($attributeField, $value)) {
                    $this->{$attributeField} = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Assigns values to a model from an array returning a new model.
     *
     * <code>
     * $robot = \Phalcon\Mvc\Model::cloneResultMap(new Robots(), array(
     *  'type' => 'mechanical',
     *  'name' => 'Astro Boy',
     *  'year' => 1952
     * ));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $base
     * @param array $data
     * @param array $columnMap
     * @param int|null $dirtyState
     * @param boolean|null $keepSnapshots
     * @return Model|ModelInterface
     * @throws Exception
     */
    public static function cloneResultMap($base, $data, $columnMap, $dirtyState = 0, $keepSnapshots = null)
    {

        if (!is_array($data) ||
            is_int($dirtyState === false) ||
            (!is_bool($keepSnapshots) === false && !is_null($keepSnapshots))) {
            throw new Exception('Invalid parameter type.');
        }
        $instance = clone $base;

        // Change the dirty state to persistent
        $instance->setDirtyState($dirtyState);

        foreach ($data as $key => $value) {
            if (is_string($key)) {
                // Only string keys in the data are valid
                if (!is_array($columnMap)) {
                    $instance->{$key} = $value;
                    continue;
                }

                // Every field must be part of the column map
                if (!isset($columnMap[$key])) {
                    if (!Kernel::getGlobals('orm.ignore_unknown_columns')) {
                        throw new Exception("Column '" . $key . "' doesn't make part of the column map");
                    } else {
                        continue;
                    }
                } else {
                    $attribute = $columnMap[$key];
                }

                if (!is_array($attribute)) {
                    $instance->{$attribute} = $value;
                    continue;
                }


                if ($value != "" && $value !== null) {
                    switch ($attribute[1]) {

                        case Column::TYPE_INTEGER:
                            $castValue = intval($value, 10);
                            break;

                        case Column::TYPE_DOUBLE:
                        case Column::TYPE_DECIMAL:
                        case Column::TYPE_FLOAT:
                            $castValue = doubleval($value);
                            break;

                        case Column::TYPE_BOOLEAN:
                            $castValue = (boolean)$value;
                            break;

                        default:
                            $castValue = $value;
                            break;
                    }
                } else {
                    switch ($attribute[1]) {

                        case Column::TYPE_INTEGER:
                        case Column::TYPE_DOUBLE:
                        case Column::TYPE_DECIMAL:
                        case Column::TYPE_FLOAT:
                        case Column::TYPE_BOOLEAN:
                            $castValue = null;
                            break;

                        default:
                            $castValue = $value;
                            break;
                    }
                }
                $attributeName = $attribute[0];
                $instance->{$attributeName} = $castValue;
            }
        }

        /**
         * Models that keep snapshots store the original data in t
         */
        if ($keepSnapshots) {
            $instance->setSnapshotData($data, $columnMap);
        }

        /**
         * Call afterFetch, this allows the developer to execute actions after a record is fetched from the database
         */
        if (method_exists($instance, "fireEvent")) {
            $instance->{"fireEvent"}("afterFetch");
        }

        return $instance;
    }

    /**
     * Returns an hydrated result based on the data and the column map
     *
     * @param array $data
     * @param array $columnMap
     * @param int $hydrationMode
     * @return mixed
     * @throws Exception
     */
    public static function cloneResultMapHydrate($data, $columnMap, $hydrationMode)
    {
        $hydrateObject = null;
        $hydrateArray = null;
        if (is_array($data) === false) {
            throw new Exception('Data to hydrate must be an Array'); //@note fixed typo
        }

        if (is_int($hydrationMode) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * If there is no column map and the hydration mode is arrays return the data as it is
         */
        if (!is_array($columnMap)) {
            if ($hydrationMode == Resultset::HYDRATE_ARRAYS) {
                return $data;
            }
        }


        /**
         * Create the destination object according to the hydration mode
         */
        if ($hydrationMode == Resultset::HYDRATE_ARRAYS) {
            $hydrateArray = [];
        } else {
            $hydrateObject = new \stdclass();
        }

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($columnMap)) {

                /**
                 * Every field must be part of the column map
                 */
                if (!isset($columnMap[$key])) {
                    if (!Kernel::getGlobals("orm.ignore_unknown_columns")) {
                        throw new Exception("Column '" . $key . "' doesn't make part of the column map");
                    } else {
                        continue;
                    }
                }
                $attribute = $columnMap[$key];

                /**
                 * Attribute can store info about his type
                 */
                if (is_array($attribute)) {
                    $attributeName = $attribute[0];
                } else {
                    $attributeName = $attribute;
                }

                if ($hydrationMode == Resultset::HYDRATE_ARRAYS) {
                    $hydrateArray[$attributeName] = $value;
                } else {
                    $hydrateObject->{$attributeName} = $value;
                }
            } else {
                if ($hydrationMode == Resultset::HYDRATE_ARRAYS) {
                    $hydrateArray[$key] = $value;
                } else {
                    $hydrateObject->{$key} = $value;
                }
            }
        }

        if ($hydrationMode == Resultset::HYDRATE_ARRAYS) {
            return $hydrateArray;
        }

        return $hydrateObject;
    }

    /**
     * Assigns values to a model from an array returning a new model
     *
     * <code>
     * $robot = \Phalcon\Mvc\Model::cloneResult(new Robots(), array(
     *  'type' => 'mechanical',
     *  'name' => 'Astro Boy',
     *  'year' => 1952
     * ));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $base
     * @param array $data
     * @param int|null $dirtyState
     * @return \Phalcon\Mvc\Model|ModelInterface
     * @throws Exception
     */
    public static function cloneResult(ModelInterface $base, $data, $dirtyState = 0)
    {
        if (is_object($base) === false ||
            $base instanceof ModelInterface === false ||
            is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($dirtyState) === true) {
            $dirtyState = 0;
        } elseif (is_int($dirtyState) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Clone the base record
        $instance = clone $base;

        /**
         * Mark the object as persistent
         */
        $instance->setDirtyState($dirtyState);

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                throw new Exception("Invalid key in array data provided to dumpResult()");
            }
            $instance->{$key} = $value;
        }

        /**
         * Call afterFetch, this allows the developer to execute actions after a record is fetched from the database
         */
        $instance->fireEvent("afterFetch");

        return $instance;
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * <code>
     *
     * //How many robots are there?
     * $robots = Robots::find();
     * echo "There are ", count($robots), "\n";
     *
     * //How many mechanical robots are there?
     * $robots = Robots::find("type='mechanical'");
     * echo "There are ", count($robots), "\n";
     *
     * //Get and print virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name"));
     * foreach ($robots as $robot) {
     *     echo $robot->name, "\n";
     * }
     *
     * //Get first 100 virtual robots ordered by name
     * $robots = Robots::find(array("type='virtual'", "order" => "name", "limit" => 100));
     * foreach ($robots as $robot) {
     *     echo $robot->name, "\n";
     * }
     * </code>
     *
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public static function find($parameters = null)
    {
        $dependencyInjector = Di::getDefault();
        $manager = $dependencyInjector->getShared("modelsManager");

        if (!is_array($parameters)) {
            $params = [];
            if ($parameters !== null) {
                $params[] = $parameters;
            }
        } else {
            $params = $parameters;
        }

        /**
         * Builds a query with the passed parameters
         */
        $builder = $manager->createBuilder($params);
        $builder->from(get_called_class());

        $query = $builder->getQuery();

        /**
         * Check for bind parameters
         */
        if (isset($params["bind"])) {
            $bindParams = $params["bind"];

            if (is_array($bindParams)) {
                $query->setBindParams($bindParams, true);
            }

            if (isset($params["bindTypes"])) {
                $bindTypes = $params["bindTypes"];
                if (is_array($bindTypes)) {
                    $query->setBindTypes($bindTypes, true);
                }
            }
        }


        /**
         * Pass the cache options to the query
         */
        if (isset($params["cache"])) {
            $cache = $params["cache"];
            $query->cache($cache);
        }

        /**
         * Execute the query passing the bind-params and casting-types
         */
        $resultset = $query->execute();

        /**
         * Define an hydration mode
         */
        if (is_object($resultset)) {
            if (isset($params["hydration"])) {
                $hydration = $params["hydration"];
                $resultset->setHydrateMode($hydration);
            }
        }

        return $resultset;
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * <code>
     *
     * //What's the first robot in robots table?
     * $robot = Robots::findFirst();
     * echo "The robot name is ", $robot->name;
     *
     * //What's the first mechanical robot in robots table?
     * $robot = Robots::findFirst("type='mechanical'");
     * echo "The first mechanical robot name is ", $robot->name;
     *
     * //Get first virtual robot ordered by name
     * $robot = Robots::findFirst(array("type='virtual'", "order" => "name"));
     * echo "The first virtual robot name is ", $robot->name;
     *
     * </code>
     *
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public static function findFirst($parameters = null)
    {

        $dependencyInjector = Di::getDefault();
        $manager = $dependencyInjector->getShared("modelsManager");

        if (!is_array($parameters)) {
            $params = [];
            if ($parameters !== null) {
                $params[] = $parameters;
            }
        } else {
            $params = $parameters;
        }

        /**
         * Builds a query with the passed parameters
         */
        $builder = $manager->createBuilder($params);
        if (!$builder instanceof Builder) {
            $builder = null;
        }
        $builder->from(get_called_class());

        /**
         * We only want the first record
         */
        $builder->limit(1);

        $query = $builder->getQuery();

        /**
         * Check for bind parameters
         */
        if (isset($params["bind"])) {
            $bindParams = $params["bind"];

            if (is_array($bindParams)) {
                $query->setBindParams($bindParams, true);
            }

            if (isset($params["bindTypes"])) {
                $bindTypes = $params["bindTypes"];
                if (is_array($bindTypes)) {
                    $query->setBindTypes($bindTypes, true);
                }
            }
        }

        /**
         * Pass the cache options to the query
         */
        if (isset($params["cache"])) {
            $cache = $params["cache"];
            $query->cache($cache);
        }

        /**
         * Return only the first row
         */
        $query->setUniqueRow(true);

        /**
         * Execute the query passing the bind-params and casting-types
         */
        return $query->execute();
    }

    /**
     * Create a criteria for a specific model
     *
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @return \Phalcon\Mvc\Model\Criteria
     */
    public static function query(DiInterface $dependencyInjector = null)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            $dependencyInjector = DI::getDefault();
        }

        /**
         * Gets Criteria instance from DI container
         */
        if ($dependencyInjector instanceof DiInterface) {
            $criteria = $dependencyInjector->get("Phalcon\\Mvc\\Model\\Criteria");
        } else {
            $criteria = new Criteria();
            $criteria->setDI($dependencyInjector);
        }
        $criteria->setModelName(get_called_class());

        return $criteria;
    }

    /**
     * Checks if the current record already exists or not
     *
     * @param \Phalcon\Mvc\Model\MetadataInterface $metaData
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param null $table
     * @return boolean
     * @throws Exception
     */
    protected function _exists(MetaDataInterface $metaData, AdapterInterface $connection, $table = null)
    {

        $uniqueParams = null;
        $uniqueTypes = null;
        $uniqueKey = $this->_uniqueKey;

        //Builds an unique primary key condition

        if ($uniqueKey === null) {

            $primaryKeys = $metaData->getPrimaryKeyAttributes($this);
            $bindDataTypes = $metaData->getBindTypes($this);

            $numberPrimary = count($primaryKeys);
            if (!$numberPrimary) {
                return false;
            }

            /**
             * Check if column renaming is globally activated
             */
            if (Kernel::getGlobals("orm.column_renaming")) {
                $columnMap = $metaData->getColumnMap($this);
            } else {
                $columnMap = null;
            }

            $numberEmpty = 0;
            $wherePk = [];
            $uniqueParams = [];
            $uniqueTypes = [];

            /**
             * We need to create a primary key based on the current data
             */
            foreach ($primaryKeys as $field) {
                if (is_array($columnMap)) {
                    if (isset($columnMap[$field])) {
                        $attributeField = $columnMap[$field];
                    } else {
                        throw new Exception("Column '" . $field . "' isn't part of the column map");
                    }
                } else {
                    $attributeField = $field;
                }

                /**
                 * If the primary key attribute is set append it to the conditions
                 */
                $value = null;
                if (isset($this->{$attributeField})) {
                    $value = $this->{$attributeField};
                    if ($value === null || $value === "") {
                        $numberEmpty++;
                    }
                    $uniqueParams[] = $value;
                } else {
                    $uniqueParams[] = null;
                    $numberEmpty++;
                }

                if (!isset($bindDataTypes[$field])) {
                    throw new Exception("Column '" . $field . "' isn't part of the table columns");
                } else {
                    $type = $bindDataTypes[$field];
                }

                $uniqueTypes[] = $type;
                $wherePk[] = $connection->escapeIdentifier($field) . " = ?";
            }

            /**
             * There are no primary key fields defined, assume the record does not exist
             */
            if ($numberPrimary == $numberEmpty) {
                return false;
            }

            $joinWhere = join(" AND ", $wherePk);

            /**
             * The unique key is composed of 3 parts _uniqueKey, uniqueParams, uniqueTypes
             */
            $this->_uniqueKey = $joinWhere;
            $this->_uniqueParams = $uniqueParams;
            $this->_uniqueTypes = $uniqueTypes;
            $uniqueKey = $joinWhere;
        }


        /**
         * If we already know if the record exists we don't check it
         */
        if (!$this->_dirtyState) {
            return true;
        }

        if ($uniqueKey === null) {
            $uniqueKey = $this->_uniqueKey;
        }

        if ($uniqueParams === null) {
            $uniqueParams = $this->_uniqueParams;
        }

        if ($uniqueTypes === null) {
            $uniqueTypes = $this->_uniqueTypes;
        }

        $schema = $this->getSchema();
        $source = $this->getSource();
        if ($schema) {
            $table = [$schema, $source];
        } else {
            $table = $source;
        }

        /**
         * Here we use a single COUNT(*) without PHQL to make the execution faster
         */
        $num = $connection->fetchOne(
            "SELECT COUNT(*) \"rowcount\" FROM " . $connection->escapeIdentifier($table) . " WHERE " . $uniqueKey,
            $uniqueParams,
            $uniqueTypes
        );
        if ($num["rowcount"]) {
            $this->_dirtyState = self::DIRTY_STATE_PERSISTENT;
            return true;
        } else {
            $this->_dirtyState = self::DIRTY_STATE_TRANSIENT;
        }

        return false;
    }

// ====== 伟大的分割线 ======

    /**
     * Generate a PHQL SELECT statement for an aggregate
     *
     * @param string $functionName
     * @param string $alias
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    protected static function _groupResult($functionName, $alias, $parameters)
    {
        if (is_string($functionName) === false ||
            is_string($alias) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $dependencyInjector = Di::getDefault();
        if (is_object($dependencyInjector) === false ||
            !$dependencyInjector instanceof ManagerInterface) {
            throw new Exception('Invalid parameter type.');
        }
        $manager = $dependencyInjector->getShared("modelsManager");

        if (!is_array($parameters)) {
            $params = [];
            if ($parameters !== null) {
                $params[] = $parameters;
            }
        } else {
            $params = $parameters;
        }
        if (isset($params['column'])) {
            $groupColumn = $params;
        } else {
            $groupColumn = '*';
        }

        /**
         * Builds the columns to query according to the received parameters
         */
        if (isset($params['distinct'])) {
            $distinctColumn = $params['distinct'];
            $columns = $functionName . "(DISTINCT " . $distinctColumn . ") AS " . $alias;
        } else {
            if (isset($params['group'])) {
                $groupColumns = $params['group'];
                $columns = $groupColumns . ", " . $functionName . "(" . $groupColumn . ") AS " . $alias;
            } else {
                $columns = $functionName . "(" . $groupColumn . ") AS " . $alias;
            }
        }

        /**
         * Builds a query with the passed parameters
         */
        $builder = $manager->createBuilder($params);
        if (!$builder instanceof Builder) {
            $builder = null;
        }

        $builder->columns($columns);
        $builder->from(get_called_class());

        $query = $builder->getQuery();


        /**
         * Check for bind parameters
         */
        $bindParams = null;
        $bindTypes = null;
        if (isset($params['bind'])) {
            $bindParams = $params['bind'];
            $bindTypes = $params['bindTypes'];
        }

        /**
         * Pass the cache options to the query
         */
        if (isset($params['cache'])) {
            $cache = $params['cache'];
            $query->cache($cache);
        }

        /**
         * Execute the query
         */
        $resultset = $query->execute($bindParams, $bindTypes);

        /**
         * Return the full resultset if the query is grouped
         */
        if (isset ($params["group"])) {
            return $resultset;
        }

        /**
         * Return only the value in the first result
         */
        $firstRow = $resultset->getFirst();
        return $firstRow->{$alias};
    }

    /**
     * Allows to count how many records match the specified conditions
     *
     * <code>
     *
     * //How many robots are there?
     * $number = Robots::count();
     * echo "There are ", $number, "\n";
     *
     * //How many mechanical robots are there?
     * $number = Robots::count("type='mechanical'");
     * echo "There are ", $number, " mechanical robots\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return mixed
     */
    public static function count($parameters = null)
    {

        $result = self::_groupResult("COUNT", "rowcount", $parameters);
        if (is_string($result)) {
            return (int)$result;
        }
        return $result;
    }

    /**
     * Allows to calculate a summatory on a column that match the specified conditions
     *
     * <code>
     *
     * //How much are all robots?
     * $sum = Robots::sum(array('column' => 'price'));
     * echo "The total price of robots is ", $sum, "\n";
     *
     * //How much are mechanical robots?
     * $sum = Robots::sum(array("type='mechanical'", 'column' => 'price'));
     * echo "The total price of mechanical robots is  ", $sum, "\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return double | mixed
     */
    public static function sum($parameters = null)
    {
        return self::_groupResult('SUM', 'summatory', $parameters);
    }

    /**
     * Allows to get the maximum value of a column that match the specified conditions
     *
     * <code>
     *
     * //What is the maximum robot id?
     * $id = Robots::maximum(array('column' => 'id'));
     * echo "The maximum robot id is: ", $id, "\n";
     *
     * //What is the maximum id of mechanical robots?
     * $sum = Robots::maximum(array("type='mechanical'", 'column' => 'id'));
     * echo "The maximum robot id of mechanical robots is ", $id, "\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return mixed
     */
    public static function maximum($parameters = null)
    {
        return self::_groupResult('MAX', 'maximum', $parameters);
    }

    /**
     * Allows to get the minimum value of a column that match the specified conditions
     *
     * <code>
     *
     * //What is the minimum robot id?
     * $id = Robots::minimum(array('column' => 'id'));
     * echo "The minimum robot id is: ", $id;
     *
     * //What is the minimum id of mechanical robots?
     * $sum = Robots::minimum(array("type='mechanical'", 'column' => 'id'));
     * echo "The minimum robot id of mechanical robots is ", $id;
     *
     * </code>
     *
     * @param array|null $parameters
     * @return mixed
     */
    public static function minimum($parameters = null)
    {
        return self::_groupResult('MIN', 'minimum', $parameters);
    }

    /**
     * Allows to calculate the average value on a column matching the specified conditions
     *
     * <code>
     *
     * //What's the average price of robots?
     * $average = Robots::average(array('column' => 'price'));
     * echo "The average price is ", $average, "\n";
     *
     * //What's the average price of mechanical robots?
     * $average = Robots::average(array("type='mechanical'", 'column' => 'price'));
     * echo "The average price of mechanical robots is ", $average, "\n";
     *
     * </code>
     *
     * @param array|null $parameters
     * @return double|mixed
     */
    public static function average($parameters = null)
    {
        return self::_groupResult('AVG', 'average', $parameters);
    }

    /**
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     *
     * @param string $eventName
     * @return boolean
     * @throws Exception
     */
    public function fireEvent($eventName)
    {
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Check if there is a method with the same name of the event
        if (method_exists($this, $eventName)) {
            $this->$eventName();
        }

        //Send a notification to the events manager
        return $this->_modelsManager->notifyEvent($eventName, $this);
    }

    /**
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     * This method stops if one of the callbacks/listeners returns boolean false
     *
     * @param string $eventName
     * @return boolean
     * @throws Exception
     */
    public function fireEventCancel($eventName)
    {
        if (is_string($eventName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Check if there is a method with the same name of the event
        if (method_exists($this, $eventName)) {
            if ($this->$eventName() === false) {
                return false;
            }
        }

        //Send a notification to the events manager
        if ($this->_modelsManager->notifyEvent($eventName, $this) === false) {
            return false;
        }

        return true;
    }

    /**
     * Cancel the current operation
     */
    protected function _cancelOperation()
    {
        if ($this->_operationMade == self::OP_DELETE) {
            $this->fireEvent("notDeleted");
        } else {
            $this->fireEvent("notSaved");
        }
    }

    /**
     * @param MessageInterface $message
     * @return $this
     * @throws Exception
     */
    public function appendMessage($message)
    {
        if (is_object($message) === false ||
            $message instanceof MessageInterface === false) {
            throw new Exception("Invalid message format '" . getType($message) . "'");
        }

        $this->_errorMessages[] = $message;

        return $this;
    }

    /**
     * Executes validators on every validation call
     *
     * <code>
     * use \Phalcon\Mvc\Model\Validator\ExclusionIn as ExclusionIn;
     *
     * class Subscriptors extends \Phalcon\Mvc\Model
     * {
     *
     *  public function validation()
     *  {
     *      $this->validate(new ExclusionIn(array(
     *          'field' => 'status',
     *          'domain' => array('A', 'I')
     *      )));
     *      if ($this->validationHasFailed() == true) {
     *          return false;
     *      }
     *  }
     *
     * }
     * </code>
     *
     * @param ValidationInterface $validator
     * @return \Phalcon\Mvc\Model|mixed
     * @throws Exception
     */
    protected function validate(ValidationInterface $validator)
    {
        //@note no validator interface validation
        $messages = $validator->validate(null, $this);

        // Call the validation, if it returns not the boolean
        // we append the messages to the current object
        if (is_bool($messages)) {
            return $messages;
        }

        foreach (iterator($messages) as $message) {
            $this->appendMessage(
                new Message(
                    $message->getMessage(),
                    $message->getField(),
                    $message->getType(),
                    null,
                    $message->getCode()
                )
            );
        }

        // If there is a message, it returns false otherwise true
        return !count($messages);
    }

    /**
     * Check whether validation process has generated any messages
     *
     * <code>
     * use \Phalcon\Mvc\Model\Validator\ExclusionIn as ExclusionIn;
     *
     * class Subscriptors extends \Phalcon\Mvc\Model
     * {
     *
     *  public function validation()
     *  {
     *      $this->validate(new ExclusionIn(array(
     *          'field' => 'status',
     *          'domain' => array('A', 'I')
     *      )));
     *      if ($this->validationHasFailed() == true) {
     *          return false;
     *      }
     *  }
     *
     * }
     * </code>
     *
     * @return boolean
     */
    public function validationHasFailed()
    {
        $errorMessages = $this->_errorMessages;
        if (is_array($errorMessages)) {
            return count($errorMessages) > 0;
        }
        return false;
    }

    /**
     * Returns all the validation messages
     *
     * <code>
     *  $robot = new Robots();
     *  $robot->type = 'mechanical';
     *  $robot->name = 'Astro Boy';
     *  $robot->year = 1952;
     *  if ($robot->save() == false) {
     *      echo "Umh, We can't store robots right now ";
     *      foreach ($robot->getMessages() as $message) {
     *          echo $message;
     *      }
     *  } else {
     *      echo "Great, a new robot was saved successfully!";
     *  }
     * </code>
     *
     * @return \Phalcon\Mvc\Model\MessageInterface[]|null
     */
    public function getMessages($filter = null)
    {

        if (is_string($filter) && !isEmpty($filter)) {
            $filtered = [];
            foreach ($this->_errorMessages as $message) {
                if ($message->getField() == $filter) {
                    $filtered[] = $message;
                }
            }
            return $filtered;
        }

        return $this->_errorMessages;
    }

    /**
     * Reads "belongs to" relations and check the virtual foreign keys when inserting or updating records
     * to verify that inserted/updated values are present in the related entity
     *
     * @return bool
     * @throws Exception
     */
    protected function _checkForeignKeysRestrict()
    {
        //Get the models manager

        $manager = $this->_modelsManager;
        if (is_object($manager) === false ||
            !$manager instanceof ManagerInterface) {
            throw new Exception('Invalid parameter type.');
        }

        $belongsTo = $manager->getBelongsTo($this);
        if (is_array($belongsTo) === false) {
            $belongsTo = array();
        }
        $error = false;
        foreach ($belongsTo as $relation) {

            $validateWithNulls = false;
            $foreignKey = $relation->getForeignKey();
            if ($foreignKey === false) {
                continue;
            }

            /**
             * By default action is restrict
             */
            $action = Relation::ACTION_RESTRICT;

            /**
             * Try to find a different action in the foreign key's options
             */
            if (is_array($foreignKey)) {
                if (isset($foreignKey["action"])) {
                    $action = (int)$foreignKey["action"];
                }
            }

            /**
             * Check only if the operation is restrict
             */
            if ($action != Relation::ACTION_RESTRICT) {
                continue;
            }

            /**
             * Load the referenced model if needed
             */
            $referencedModel = $manager->load($relation->getReferencedModel());

            /**
             * Since relations can have multiple columns or a single one, we need to build a condition for each of these cases
             */
            $conditions = [];
            $bindParams = [];

            $numberNull = 0;
            $fields = $relation->getFields();
            $referencedFields = $relation->getReferencedFields();

            if (is_array($fields)) {
                /**
                 * Create a compound condition
                 */
                foreach ($fields as $position => $field) {
                    $value = $this->{$field};
                    $conditions[] = "[" . $referencedFields[$position] . "] = ?" . $position;
                    $bindParams[] = $value;
                    if (is_null($value)) {
                        $numberNull++;
                    }
                }

                $validateWithNulls = $numberNull == count($fields);

            } else {

                $value = $this->{$fields};
                $conditions[] = "[" . $referencedFields . "] = ?0";
                $bindParams[] = $value;

                if (is_null($value)) {
                    $validateWithNulls = true;
                }
            }

            /**
             * Check if the virtual foreign key has extra conditions
             */
            if (isset($foreignKey['conditions'])) {
                $extraConditions = $foreignKey['conditions'];
                $conditions[] = $extraConditions;
            }

            /**
             * Check if the relation definition allows nulls
             */
            if ($validateWithNulls) {
                if (isset($foreignKey["allowNulls"])) {
                    $allowNulls = $foreignKey["allowNulls"];
                    $validateWithNulls = (boolean)$allowNulls;
                } else {
                    $validateWithNulls = false;
                }
            }

            /**
             * We don't trust the actual values in the object and pass the values using bound parameters
             * Let's make the checking
             */
            if (!$validateWithNulls && !$referencedModel->count([join(" AND ", $conditions), "bind" => $bindParams])) {

                /**
                 * Get the user message or produce a new one
                 */
                if (isset($foreignKey["message"])) {
                    $message = $foreignKey['message'];
                } else {
                    if (is_array($fields)) {
                        $message = "Value of fields \"" . join(", ", $fields) . "\" does not exist on referenced table";
                    } else {
                        $message = "Value of field \"" . $fields . "\" does not exist on referenced table";
                    }
                }

                /**
                 * Create a message
                 */
                $this->appendMessage(new Message($message, $fields, "ConstraintViolation"));
                $error = true;
                break;
            }
        }

        /**
         * Call 'onValidationFails' if the validation fails
         */
        if ($error === true) {
            if (Kernel::getGlobals("orm.events")) {
                $this->fireEvent("onValidationFails");
                $this->_cancelOperation();
            }
            return false;
        }

        return true;
    }

    /**
     * Reads both "hasMany" and "hasOne" relations and checks the virtual foreign keys (restrict) when deleting records
     *
     * @return boolean
     * @throws Exception
     */
    protected function _checkForeignKeysReverseRestrict()
    {
        //Get the models manager
        $manager = $this->_modelsManager;
        if (is_object($manager) === false ||
            !$manager instanceof ManagerInterface) {
            throw new Exception('Invalid parameter type.');
        }

        //We check if some of the hasOne/hasMany relations are a foreign key
        $relations = $manager->getHasOneAndHasMany($this);
        if (is_array($relations) === false) {
            $relations = [];
        }
        foreach ($relations as $relation) {

            /**
             * Check if the relation has a virtual foreign key
             */
            $foreignKey = $relation->getForeignKey();
            if ($foreignKey === false) {
                continue;
            }

            /**
             * By default action is restrict
             */
            $action = Relation::NO_ACTION;

            /**
             * Try to find a different action in the foreign key's options
             */
            if (is_array($foreignKey)) {
                if (isset($foreignKey["action"])) {
                    $action = (int)$foreignKey["action"];
                }
            }

            /**
             * Check only if the operation is restrict
             */
            if ($action != Relation::ACTION_CASCADE) {
                continue;
            }

            /**
             * Load a plain instance from the models manager
             */
            $referencedModel = $manager->load($relation->getReferencedModel());

            $fields = $relation->getFields();
            $referencedFields = $relation->getReferencedFields();

            /**
             * Create the checking conditions. A relation can has many fields or a single one
             */
            $conditions = [];
            $bindParams = [];

            if (is_array($fields)) {
                foreach ($fields as $position => $field) {
                    $value = $this->{$field};
                    $conditions[] = "[" . $referencedFields[$position] . "] = ?" . $position;
                    $bindParams[] = $value;
                }
            } else {
                $value = $this->{$fields};
                $conditions[] = "[" . $referencedFields . "] = ?0";
                $bindParams[] = $value;
            }

            /**
             * Check if the virtual foreign key has extra conditions
             */
            if (isset($foreignKey["conditions"])) {
                $extraConditions = $foreignKey["conditions"];
                $conditions[] = $extraConditions;
            }

            /**
             * We don't trust the actual values in the object and then we're passing the values using bound parameters
             * Let's make the checking
             */
            $resultset = $referencedModel->find([
                join(" AND ", $conditions),
                "bind" => $bindParams
            ]);
            if (!$resultset instanceof Resultset) {
                $resultset = null;
            }

            /**
             * Delete the resultset
             * Stop the operation if needed
             */
            if ($resultset->delete() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reads both "hasMany" and "hasOne" relations and checks the virtual foreign keys (cascade) when deleting records
     *
     * @return bool
     * @throws Exception
     */
    protected function _checkForeignKeysReverseCascade()
    {
        //Get the models manager
        $manager = $this->_modelsManager;
        if (is_object($manager) === false ||
            !$manager instanceof ManagerInterface) {
            throw new Exception('Invalid parameter type.');
        }
        $relations = $manager->getHasOneAndHasMany($this);

        $error = false;
        foreach ($relations as $relation) {

            /**
             * Check if the relation has a virtual foreign key
             */
            $foreignKey = $relation->getForeignKey();
            if ($foreignKey === false) {
                continue;
            }

            /**
             * By default action is restrict
             */
            $action = Relation::ACTION_RESTRICT;

            /**
             * Try to find a different action in the foreign key's options
             */
            if (is_array($foreignKey)) {
                if (isset ($foreignKey["action"])) {
                    $action = (int)$foreignKey["action"];
                }
            }

            /**
             * Check only if the operation is restrict
             */
            if ($action != Relation::ACTION_RESTRICT) {
                continue;
            }

            $relationClass = $relation->getReferencedModel();

            /**
             * Load a plain instance from the models manager
             */
            $referencedModel = $manager->load($relationClass);

            $fields = $relation->getFields();
            $referencedFields = $relation->getReferencedFields();

            /**
             * Create the checking conditions. A relation can has many fields or a single one
             */
            $conditions = [];
            $bindParams = [];

            if (is_array($fields)) {

                foreach ($fields as $position => $field) {
                    $value = $this->{$field};
                    $conditions[] = "[" . $referencedFields[$position] . "] = ?" . $position;
                    $bindParams[] = $value;
                }

            } else {
                $value = $this->{$fields};
                $conditions[] = "[" . $referencedFields . "] = ?0";
                $bindParams[] = $value;
            }

            /**
             * Check if the virtual foreign key has extra conditions
             */
            if (isset($foreignKey["conditions"])) {
                $extraConditions = $foreignKey["conditions"];
                $conditions[] = $extraConditions;
            }

            /**
             * We don't trust the actual values in the object and then we're passing the values using bound parameters
             * Let's make the checking
             */
            if ($referencedModel->count([join(" AND ", $conditions), "bind" => $bindParams])) {

                /**
                 * Create a new message
                 */
                if (isset($foreignKey['message'])) {
                    $message = $foreignKey['message'];
                } else {
                    $message = "Record is referenced by model " . $relationClass;
                }

                /**
                 * Create a message
                 */
                $this->appendMessage(new Message($message, $fields, "ConstraintViolation"));
                $error = true;
                break;
            }
        }

        /**
         * Call validation fails event
         */
        if ($error === true) {
            if (Kernel::getGlobals("orm.events")) {
                $this->fireEvent("onValidationFails");
                $this->_cancelOperation();
            }
            return false;
        }

        return true;
    }

    /**
     * Executes internal hooks before save a record
     *
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param boolean $exists
     * @param string $identityField
     * @return boolean
     * @throws Exception
     */
    protected function _preSave(MetaDataInterface $metaData, $exists, $identityField)
    {
        if (is_object($metaData) === false ||
            $metaData instanceof MetaDataInterface === false ||
            is_bool($exists) === false ||
            is_string($identityField) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Run Validation Callbacks "Before"
        if (Kernel::getGlobals("orm.events")) {
            //Call the beforeValidation
            if ($this->fireEventCancel('beforeValidation') === false) {
                return false;
            }

            //Call the specific beforeValidation event for the current action
            if ($exists === true) {
                if ($this->fireEventCancel('beforeValidationOnCreate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('beforeValidationOnUpdate') === false) {
                    return false;
                }
            }
        }

        //Check for Virtual foreign keys
        if (Kernel::getGlobals("orm.virtual_foreign_keys")) {
            if ($this->_checkForeignKeysRestrict() === false) {
                return false;
            }
        }

        //Columns marked as not null are automatically validated by the ORM
        if (Kernel::getGlobals("orm.not_null_validations")) {
            $notNull = $metaData->getNotNullAttributes($this);
            if (is_array($notNull) === true) {
                //Get the fields which are numeric, these are validated in a different way
                $dataTypeNumeric = $metaData->getDataTypesNumeric($this);
                if (Kernel::getGlobals("orm.column_renaming")) {
                    $columnMap = $metaData->getColumnMap($this);
                } else {
                    $columnMap = null;
                }

                //Get fields which must be omitted from the SQL generation
                if ($exists) {
                    $automaticAttributes = $metaData->getAutomaticUpdateAttributes($this);
                } else {
                    $automaticAttributes = $metaData->getAutomaticCreateAttributes($this);
                }
                $defaultValues = $metaData->getDefaultValues($this);

                /**
                 * Get string attributes that allow empty strings as defaults
                 */
                $emptyStringValues = $metaData->getEmptyStringAttributes($this);

                $error = false;

                foreach ($notNull as $field) {
                    //We don't check fields which must be omitted
                    if (isset($automaticAttributes[$field]) === false) {
                        $isNull = false;

                        if (is_array($columnMap) === true) {
                            if (isset($columnMap[$field]) === true) {
                                $attributeField = $columnMap[$field];
                            } else {
                                throw new Exception("Column '" . $field . "' isn't part of the column map");
                            }
                        } else {
                            $attributeField = $field;
                        }

                        //Field is null when: 1) is not set, 2) is numeric but its value is not numeric,
                        //3) is null or 4) is empty string
                        if (isset($this->$attributeField) === true) {
                            //Read the attribute from $this using the real or renamed name
                            $value = $this->$attributeField;

                            //Objects are never treated as null, numeric fields must be numeric to be accepted
                            //as not null
                            if (is_object($value) === false) {
                                if (isset($dataTypeNumeric[$field]) === false) {
                                    if (empty($value) === true) {
                                        $isNull = true;
                                    }
                                } else {
                                    if ($value === null || ($value === "" && (!isset ($defaultValues[$field]) || $value !== $defaultValues[$field]))) {
                                        $isNull = true;
                                    }
                                }
                            }
                        } else {
                            $isNull = true;
                        }

                        if ($isNull === true) {
                            if ($exists === false) {
                                //The identity field can be null
                                if ($field === $identityField) {
                                    continue;
                                }
                            }
                            if (isset ($defaultValues[$field])) {
                                continue;
                            }

                            //A implicit PresenceOf message is created
                            $this->_errorMessages[] = new Message($attributeField . ' is required', $attributeField, 'PresenceOf');
                            $error = true;
                        }
                    }
                }

                if ($error === true) {
                    if (Kernel::getGlobals("orm.events")) {
                        $this->fireEvent('onValidationFails');
                        $this->_cancelOperation();
                    }

                    return false;
                }
            }
        }

        //Call the main validation event
        if ($this->fireEventCancel('validation') === false) {
            if (Kernel::getGlobals("orm.events")) {
                $this->fireEvent('onValidationFails');
            }

            return false;
        }

        //Run validation
        if (Kernel::getGlobals("orm.events")) {
            //Run Validation Callbacks "After"
            if ($exists === false) {
                if ($this->fireEventCancel('afterValidationOnCreate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('afterValidationOnUpdate') === false) {
                    return false;
                }
            }

            if ($this->fireEventCancel('afterValidation') === false) {
                return false;
            }

            //Run "Before" Callbacks
            if ($this->fireEventCancel('beforeSave') === false) {
                return false;
            }

            //The operation can be skipped here
            $this->_skipped = false;
            if ($exists === true) {
                if ($this->fireEventCancel('beforeUpdate') === false) {
                    return false;
                }
            } else {
                if ($this->fireEventCancel('beforeCreate') === false) {
                    return false;
                }
            }

            //Always return true if the operation is skipped
            if ($this->_skipped === true) {
                return true;
            }
        }

        return true;
    }

    /**
     * Executes internal events after save a record
     *
     * @param boolean $success
     * @param boolean $exists
     * @return boolean
     * @throws Exception
     */
    protected function _postSave($success, $exists)
    {
        if (is_bool($success) === false ||
            is_bool($exists) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($success === true) {
            if ($exists === true) {
                $this->fireEvent('afterUpdate');
            } else {
                $this->fireEvent('afterCreate');
            }
        }
        return $success;
    }

    /**
     * Sends a pre-build INSERT SQL statement to the relational database system
     *
     * @param \Phalcon\Mvc\Model\MetaDataInterface metaData
     * @param \Phalcon\Db\AdapterInterface connection
     * @param string|array table
     * @param boolean|string identityField
     * @throws Exception
     * @return boolean
     */
    protected function _doLowInsert(MetaDataInterface $metaData, AdapterInterface $connection, $table, $identityField)
    {

        $bindSkip = Column::BIND_SKIP;
        $manager = $this->_modelsManager;
        $fields = array();
        $values = array();
        $bindTypes = array();
        $snapshot = array();
        $columnMap = null;

        $attributes = $metaData->getAttributes($this);
        $bindDataTypes = $metaData->getBindTypes($this);
        $automaticAttributes = $metaData->getAutomaticCreateAttributes($this);
        $defaultValues = $metaData->getDefaultValues($this);

        if (Kernel::getGlobals("orm.column_renaming")) {
            $columnMap = $metaData->getColumnMap($this);
        } else {
            $columnMap = null;
        }

        //All fields in the model are part of the INSERT statement
        foreach ($attributes as $field) {
            if (!isset ($automaticAttributes[$field])) {

                /**
                 * Check if the model has a column map
                 */
                if (is_array($columnMap)) {
                    if (isset($columnMap[$field])) {
                        $attributeField = $columnMap[$field];
                    } else {
                        throw new Exception("Column '" . $field . "' isn't part of the column map");
                    }
                } else {
                    $attributeField = $field;
                }

                /**
                 * Check every attribute in the model except identity field
                 */
                if ($field != $identityField) {

                    /**
                     * This isset checks that the property be defined in the model
                     */
                    if (isset($this->{$attributeField})) {
                        $value = $this->{$attributeField};

                        if ($value === null && isset ($defaultValues[$field])) {
                            $snapshot[$attributeField] = null;
                            $value = $connection->getDefaultValue();
                        } else {
                            $snapshot[$attributeField] = $value;
                        }

                        /**
                         * Every column must have a bind data type defined
                         */
                        if (isset($bindDataTypes[$field])) {
                            $bindType = $bindDataTypes[$field];
                        } else {
                            throw new Exception("Column '" . $field . "' have not defined a bind data type");
                        }

                        $fields[] = $field;
                        $values[] = $value;
                        $bindTypes[] = $bindType;
                    } else {

                        if (isset ($defaultValues[$field])) {
                            $values[] = $connection->getDefaultValue();
                            /**
                             * This is default value so we set null, keep in mind it's value in database!
                             */
                            $snapshot[$attributeField] = null;
                        } else {
                            $values[] = $value;
                            $snapshot[$attributeField] = $value;
                        }

                        $fields[] = $field;
                        $bindTypes[] = $bindSkip;
                    }
                }
            }
        }

        //If there is an identity field, we add it using "null" or "default"
        if ($identityField !== false) {

            $defaultValue = $connection->getDefaultIdValue();

            /**
             * Not all the database systems require an explicit value for identity columns
             */
            $useExplicitIdentity = (boolean)$connection->useExplicitIdValue();
            if ($useExplicitIdentity) {
                $fields[] = $identityField;
            }

            /**
             * Check if the model has a column map
             */
            if (is_array($columnMap)) {
                if (isset($columnMap[$identityField])) {
                    $attributeField = $columnMap[$identityField];
                } else {
                    throw new Exception("Identity column '" . $identityField . "' isn't part of the column map");
                }
            } else {
                $attributeField = $identityField;
            }

            /**
             * Check if the developer set an explicit value for the column
             */
            if (isset($this->{$attributeField})) {
                $value = $this->{$attributeField};

                if ($value === null || $value === "") {
                    if ($useExplicitIdentity) {
                        $values[] = $defaultValue;
                        $bindTypes[] = $bindSkip;
                    }
                } else {

                    /**
                     * Add the explicit value to the field list if the user has defined a value for it
                     */
                    if (!$useExplicitIdentity) {
                        $fields[] = $identityField;
                    }

                    /**
                     * The field is valid we look for a bind value (normally int)
                     */
                    if (isset($bindDataTypes[$identityField])) {
                        $bindType = $bindDataTypes[$identityField];
                    } else {
                        throw new Exception("Identity column '" . $identityField . "' isn\'t part of the table columns");
                    }

                    $values[] = $value;
                    $bindTypes[] = $bindType;
                }
            } else {
                if ($useExplicitIdentity) {
                    $values[] = $defaultValue;
                    $bindTypes[] = $bindSkip;
                }
            }
        }

        //The low-level insert
        $success = $connection->insert($table, $values, $fields, $bindTypes);
        if ($success && $identityField !== false) {
            //We check if the model has sequences
            $sequenceName = null;
            if ($connection->supportSequences() === true) {
                if (method_exists($this, 'getSequenceName') === true) {
                    $sequenceName = $this->{"getSequenceName"}();
                } else {
                    $source = $this->getSource();
                    $schema = $this->getSchema();
                    if (empty($schema)) {
                        $sequenceName = $source . "_" . $identityField . "_seq";
                    } else {
                        $sequenceName = $schema . "." . $source . "_" . $identityField . "_seq";
                    }
                }
            }

//Recover the last "insert id" and assign it to the object
            $lastInsertedId = $connection->lastInsertId($sequenceName);
            $this->{$attributeField} = $lastInsertedId;
            $snapshot[$attributeField] = $lastInsertedId;

//Since the primary key was modified, we delete the _uniqueParams to force any
//future update to rebuild the primary key
            if ($manager->isKeepingSnapshots($this) && Kernel::getGlobals("orm.update_snapshot_on_save")) {
                $this->_snapshot = $snapshot;
            }
            $this->_uniqueParams = null;
        }

        return $success;
    }

    /**
     * Sends a pre-build UPDATE SQL statement to the relational database system
     *
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param string|array $table
     * @return boolean
     * @throws Exception
     */
    protected function _doLowUpdate(MetaDataInterface $metaData, AdapterInterface $connection, $table)
    {
        $bindSkip = Column::BIND_SKIP;
        $fields = [];
        $values = [];
        $bindTypes = [];
        $newSnapshot = [];
        $manager = $this->_modelsManager;

        /**
         * Check if the model must use dynamic update
         */
        $useDynamicUpdate = (boolean)$manager->isUsingDynamicUpdate($this);

        $snapshot = $this->_snapshot;

        if ($useDynamicUpdate) {
            if (is_array($snapshot)) {
                $useDynamicUpdate = false;
            }
        }

        $dataTypes = $metaData->getDataTypes($this);
        $bindDataTypes = $metaData->getBindTypes($this);
        $nonPrimary = $metaData->getNonPrimaryKeyAttributes($this);
        $automaticAttributes = $metaData->getAutomaticUpdateAttributes($this);

        if (Kernel::getGlobals("orm.column_renaming")) {
            $columnMap = $metaData->getColumnMap($this);
        } else {
            $columnMap = null;
        }

        /**
         * We only make the update based on the non-primary attributes, values in primary key attributes are ignored
         */
        foreach ($nonPrimary as $field) {

            if (!isset ($automaticAttributes[$field])) {

                /**
                 * Check a bind type for field to update
                 */
                if (isset($bindDataTypes[$field])) {
                    $bindType = $bindDataTypes[$field];
                } else {
                    throw new Exception("Column '" . $field . "' have not defined a bind data type");
                }

                /**
                 * Check if the model has a column map
                 */
                if (is_array($columnMap)) {
                    if (isset($columnMap[$field])) {
                        $attributeField = $columnMap[$field];
                    } else {
                        throw new Exception("Column '" . $field . "' isn't part of the column map");
                    }
                } else {
                    $attributeField = $field;
                }

                /**
                 * Get the field's value
                 * If a field isn't set we pass a null value
                 */
                if (isset($this->{$attributeField})) {
                    $value = $this->{$attributeField};
                    /**
                     * When dynamic update is not used we pass every field to the update
                     */
                    if (!$useDynamicUpdate) {
                        $fields[] = $field;
                        $values[] = $value;
                        $bindTypes[] = $bindType;
                    } else {

                        /**
                         * If the field is not part of the snapshot we add them as changed
                         */
                        if (!isset($snapshot[$attributeField])) {
                            $changed = true;
                        } else {
                            $snapshotValue = $snapshot[$attributeField];
                            /**
                             * See https://github.com/phalcon/cphalcon/issues/3247
                             * Take a TEXT column with value '4' and replace it by
                             * the value '4.0'. For PHP '4' and '4.0' are the same.
                             * We can't use simple comparison...
                             *
                             * We must use the type of snapshotValue.
                             */
                            if ($value === null) {
                                $changed = $snapshotValue !== null;
                            } else {
                                if ($snapshotValue === null) {
                                    $changed = true;
                                } else {

                                    if (isset($dataTypes[$field])) {
                                        $dataType = $dataTypes[$field];
                                    } else {
                                        throw new Exception("Column '" . $field . "' have not defined a data type");
                                    }

                                    switch ($dataType) {

                                        case Column::TYPE_BOOLEAN:
                                            $changed = (boolean)$snapshotValue !== (boolean)$value;
                                            break;

                                        case Column::TYPE_INTEGER:
                                            $changed = (int)$snapshotValue !== (int)$value;
                                            break;

                                        case Column::TYPE_DECIMAL:
                                        case Column::TYPE_FLOAT:
                                            $changed = floatval($snapshotValue) !== floatval($value);
                                            break;

                                        case Column::TYPE_DATE:
                                        case Column::TYPE_VARCHAR:
                                        case Column::TYPE_DATETIME:
                                        case Column::TYPE_CHAR:
                                        case Column::TYPE_TEXT:
                                        case Column::TYPE_VARCHAR:
                                        case Column::TYPE_BIGINTEGER:
                                            $changed = (string)$snapshotValue !== (string)$value;
                                            break;

                                        /**
                                         * Any other type is not really supported...
                                         */
                                        default:
                                            $changed = $value != $snapshotValue;
                                    }
                                }
                            }
                        }

                        /**
                         * Only changed values are added to the SQL Update
                         */
                        if ($changed) {
                            $fields[] = $field;
                            $values[] = $value;
                            $bindTypes[] = $bindType;
                        }
                    }
                    $newSnapshot[$attributeField] = $value;

                } else {
                    $newSnapshot[$attributeField] = null;
                    $fields[] = $field;
                    $values[] = null;
                    $bindTypes[] = $bindSkip;
                }
            }
        }

        /**
         * If there is no fields to update we return true
         */
        if (!count($fields)) {
            return true;
        }

        $uniqueKey = $this->_uniqueKey;
        $uniqueParams = $this->_uniqueParams;
        $uniqueTypes = $this->_uniqueTypes;

        /**
         * When unique params is null we need to rebuild the bind params
         */
        if (!is_array($uniqueParams)) {

            $primaryKeys = $metaData->getPrimaryKeyAttributes($this);

            /**
             * We can't create dynamic SQL without a primary key
             */
            if (!count($primaryKeys)) {
                throw new Exception("A primary key must be defined in the model in order to perform the operation");
            }

            $uniqueParams = [];
            foreach ($primaryKeys as $field) {

                /**
                 * Check if the model has a column map
                 */
                if (is_array($columnMap)) {
                    if (isset($columnMap[$field])) {
                        $attributeField = $columnMap[$field];
                    } else {
                        throw new Exception("Column '" . $field . "' isn't part of the column map");
                    }
                } else {
                    $attributeField = $field;
                }

                if (isset($this->{$attributeField})) {
                    $value = $this->{$attributeField};
                    $newSnapshot[$attributeField] = $value;
                    $uniqueParams[] = $value;
                } else {
                    $newSnapshot[$attributeField] = null;
                    $uniqueParams[] = null;
                }
            }
        }

        /**
         * We build the conditions as an array
         * Perform the low level update
         */
        $success = $connection->update($table, $fields, $values, [
            "conditions" => $uniqueKey,
            "bind" => $uniqueParams,
            "bindTypes" => $uniqueTypes
        ], $bindTypes);

        if ($success && $manager->isKeepingSnapshots($this) && Kernel::getGlobals("orm.update_snapshot_on_save")) {
            if (is_array($snapshot)) {
                $this->_oldSnapshot = $snapshot;
                $this->_snapshot = array_merge($snapshot, $newSnapshot);
            } else {
                $this->_oldSnapshot = [];
                $this->_snapshot = $newSnapshot;
            }
        }

        return $success;
    }

    /**
     * Get messages from model(没用了)
     *
     * @param object $model
     * @param object $target
     * @return boolean
     */
    private static function getMessagesFromModel($pointer, $model, $target)
    {
        try {
            $messages = $model->getMessages();

            if (is_array($messages) === false) {
                return false;
            }

            foreach ($messages as $message) {
                if (is_object($message) === true) {
                    $message->setModel($target);
                }

                $pointer->appendMessage($message);
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Saves related records that must be stored prior to save the master record
     *
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param \Phalcon\Mvc\ModelInterface[] $related
     * @return boolean
     * @throws Exception
     */
    protected function _preSaveRelatedRecords(AdapterInterface $connection, $related)
    {

        $nesting = false;

        /**
         * Start an implicit transaction
         */
        $connection->begin($nesting);

        $className = get_class($this);
        $manager = $this->getModelsManager();

        foreach ($related as $name => $record) {

            /**
             * Try to get a relation with the same name
             */
            $relation = $manager->getRelationByAlias($className, $name);
            if (is_object($relation)) {

                /**
                 * Get the relation type
                 */
                $type = $relation->getType();

                /**
                 * Only belongsTo are stored before save the master record
                 */
                if ($type == Relation::BELONGS_TO) {

                    if (!is_object($record)) {
                        $connection->rollback($nesting);
                        throw new Exception("Only objects can be stored as part of belongs-to relations");
                    }

                    $columns = $relation->getFields();
                    $referencedModel = $relation->getReferencedModel();
                    $referencedFields = $relation->getReferencedFields();

                    if (is_array($columns)) {
                        $connection->rollback($nesting);
                        throw new Exception("Not implemented");
                    }

                    /**
                     * If dynamic update is enabled, saving the record must not take any action
                     */
                    if (!$record->save()) {

                        /**
                         * Get the validation messages generated by the referenced model
                         */
                        foreach ($record->getMessages() as $message) {

                            /**
                             * Set the related model
                             */
                            if (is_object($message)) {
                                $message->setModel($record);
                            }

                            /**
                             * Appends the messages to the current model
                             */
                            $this->appendMessage($message);
                        }

                        /**
                         * Rollback the implicit transaction
                         */
                        $connection->rollback($nesting);
                        return false;
                    }

                    /**
                     * Read the attribute from the referenced model and assigns it to the current model
                     * Assign it to the model
                     */
                    $this->{$columns} = $record->readAttribute($referencedFields);
                }
            }
        }

        return true;
    }

    /**
     * Save the related records assigned in the has-one/has-many relations
     *
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param \Phalcon\Mvc\ModelInterface[] $related
     * @return boolean|null
     * @throws Exception
     */
    protected function _postSaveRelatedRecords(AdapterInterface $connection, $related)
    {

        $nesting = false;
        $className = get_class($this);
        $manager = $this->getModelsManager();

        foreach ($related as $name => $record) {
            //Try to get a relation with the same name
            $relation = $manager->getRelationByAlias($className, $name);
            if (is_object($relation) === true) {
                $type = $relation->getType();

                //Discard belongsTo relation
                if ($type === Relation::BELONGS_TO) {
                    continue;
                }

                if (is_object($record) === false && is_array($record) === false) {
                    $connection->rollback($nesting);
                    throw new Exception("Only objects/arrays can be stored as part of has-many/has-one/has-many-to-many relations");
                }

                $columns = $relation->getFields();
                $referencedModel = $relation->getReferencedModel();
                $referencedFields = $relation->getReferencedFields();

                if (is_array($columns) === true) {
                    $connection->rollback($nesting);
                    throw new Exception('Not implemented');
                }

                //Create an implicit array for has-many/has-one records
                if (is_object($record) === true) {
                    $relatedRecord = array($record);
                } else {
                    $relatedRecord = $record;
                }

                if (isset($this->$columns) === false) {
                    $value = $this->$columns;
                    $connection->rollback($nesting);
                    throw new Exception("The column '" . $columns . "' needs to be present in the model");
                } else {
                    $value = null;
                }

                //Get the value of the field from the current model


                //Check if the relation is has-many-to-amy
                $isThrough = (boolean)$relation->isThrough();

                //Get the rest of intermediate model info
                if ($isThrough) {
                    $intermediateModelName = $relation->getIntermediateModel();
                    $intermediateFields = $relation->getIntermediateFields();
                    $intermediateReferencedFields = $relation->getIntermediateReferencedFields();
                } else {
                    $intermediateModelName = null;
                    $intermediateFields = null;
                    $intermediateReferencedFields = null;
                }

                foreach ($relatedRecord as $recordAfter) {
                    //For non has-many-to-many relations just assign the local value in the referenced
                    //model
                    if ($isThrough === false) {
                        //Assign the value
                        $recordAfter->writeAttribute($referencedFields, $value);
                    }

                    //Save the record and get messages
                    if ($recordAfter->save() === false) {
                        //Get the validation messages generated by the referenced model
                        foreach ($recordAfter->getMessages() as $message) {

                            /**
                             * Set the related model
                             */
                            if (is_object($message)) {
                                $message->setModel($record);
                            }

                            /**
                             * Appends the messages to the current model
                             */
                            $this->appendMessage($message);
                        }

                        /**
                         * Rollback the implicit transaction
                         */
                        $connection->rollback($nesting);
                        return false;
                    }

                    if ($isThrough === true) {
                        /**
                         * Create a new instance of the intermediate model
                         */
                        $intermediateModel = $manager->load($intermediateModelName, true);

                        /**
                         * Write value in the intermediate model
                         */
                        $intermediateModel->writeAttribute($intermediateFields, $value);

                        /**
                         * Get the value from the referenced model
                         */
                        $intermediateValue = $recordAfter->readAttribute($referencedFields);

                        /**
                         * Write the intermediate value in the intermediate model
                         */
                        $intermediateModel->writeAttribute($intermediateReferencedFields, $intermediateValue);

                        /**
                         * Save the record and get messages
                         */
                        if (!$intermediateModel->save()) {

                            /**
                             * Get the validation messages generated by the referenced model
                             */
                            foreach ($intermediateModel->getMessages() as $message) {

                                /**
                                 * Set the related model
                                 */
                                if (is_object($message)) {
                                    $message->setModel($record);
                                }

                                /**
                                 * Appends the messages to the current model
                                 */
                                $this->appendMessage($message);
                            }

                            /**
                             * Rollback the implicit transaction
                             */
                            $connection->rollback($nesting);
                            return false;
                        }
                    }
                }
            } else {
                if (is_array($record) === false) {
                    $connection->rollback($nesting);
                    throw new Exception('There are no defined relations for the model "' . __CLASS__ . '" using alias "' . $name . '"');
                }
            }
        }

        //Commit the implicit transaction
        $connection->commit($nesting);
        return true;
    }

    /**
     * Inserts or updates a model instance. Returning true on success or false otherwise.
     *
     * <code>
     *  //Creating a new robot
     *  $robot = new Robots();
     *  $robot->type = 'mechanical';
     *  $robot->name = 'Astro Boy';
     *  $robot->year = 1952;
     *  $robot->save();
     *
     *  //Updating a robot name
     *  $robot = Robots::findFirst("id=100");
     *  $robot->name = "Biomass";
     *  $robot->save();
     * </code>
     *
     * @param array|null $data
     * @param array|null $whiteList
     * @return boolean
     * @throws Exception
     */
    public function save($data = null, $whiteList = null)
    {


        $metaData = $this->getModelsMetaData();

        if (is_array($data) && count($data) > 0) {
            $this->assign($data, null, $whiteList);
        }

        /**
         * Create/Get the current database connection
         */
        $writeConnection = $this->getWriteConnection();

        /**
         * Fire the start event
         */
        $this->fireEvent("prepareSave");

        /**
         * Save related records in belongsTo relationships
         */
        $related = $this->_related;
        if (is_array($related)) {
            if ($this->_preSaveRelatedRecords($writeConnection, $related) === false) {
                return false;
            }
        }

        $schema = $this->getSchema();
        $source = $this->getSource();

        if ($schema) {
            $table = [$schema, $source];
        } else {
            $table = $source;
        }

        /**
         * Create/Get the current database connection
         */
        $readConnection = $this->getReadConnection();

        /**
         * We need to check if the record exists
         */
        $exists = $this->_exists($metaData, $readConnection, $table);

        if ($exists) {
            $this->_operationMade = self::OP_UPDATE;
        } else {
            $this->_operationMade = self::OP_CREATE;
        }

        /**
         * Clean the messages
         */
        $this->_errorMessages = [];

        /**
         * Query the identity field
         */
        $identityField = $metaData->getIdentityField($this);

        /**
         * _preSave() makes all the validations
         */
        if ($this->_preSave($metaData, $exists, $identityField) === false) {

            /**
             * Rollback the current transaction if there was validation errors
             */
            if (is_array($related)) {
                $writeConnection->rollback(false);
            }

            /**
             * Throw exceptions on failed saves?
             */
            if (Kernel::getGlobals("orm.exception_on_failed_save")) {
                /**
                 * Launch a Phalcon\Mvc\Model\ValidationFailed to notify that the save failed
                 */
                throw new ValidationFailed($this, $this->getMessages());
            }

            return false;
        }

        /**
         * Depending if the record exists we do an update or an insert operation
         */
        if ($exists) {
            $success = $this->_doLowUpdate($metaData, $writeConnection, $table);
        } else {
            $success = $this->_doLowInsert($metaData, $writeConnection, $table, $identityField);
        }

        /**
         * Change the dirty state to persistent
         */
        if ($success) {
            $this->_dirtyState = self::DIRTY_STATE_PERSISTENT;
        }

        if (is_array($related)) {

            /**
             * Rollbacks the implicit transaction if the master save has failed
             */
            if ($success === false) {
                $writeConnection->rollback(false);
            } else {
                /**
                 * Save the post-related records
                 */
                $success = $this->_postSaveRelatedRecords($writeConnection, $related);
            }
        }

        /**
         * _postSave() invokes after* events if the operation was successful
         */
        if (Kernel::getGlobals("orm.events")) {
            $success = $this->_postSave($success, $exists);
        }

        if ($success === false) {
            $this->_cancelOperation();
        } else {
            $this->fireEvent("afterSave");
        }

        return $success;
    }

    /**
     * Inserts a model instance. If the instance already exists in the persistance it will throw an exception
     * Returning true on success or false otherwise.
     *
     * <code>
     *  //Creating a new robot
     *  $robot = new Robots();
     *  $robot->type = 'mechanical';
     *  $robot->name = 'Astro Boy';
     *  $robot->year = 1952;
     *  $robot->create();
     *
     *  //Passing an array to create
     *  $robot = new Robots();
     *  $robot->create(array(
     *      'type' => 'mechanical',
     *      'name' => 'Astroy Boy',
     *      'year' => 1952
     *  ));
     * </code>
     *
     * @param array|null $data
     * @param array|null $whiteList
     * @return boolean
     * @throws Exception
     */
    public function create($data = null, $whiteList = null)
    {

        $metaData = $this->getModelsMetaData();

        if ($this->_exists($metaData, $this->getReadConnection())) {
            $this->_errorMessages = [
                new Message("Record cannot be created because it already exists", null, "InvalidCreateAttempt")
            ];
            return false;
        }

        /**
         * Using save() anyways
         */
        return $this->save($data, $whiteList);
    }

    /**
     * Updates a model instance. If the instance doesn't exist in the persistance it will throw an exception
     * Returning true on success or false otherwise.
     *
     * <code>
     *  //Updating a robot name
     *  $robot = Robots::findFirst("id=100");
     *  $robot->name = "Biomass";
     *  $robot->update();
     * </code>
     *
     * @param array|null $data
     * @param array|null $whiteList
     * @return boolean
     * @throws Exception
     */
    public function update($data = null, $whiteList = null)
    {

        if ($this->_dirtyState) {

            $metaData = $this->getModelsMetaData();

            if (!$this->_exists($metaData, $this->getReadConnection())) {
                $this->_errorMessages = [
                    new Message(
                        "Record cannot be updated because it does not exist",
                        null,
                        "InvalidUpdateAttempt"
                    )
                ];

                return false;
            }
        }

        /**
         * Call save() anyways
         */
        return $this->save($data, $whiteList);
    }

    /**
     * Deletes a model instance. Returning true on success or false otherwise.
     *
     * <code>
     * $robot = Robots::findFirst("id=100");
     * $robot->delete();
     *
     * foreach (Robots::find("type = 'mechanical'") as $robot) {
     *   $robot->delete();
     * }
     * </code>
     *
     * @return boolean
     * @throws Exception
     */
    public function delete()
    {
        $metaData = $this->getModelsMetaData();
        $writeConnection = $this->getWriteConnection();
        $this->_errorMessages = array();

        //Operation made is OP_DELETE
        $this->_operationMade = 3;

        //Check if deleting the record violates a virtual foreign key
        if (Kernel::getGlobals("orm.virtual_foreign_keys") &&
            $this->_checkForeignKeysReverseRestrict() === false) {
            return false;
        }

        $values = array();
        $bindTypes = array();
        $conditions = array();
        $primaryKeys = $metaData->getPrimaryKeyAttributes($this);
        $bindDataTypes = $metaData->getBindTypes($this);

        if (Kernel::getGlobals("orm.column_renaming")) {
            $columnMap = $metaData->getColumnMap($this);
        } else {
            $columnMap = null;
        }

        //We can't create dynamic SQL without a primary key
        if (!count($primaryKeys)) {
            throw new Exception('A primary key must be defined in the model in order to perform the operation');
        }

        //Create a condition from the primary keys
        foreach ($primaryKeys as $primaryKey) {
            //Every column part of the primary key must be in the bind_data_types
            if (isset($bindDataTypes[$primaryKey]) === false) {
                throw new Exception("Column '" . $primaryKey . "' have not defined a bind data type");
            } else {
                $bindType = $bindDataTypes[$primaryKey];
            }

            //Take the column values based on the column map if any
            if (is_array($columnMap) === true) {
                if (isset($columnMap[$primaryKey]) === true) {
                    $attributeField = $columnMap[$primaryKey];
                } else {
                    throw new Exception("Column '" . $primaryKey . "' isn't part of the column map");
                }
            } else {
                $attributeField = $primaryKey;
            }

            //If the attribute is currently set in the object add it to the conditions
            if (isset($this->$attributeField) === false) {
                throw new Exception("Cannot delete the record because the priamry key attribute: '" . $attributeField . "' wasn't set");
            }

            $values[] = $this->$attributeField;
            //Escape the column identifier
            $conditions[] = $writeConnection->escapeIdentifier($primaryKey) . ' = ?';
            $bindTypes[] = $bindDataTypes[$primaryKey];
        }


        if (Kernel::getGlobals("orm.events")) {
            $this->_skipped = false;

            //Fire the beforeDelete event
            if ($this->fireEventCancel('beforeDelete') === false) {
                return false;
            } else {
                //The operation can be skipped
                if ($this->_skipped === true) {
                    return true;
                }
            }
        }

        $schema = $this->getSchema();
        $source = $this->getSource();

        if ($schema == true) {
            $table = array($schema, $source);
        } else {
            $table = $source;
        }

        //Do the deletion
        $success = $writeConnection->delete($table, join(" AND ", $conditions), $values, $bindTypes);

        //Check if there is a virtual foreign key with cascade action
        if (Kernel::getGlobals("orm.virtual_foreign_keys")) {
            if ($this->_checkForeignKeysReverseCascade() === false) {
                return false;
            }
        }

        if (Kernel::getGlobals("orm.events") &&
            $success === true) {
            $this->fireEvent('afterDelete');
        }

        //Force perform the record existence check again
        $this->_dirtyState = 2;

        return $success;
    }

    /**
     * Returns the type of the latest operation performed by the ORM
     * Returns one of the OP_* class constants
     *
     * @return int
     */
    public function getOperationMade()
    {
        return $this->_operationMade;
    }

    /**
     * Refreshes the model attributes re-querying the record from the database
     *
     * @throws Exception
     */
    public function refresh()
    {
        if ($this->_dirtyState != self::DIRTY_STATE_PERSISTENT) {
            throw new Exception('The record cannot be refreshed because it does not exist or is deleted');
        }

        $metaData = $this->getModelsMetaData();
        $readConnection = $this->getReadConnection();
        $schema = $this->getSchema();
        $source = $this->getSource();
        $manager = $this->_modelsManager;

        if ($schema == true) {
            $table = array($schema, $source);
        } else {
            $table = $source;
        }

        $uniqueKey = $this->_uniqueKey;
        if (!$uniqueKey) {
            //We need to check if the record exists
            $exists = $this->_exists($metaData, $readConnection, $table);
            if ($exists !== true) {
                throw new Exception('The record cannot be refreshed because it does not exist or is deleted');
            }

            $uniqueKey = $this->_uniqueKey;
        }

        $uniqueParams = $this->_uniqueParams;
        if (is_array($uniqueParams) === false) {
            throw new Exception('The record cannot be refreshed because it does not exist or is deleted');
        }

        $uniqueTypes = $this->_uniqueTypes;

        //We only refresh the attributes in the model's metadata
        $attributes = $metaData->getAttributes($this);
        $fields = array();

        foreach ($attributes as $attribute) {
            $fields[] = array($attribute);
        }

        //We directly build the SELECT to save resources
        $dialect = $readConnection->getDialect();
        $tables = $dialect->select([
            "columns" => $fields,
            "tables" => $readConnection->escapeIdentifier($table),
            "where" => $uniqueKey
        ]);
        $row = $readConnection->fetchOne($tables, \Phalcon\Db::FETCH_ASSOC, $uniqueParams, $this->_uniqueTypes);

        //Assign the resulting array to the $this object
        if (is_array($row)) {
            $columnMap = $metaData->getColumnMap($this);
            $this->assign($row, $columnMap);
            if ($manager->isKeepingSnapshots($this)) {
                $this->setSnapshotData($row, $columnMap);
            }
        }

        return $this;
    }

    /**
     * Skips the current operation forcing a success state
     *
     * @param boolean $skip
     * @throws Exception
     */
    public function skipOperation($skip)
    {
        if (is_bool($skip) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_skipped = $skip;
    }

    /**
     * Reads an attribute value by its name
     *
     * <code>
     * echo $robot->readAttribute('name');
     * </code>
     *
     * @param string $attribute
     * @return mixed
     * @throws Exception
     */
    public function readAttribute($attribute)
    {
        if (is_string($attribute) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!isset ($this->{$attribute})) {
            return null;
        }

        return $this->{$attribute};
    }

    /**
     * Writes an attribute value by its name
     *
     * <code>
     *  $robot->writeAttribute('name', 'Rosey');
     * </code>
     *
     * @param string $attribute
     * @param mixed $value
     * @throws Exception
     */
    public function writeAttribute($attribute, $value)
    {
        if (is_string($attribute) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->$attribute = $value;
    }

    /**
     * Sets a list of attributes that must be skipped from the
     * generated INSERT/UPDATE statement
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *       $this->skipAttributes(array('price'));
     *   }
     *
     * }
     * </code>
     *
     * @param array $attributes
     * @param boolean|null $replace
     * @throws Exception
     */
    protected function skipAttributes($attributes, $replace = null)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Attributes must be an array');
        }

        $this->skipAttributesOnCreate($attributes);
        $this->skipAttributesOnUpdate($attributes);
    }

    /**
     * Sets a list of attributes that must be skipped from the
     * generated INSERT statement
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *       $this->skipAttributesOnCreate(array('created_at'));
     *   }
     *
     * }
     * </code>
     *
     * @param array $attributes
     * @throws Exception
     */
    protected function skipAttributesOnCreate($attributes)
    {
        $keysAttributes = [];
        foreach ($attributes as $attribute) {
            $keysAttributes[$attribute] = null;
        }

        $this->getModelsMetaData()->setAutomaticCreateAttributes($this, $keysAttributes);
    }

    /**
     * Sets a list of attributes that must be skipped from the
     * generated UPDATE statement
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *       $this->skipAttributesOnUpdate(array('modified_in'));
     *   }
     *
     * }
     * </code>
     *
     * @param array $attributes
     * @throws Exception
     */
    protected function skipAttributesOnUpdate($attributes)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Attributes must be an array');
        }

        $keysAttributes = [];
        foreach ($attributes as $attribute) {
            $keysAttributes[$attribute] = null;
        }

        $this->getModelsMetaData()->setAutomaticUpdateAttributes($this, $keysAttributes);
    }

    /**
     * Sets a list of attributes that must be skipped from the
     * generated UPDATE statement
     *
     *<code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *     public function initialize()
     *     {
     *         $this->allowEmptyStringValues(
     *             [
     *                 "name",
     *             ]
     *         );
     *     }
     * }
     *</code>
     * @param $attributes
     * @throws Exception
     */
    protected function allowEmptyStringValues($attributes)
    {
        if (is_array($attributes) === false) {
            throw new Exception('Attributes must be an array');
        }

        $keysAttributes = [];
        foreach ($attributes as $attribute) {
            $keysAttributes[$attribute] = null;
        }

        $this->getModelsMetaData()->setEmptyStringAttributes($this, $keysAttributes);
    }


    /**
     * Setup a 1-1 relation between two models
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *       $this->hasOne('id', 'RobotsDescription', 'robots_id');
     *   }
     *
     * }
     * </code>
     *
     * @param mixed $fields
     * @param string $referenceModel
     * @param mixed $referencedFields
     * @param array $options
     * @throws Exception
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function hasOne($fields, $referenceModel, $referencedFields, $options)
    {
        if (is_string($referenceModel) === false) {
            throw new Exception('Attributes must be an array');
        }
        return $this->_modelsManager->addHasOne($this, $fields, $referenceModel, $referencedFields, $options);
    }

    /**
     * Setup a relation reverse 1-1  between two models
     *
     * <code>
     *
     * class RobotsParts extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *       $this->belongsTo('robots_id', 'Robots', 'id');
     *   }
     *
     * }
     * </code>
     *
     * @param mixed $fields
     * @param string $referenceModel
     * @param mixed $referencedFields
     * @param array|null $options
     * @throws Exception
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function belongsTo($fields, $referenceModel, $referencedFields, $options = null)
    {
        if (is_string($referenceModel) === false) {
            throw new Exception('Attributes must be an array');
        }
        return ($this->_modelsManager)->addBelongsTo(
            $this,
            $fields,
            $referenceModel,
            $referencedFields,
            $options
        );
    }

    /**
     * Setup a relation 1-n between two models
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *       $this->hasMany('id', 'RobotsParts', 'robots_id');
     *   }
     *
     * }
     * </code>
     *
     * @param mixed $fields
     * @param string $referenceModel
     * @param mixed $referencedFields
     * @param array|null $options
     * @throws Exception
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function hasMany($fields, $referenceModel, $referencedFields, $options = null)
    {
        if (is_string($referenceModel) === false) {
            throw new Exception('Attributes must be an array');
        }
        return ($this->_modelsManager)->addHasMany(
            $this,
            $fields,
            $referenceModel,
            $referencedFields,
            $options
        );
    }

    /**
     * Setup a relation n-n between two models through an intermediate relation
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *       //Setup a many-to-many relation to Parts through RobotsParts
     *       $this->hasManyToMany(
     *          'id',
     *          'RobotsParts',
     *          'robots_id',
     *          'parts_id',
     *          'Parts',
     *          'id'
     *      );
     *   }
     *
     * }
     * </code>
     *
     * @param string $fields
     * @param string $intermediateModel
     * @param string $intermediateFields
     * @param string $intermediateReferencedFields
     * @param string $referenceModel
     * @param string $referencedFields
     * @param array|null $options
     * @throws Exception
     * @return \Phalcon\Mvc\Model\Relation
     */
    public function hasManyToMany($fields, $intermediateModel, $intermediateFields, $intermediateReferencedFields, $referenceModel, $referencedFields, $options = null)
    {
        if (is_string($referenceModel) === false ||
            is_string($intermediateModel) === false) {
            throw new Exception('Attributes must be an array');
        }
        return ($this->_modelsManager)->addHasManyToMany(
            $this,
            $fields,
            $intermediateModel,
            $intermediateFields,
            $intermediateReferencedFields,
            $referenceModel,
            $referencedFields,
            $options
        );
    }

    /**
     * Setups a behavior in a model
     *
     * <code>
     *
     * use \Phalcon\Mvc\Model\Behavior\Timestampable;
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *      $this->addBehavior(new Timestampable(array(
     *          'onCreate' => array(
     *              'field' => 'created_at',
     *              'format' => 'Y-m-d'
     *          )
     *      )));
     *   }
     *
     * }
     * </code>
     *
     * @param \Phalcon\Mvc\Model\BehaviorInterface $behavior
     */
    public function addBehavior(BehaviorInterface $behavior)
    {
        $this->_modelsManager->addBehavior($this, $behavior);
    }

    /**
     * Sets if the model must keep the original record snapshot in memory
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *      $this->keepSnapshots(true);
     *   }
     *
     * }
     * </code>
     *
     * @param boolean $keepSnapshots
     */
    protected function keepSnapshots($keepSnapshots)
    {
        $keepSnapshots = (boolean)$keepSnapshots;
        $this->_modelsManager->keepSnapshots($this, $keepSnapshots);
    }

    /**
     * Sets the record's snapshot data.
     * This method is used internally to set snapshot data when the model was set up to keep snapshot data
     *
     * @param array $data
     * @param array|null $columnMap
     * @throws Exception
     */
    public function setSnapshotData($data, $columnMap = null)
    {
        if (is_array($data) === false) {
            throw new Exception('The snapshot data must be an array');
        }

        //Build the snapshot based on a column map
        if (is_array($columnMap) === true) {
            $snapshot = array();
            foreach ($data as $key => $value) {
                //Use only strings
                if (is_string($key) === false) {
                    continue;
                }

                //Every field must be part of the column map
                if (isset($columnMap[$key])) {
                    $attribute = $columnMap[$key];
                } else {
                    if (!Kernel::getGlobals("orm.ignore_unknown_columns")) {
                        throw new Exception("Column '" . $key . "' doesn't make part of the column map");
                    } else {
                        continue;
                    }
                }

                if (is_array($attribute)) {
                    if (isset($attribute[0])) {
                        $attribute = $attribute[0];
                    } else {
                        if (!Kernel::getGlobals("orm.ignore_unknown_columns")) {
                            throw new Exception("Column '" . $key . "' doesn't make part of the column map");
                        } else {
                            continue;
                        }
                    }
                }

                $snapshot[$attribute] = $value;
            }
        } else {
            $snapshot = $data;
        }

        $this->_oldSnapshot = $snapshot;
        $this->_snapshot = $snapshot;
    }

    /**
     * Checks if the object has internal snapshot data
     *
     * @return boolean
     */
    public function hasSnapshotData()
    {
        $snapshot = $this->_snapshot;

        return is_array($snapshot);
    }

    /**
     * Returns the internal snapshot data
     *
     * @return array|null
     */
    public function getSnapshotData()
    {
        return $this->_snapshot;
    }

    /**
     * Returns the internal old snapshot data
     *
     * @return array
     */
    public function getOldSnapshotData()
    {
        return $this->_oldSnapshot;
    }

    /**
     * Check if a specific attribute has changed
     * This only works if the model is keeping data snapshots
     *
     * @param string|null $fieldName
     * @return bool
     * @throws Exception
     */
    public function hasChanged($fieldName = null, $allFields = false)
    {
        $allFields = (boolean)$allFields;

        $changedFields = $this->getChangedFields();

        /**
         * If a field was specified we only check it
         */
        if (is_string($fieldName)) {
            return in_array($fieldName, $changedFields);
        } elseif (is_array($fieldName)) {
            if ($allFields) {
                return array_intersect($fieldName, $changedFields) == $fieldName;
            }

            return count(array_intersect($fieldName, $changedFields)) > 0;
        }

        return count($changedFields) > 0;
    }

    /**
     * Check if a specific attribute was updated
     * This only works if the model is keeping data snapshots
     *
     * @param string|array fieldName
     * @param bool $allFields
     * @return bool
     */
    public function hasUpdated($fieldName = null, $allFields = false)
    {

        $updatedFields = $this->getUpdatedFields();

        /**
         * If a field was specified we only check it
         */
        if (is_string($fieldName)) {
            return in_array($fieldName, $updatedFields);
        } elseif (is_array($fieldName)) {
            if ($allFields) {
                return array_intersect($fieldName, $updatedFields) == $fieldName;
            }

            return count(array_intersect($fieldName, $updatedFields)) > 0;
        }

        return count($updatedFields) > 0;
    }


    /**
     * Returns a list of changed values.
     *
     * <code>
     * $robots = Robots::findFirst();
     * print_r($robots->getChangedFields()); // []
     *
     * $robots->deleted = 'Y';
     *
     * $robots->getChangedFields();
     * print_r($robots->getChangedFields()); // ["deleted"]
     * </code>
     * @return array
     * @throws Exception
     */
    public function getChangedFields()
    {
        if (is_array($this->_snapshot) === false) {
            throw new Exception("The record doesn't have a valid data snapshot");
        }


        //return the models metadata
        $metaData = $this->getModelsMetaData();

        //The reversed column map is an array if the model has a column map
        $columnMap = $metaData->getReverseColumnMap($this);

        //Data types are field indexed
        if (is_array($columnMap) === false) {
            $allAttributes = $metaData->getDataTypes($this);
        } else {
            $allAttributes = $columnMap;
        }

        $changed = array();

        //Check every attribute in the model
        foreach ($allAttributes as $name => $type) {
            //If some attribute is not present in the snapshot, we assume the record as
            //changed
            if (isset($this->_snapshot[$name]) === false) {
                $changed[] = $name;
                continue;
            }

            //If some attribute is not present in the model, we assume the record as changed
            if (isset($this->$name) === false) {
                $changed[] = $name;
                continue;
            }

            if ($this->$name !== $this->_snapshot[$name]) {
                $changed[] = $name;
                continue;
            }
        }

        return $changed;
    }

    /**
     * Sets if a model must use dynamic update instead of the all-field update
     *
     * <code>
     *
     * class Robots extends \Phalcon\Mvc\Model
     * {
     *
     *   public function initialize()
     *   {
     *      $this->useDynamicUpdate(true);
     *   }
     *
     * }
     * </code>
     *
     * @param boolean $dynamicUpdate
     */
    protected function useDynamicUpdate($dynamicUpdate)
    {
        $this->_modelsManager->useDynamicUpdate($this, $dynamicUpdate);
    }

    /**
     * Returns related records based on defined relations
     *
     * @param string $alias
     * @param array|null $arguments
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public function getRelated($alias, $arguments = null)
    {
        if (is_string($alias) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $className = get_class($this);
        $manager = $this->_modelsManager;
        $relation = $manager->getRelationByAlias($className, $alias);
        if (is_object($relation)) {
            throw new Exception("There is no defined relations for the model '" . $className . "' using alias '" . $alias . "'");
        }

        /**
         * Call the 'getRelationRecords' in the models manager
         */
        return $manager->getRelationRecords($relation, null, $this, $arguments);
    }

    /**
     * Returns related records defined relations depending on the method name
     *
     * @param string $modelName
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    protected function _getRelatedRecords($modelName, $method, $arguments)
    {
        if (is_string($modelName) === false &&
            is_string($method) === false &&
            is_array($arguments) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $manager = $this->_modelsManager;
        $relation = false;
        $queryMethod = null;

        if (Text::startsWith($method, "count")) {
            $relation = $manager->getRelationByAlias($modelName, substr($method, 3));
        } /**
         * Calling count if the method starts with "count"
         */
        elseif (Text::startsWith($method, "count")) {
            $queryMethod = "count";
            $relation = $manager->getRelationByAlias($modelName, substr($method, 5));
        }

        /**
         * If the relation was found perform the query via the models manager
         */
        if (is_object($relation) === false) {
            return null;
        }

        $extraArgs = $arguments[0];

        return $manager->getRelationRecords(
            $relation,
            $queryMethod,
            $this,
            $extraArgs
        );
    }

    /**
     * Try to check if the query must invoke a finder
     *
     * @param $method
     * @param $arguments
     * @return null
     * @throws Exception
     */
    protected final static function _invokeFinder($method, $arguments)
    {
        $extraMethod = null;

        /**
         * Check if the method starts with "findFirst"
         */
        if (Text::startsWith($method, "findFirstBy")) {
            $type = "findFirst";
            $extraMethod = substr($method, 11);
        } /**
         * Check if the method starts with "find"
         */
        elseif (Text::startsWith($method, "findBy")) {
            $type = "find";
            $extraMethod = substr($method, 6);
        } /**
         * Check if the method starts with "count"
         */
        elseif (Text::startsWith($method, "countBy")) {
            $type = "count";
            $extraMethod = substr($method, 7);
        } else {
            $type = null;
        }

        /**
         * The called class is the model
         */
        $modelName = get_called_class();

        if (!$extraMethod) {
            return null;
        }

        if (isset($arguments[0])) {
            $value = $arguments[0];
        } else {
            throw new Exception("The static method '" . $method . "' requires one argument");
        }

        $model = new $modelName();
        $metaData = $model->getModelsMetaData();

        /**
         * Get the attributes
         */
        $attributes = $metaData->getReverseColumnMap($model);
        if (is_array($attributes) === false) {
            $attributes = $metaData->getDataTypes($model);
        }

        /**
         * Check if the extra-method is an attribute
         */
        if (isset ($attributes[$extraMethod])) {
            $field = $extraMethod;
        } else {

            /**
             * Lowercase the first letter of the extra-method
             */
            $extraMethodFirst = lcfirst($extraMethod);
            if (isset ($attributes[$extraMethodFirst])) {
                $field = $extraMethodFirst;
            } else {

                /**
                 * Get the possible real method name
                 */
                $field = Text::uncamelize($extraMethod);
                if (!isset ($attributes[$field])) {
                    throw new Exception("Cannot resolve attribute '" . $extraMethod . "' in the model");
                }
            }
        }

        /**
         * Execute the query
         */
        return $modelName::{$type}(["conditions" => "[" . $field . "] = ?0", "bind" => [$value]]);
    }

    /**
     * Handles method calls when a method is not implemented
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $arguments)
    {
        if (is_string($method) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $records = self::_invokeFinder($method, $arguments);
        if ($records !== null) {
            return $records;
        }

        $modelName = get_class($this);

        /**
         * Check if there is a default action using the magic getter
         */
        $records = $this->_getRelatedRecords($modelName, $method, $arguments);
        if ($records !== null) {
            return $records;
        }

        /**
         * Try to find a replacement for the missing method in a behavior/listener
         */
        $status = ($this->_modelsManager)->missingMethod($this, $method, $arguments);
        if ($status !== null) {
            return $status;
        }

        /**
         * The method doesn't exist throw an exception
         */
        throw new Exception("The method '" . $method . "' doesn't exist on model '" . $modelName . "'");
    }

    /**
     * Handles method calls when a static method is not implemented
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic($method, $arguments = null)
    {
        if (is_string($method) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $records = self::_invokeFinder($method, $arguments);
        if ($records === null) {
            throw new Exception("The static method '" . $method . "' doesn't exist");
        }

        return $records;
    }

    /**
     * Magic method to assign values to the the model
     *
     * @param $property
     * @param $value
     * @return mixed
     * @throws Exception
     */
    public function __set($property, $value)
    {
        if (is_string($property) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Values are probably relationships if they are objects
        if (is_object($value)) {
            if ($value instanceof ModelInterface) {
                $dirtyState = $this->_dirtyState;
                if ($value->getDirtyState() != $dirtyState) {
                    $dirtyState = self::DIRTY_STATE_TRANSIENT;
                }
                $lowerProperty = strtolower($property);
                $this->{$lowerProperty} = $value;
                $this->_related[$lowerProperty] = $value;
                $this->_dirtyState = $dirtyState;
                return $value;
            }
        }
        //Check if the value is an array
        if (is_array($value)) {

            $lowerProperty = strtolower($property);
            $modelName = get_class($this);
            $manager = $this->getModelsManager();

            $related = [];
            foreach ($value as $key => $item) {
                if (is_object($item)) {
                    if ($item instanceof ModelInterface) {
                        $related[] = $item;
                    }
                } else {
                    $lowerKey = strtolower($key);
                    $this->{$owerKey} = $item;
                    $relation = $manager->getRelationByAlias($modelName, $lowerProperty);
                    if (is_object($relation)) {
                        $referencedModel = $manager->load($relation->getReferencedModel());
                        $referencedModel->writeAttribute($lowerKey, $item);
                    }
                }
            }

            if (count($related) > 0) {
                $this->_related[$lowerProperty] = $related;
                $this->_dirtyState = self::DIRTY_STATE_TRANSIENT;
            }

            return $value;
        }

        if ($this->_possibleSetter($property, $value)) {
            return $value;
        }

        // Throw an exception if there is an attempt to set a non-public property.
        if (property_exists($this, $property)) {
            $manager = $this->getModelsManager();
            if (!$manager->isVisibleModelProperty($this, $property)) {
                throw new Exception("Property '" . $property . "' does not have a setter.");
            }
        }

        $this->{$property} = $value;

        return $value;
    }


    /**
     * Check for, and attempt to use, possible setter.
     *
     * @param string $property
     * @param mixed $value
     * @throws Exception
     * @return string
     */
    protected final function _possibleSetter($property, $value)
    {
        if (is_string($property) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $possibleSetter = "set" . Text::camelize($property);
        if (method_exists($this, $possibleSetter)) {
            $this->{$possibleSetter}($value);
            return true;
        }
        return false;
    }

    /**
     * Magic method to get related records using the relation alias as a property
     *
     * @param string $property
     * @return \Phalcon\Mvc\Model\Resultset
     * @throws Exception
     */
    public function __get($property)
    {
        if (is_string($property) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $modelName = get_class($this);
        $lowerProperty = strtolower($property);
        $manager = $this->getModelsManager();

        //Check if the property is a relationship
        $relation = $manager->getRelationByAlias($modelName, $lowerProperty);
        if (is_object($relation)) {

            /*
             Not fetch a relation if it is on CamelCase
             */
            if (isset ($this->{$lowerProperty}) && (is_object($this->{$lowerProperty}))) {
                return $this->{$lowerProperty};
            }
            /**
             * Get the related records
             */
            $result = $manager->getRelationRecords($relation, null, $this, null);

            /**
             * Assign the result to the object
             */
            if (is_object($result)) {

                /**
                 * We assign the result to the instance avoiding future queries
                 */
                $this->{$lowerProperty} = $result;

                /**
                 * For belongs-to relations we store the object in the related bag
                 */
                if ($result instanceof ModelInterface) {
                    $this->_related[$lowerProperty] = $result;
                }
            }

            return $result;
        }

        /**
         * Check if the property has getters
         */
        $method = "get" . Text::camelize($property);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        /**
         * A notice is shown if the property is not defined and it isn't a relationship
         */
        trigger_error("Access to undefined property " . $modelName . "::" . $property);
        return null;
    }

    /**
     * Magic method to check if a property is a valid relation
     *
     * @param string $property
     * @throws Exception
     * @return boolean
     */
    public function __isset($property)
    {
        if (is_string($property) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $modelName = get_class($this);
        $manager = $this->getModelsManager();

        /**
         * Check if the property is a relationship
         */
        $relation = $manager->getRelationByAlias($modelName, $property);
        return is_object($relation);
    }

    /**
     * Serializes the object ignoring connections, services, related objects or static properties
     *
     * @return string
     */
    public function serialize()
    {

        $attributes = $this->toArray();
        $manager = $this->getModelsManager();

        if ($manager->isKeepingSnapshots($this)) {
            $snapshot = $this->_snapshot;
            /**
             * If attributes is not the same as snapshot then save snapshot too
             */
            if ($snapshot != null && $attributes != $snapshot) {
                return serialize(["_attributes" => $attributes, "_snapshot" => $snapshot]);
            }
        }

        return serialize($attributes);
    }

    /**
     * Unserializes the object from a serialized string
     *
     * @param string $data
     * @throws Exception
     */
    public function unserialize($data)
    {

        if (is_string($data) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $attributes = unserialize($data);
        if (is_array($attributes)) {

            /**
             * Obtain the default DI
             */
            $dependencyInjector = Di::getDefault();
            if (!is_object($dependencyInjector)) {
                throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
            }

            /**
             * Update the dependency injector
             */
            $this->_dependencyInjector = $dependencyInjector;

            /**
             * Gets the default modelsManager service
             */
            $manager = $dependencyInjector->getShared("modelsManager");
            if (!is_object($manager)) {
                throw new Exception("The injected service 'modelsManager' is not valid");
            }

            /**
             * Update the models manager
             */
            $this->_modelsManager = $manager;

            /**
             * Try to initialize the model
             */
            $manager->initialize($this);
            if ($manager->isKeepingSnapshots($this)) {
                if (isset($attributes["_snapshot"])) {
                    $snapshot = $attributes["_snapshot"];
                    $this->_snapshot = $snapshot;
                    $attributes = $attributes["_attributes"];
                } else {
                    $this->_snapshot = $attributes;
                }
            }

            /**
             * Update the objects attributes
             */
            foreach ($attributes as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Returns a simple representation of the object that can be used with var_dump
     *
     * <code>
     * var_dump($robot->dump());
     * </code>
     *
     * @return array
     */
    public function dump()
    {
        return get_object_vars($this);
    }

    /**
     * Returns the instance as an array representation
     *
     * <code>
     * print_r($robot->toArray());
     * </code>
     *
     * @return array|null $columns
     * @throws Exception
     */
    public function toArray($columns = null)
    {
        $metaData = $this->getModelsMetaData();
        $data = array();

        //Original attributes
        $attributes = $metaData->getAttributes($this);

        //Reverse column map
        $columnMap = $metaData->getColumnMap($this);

        foreach ($attributes as $attribute) {
            //Check if the columns must be renamed
            if (is_array($columnMap)) {
                if (isset($columnMap[$attribute])) {
                    $attributeField = $columnMap[$attribute];
                } else {
                    if (!Kernel::getGlobals("orm.ignore_unknown_columns")) {
                        throw new Exception("Column '" . $attribute . "' doesn't make part of the column map");
                    } else {
                        continue;
                    }
                }
            } else {
                $attributeField = $attribute;
            }

            if (is_array($columns)) {
                if (!in_array($attributeField, $columns)) {
                    continue;
                }
            }

            if (isset($this->{$attributeField})) {
                $data[$attributeField] = $this->{$attributeField};
            } else {
                $data[$attributeField] = null;
            }
        }

        return $data;
    }

    /**
     * Serializes the object for json_encode
     *
     *<code>
     * echo json_encode($robot);
     *</code>
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Enables/disables options in the ORM
     * Available options:
     * events                — Enables/Disables globally the internal events
     * virtualForeignKeys    — Enables/Disables virtual foreign keys
     * columnRenaming        — Enables/Disables column renaming
     * notNullValidations    — Enables/Disables automatic not null validation
     * exceptionOnFailedSave — Enables/Disables throws an exception if the saving process fails
     * phqlLiterals          — Enables/Disables literals in PHQL this improves the security of applications
     *
     * @param array $options
     * @throws Exception
     */
    public static function setup($options)
    {
        if (is_array($options) === false) {
            throw new Exception('Options must be an array');
        }

        //Enable/Disable internal events
        if (isset($options["events"])) {
            Kernel::setGlobals("orm.events", $options["events"]);
        }

        /**
         * Enables/Disables virtual foreign keys
         */
        if (isset($options["virtualForeignKeys"])) {
            Kernel::setGlobals("orm.virtual_foreign_keys", $options["virtualForeignKeys"]);
        }

        /**
         * Enables/Disables column renaming
         */
        if (isset($options["columnRenaming"])) {
            Kernel::setGlobals("orm.column_renaming", $options["columnRenaming"]);
        }

        /**
         * Enables/Disables automatic not null validation
         */
        if (isset($options["notNullValidations"])) {
            Kernel::setGlobals("orm.not_null_validations", $options["notNullValidations"]);
        }

        /**
         * Enables/Disables throws an exception if the saving process fails
         */
        if (isset($options["exceptionOnFailedSave"])) {
            Kernel::setGlobals("orm.exception_on_failed_save", $options["exceptionOnFailedSave"]);
        }

        /**
         * Enables/Disables literals in PHQL this improves the security of applications
         */
        if (isset($options["phqlLiterals"])) {
            Kernel::setGlobals("orm.enable_literals", $options["phqlLiterals"]);
        }

        /**
         * Enables/Disables late state binding on model hydration
         */
        if (isset($options["lateStateBinding"])) {
            Kernel::setGlobals("orm.late_state_binding", $options["lateStateBinding"]);
        }

        /**
         * Enables/Disables automatic cast to original types on hydration
         */
        if (isset($options["castOnHydrate"])) {
            Kernel::setGlobals("orm.cast_on_hydrate", $options["castOnHydrate"]);
        }

        /**
         * Allows to ignore unknown columns when hydrating objects
         */
        if (isset($options["ignoreUnknownColumns"])) {
            Kernel::setGlobals("orm.ignore_unknown_columns", $options["ignoreUnknownColumns"]);
        }

        if (isset($options["updateSnapshotOnSave"])) {
            Kernel::setGlobals("orm.update_snapshot_on_save", $options["updateSnapshotOnSave"]);
        }

        if (isset($options["disableAssignSetters"])) {
            Kernel::setGlobals("orm.disable_assign_setters", $options["disableAssignSetters"]);
        }
    }

    /**
     * Reset a model instance data
     */
    public function reset()
    {
        $this->_uniqueParams = null;
        $this->_snapshot = null;
    }

}
