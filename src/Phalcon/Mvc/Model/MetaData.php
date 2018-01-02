<?php

namespace Phalcon\Mvc\Model;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\MetaData\Strategy\Introspection;
use Phalcon\Mvc\Model\MetaData\StrategyInterface;
use Phalcon\Kernel;

/**
 * Phalcon\Mvc\Model\MetaData
 *
 * <p>Because Phalcon\Mvc\Model requires meta-data like field names, data types, primary keys, etc.
 * this component collect them and store for further querying by Phalcon\Mvc\Model.
 * Phalcon\Mvc\Model\MetaData can also use adapters to store temporarily or permanently the meta-data.</p>
 *
 * <p>A standard Phalcon\Mvc\Model\MetaData can be used to query model attributes:</p>
 *
 * <code>
 * $metaData = new \Phalcon\Mvc\Model\MetaData\Memory();
 *
 * $attributes = $metaData->getAttributes(
 *     new Robots()
 * );
 *
 * print_r($attributes);
 * </code>
 */
abstract class MetaData implements InjectionAwareInterface, MetaDataInterface
{

    /**
     * Models: Attributes
     *
     * @var int
     */
    const MODELS_ATTRIBUTES = 0;

    /**
     * Models: Primary Key
     *
     * @var int
     */
    const MODELS_PRIMARY_KEY = 1;

    /**
     * Models: Non Primary Key
     *
     * @var int
     */
    const MODELS_NON_PRIMARY_KEY = 2;

    /**
     * Models: Not Null
     *
     * @var int
     */
    const MODELS_NOT_NULL = 3;

    /**
     * Models: Data Types
     *
     * @var int
     */
    const MODELS_DATA_TYPES = 4;

    /**
     * Models: Data Types Numeric
     *
     * @var int
     */
    const MODELS_DATA_TYPES_NUMERIC = 5;

    /**
     * Models: Date At
     *
     * @var int
     */
    const MODELS_DATE_AT = 6;

    /**
     * Models: Date In
     *
     * @var int
     */
    const MODELS_DATE_IN = 7;

    /**
     * Models: Identity Column
     *
     * @var int
     */
    const MODELS_IDENTITY_COLUMN = 8;

    /**
     * Models: Data Types Bind
     *
     * @var int
     */
    const MODELS_DATA_TYPES_BIND = 9;

    /**
     * Models: Automatic Default Insert
     *
     * @var int
     */
    const MODELS_AUTOMATIC_DEFAULT_INSERT = 10;

    /**
     * Models: AUtomatic Default Update
     *
     * @var int
     */
    const MODELS_AUTOMATIC_DEFAULT_UPDATE = 11;

    /**
     * Models: Default values
     *
     * @var int
     */
    const MODELS_DEFAULT_VALUES = 12;

    /**
     * Models: Empty string values
     *
     * @var int
     */
    const MODELS_EMPTY_STRING_VALUES = 13;

    /**
     * Models: Column Map
     *
     * @var int
     */
    const MODELS_COLUMN_MAP = 0;

    /**
     * Models: Reverse Column Map
     *
     * @var int
     */
    const MODELS_REVERSE_COLUMN_MAP = 1;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
     */
    protected $_dependencyInjector;

    /**
     * Strategy
     *
     * @var null|\Phalcon\Mvc\Model\MetaData\Strategy\Introspection
     * @access protected
     */
    protected $_strategy;

    /**
     * Metadata
     *
     * @var null|array
     * @access protected
     */
    protected $_metaData;

    /**
     * Column Map
     *
     * @var null|array
     * @access protected
     */
    protected $_columnMap;

    /**
     * Initialize the metadata for certain table
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param mixed $key
     * @param mixed $table
     * @param mixed $schema
     * @throws Exception
     */
    protected function _initialize(ModelInterface $model, $key, $table, $schema)
    {
        $strategy  = null;
        $className = get_class($model);

        if (is_null($key) === false) {
            //Check for $key in local metadata db
            $metaData = $this->_metaData;
            if (isset($metaData[$key]) === false) {
                //The meta-data is read from the adapter always
                $prefixKey = 'meta-' . $key;
                $data      = $this->read($prefixKey);

                if (is_null($data) === false) {
                    //Store the adapters metadata locally
                    if (is_array($metaData) === false) {
                        $metaData = array();
                    }

                    $metaData[$key]  = $data;
                    $this->_metaData = $metaData;
                } else {
                    //Check if there is a method 'metaData' in the model to retrieve meta-data form it
                    if (method_exists($model, 'metaData') === true) {
                        $modelMetadata = $model->metaData();
                        if (is_array($modelMetadata) === false) {
                            throw new Exception('Invalid meta-data for model ' . $className);
                        }
                    } else {
                        //Get the meta-data extraction strategy
                        $strategy      = $this->getStrategy();
                        //Get the meta-data
                        $modelMetadata = $strategy->getMetaData($model, $this->_dependencyInjector);
                    }

                    //Store the meta-data locally
                    $this->_metaData[$key] = $modelMetadata;
                    //Store the meta-data in the adapter
                    $this->write($prefixKey, $modelMetadata);
                }
            }
        }

        /**
         * Check for a column map, store in _columnMap in order and reversed order
         */
        if (!\Phalcon\Kernel::getGlobals("orm.column_renaming")) {
            return null;
        }

        $keyName = strtolower($className);
        if (isset($this->_columnMap[$keyName]) === true) {
            return null;
        }

        if (is_array($this->_columnMap) === false) {
            $this->_columnMap = array();
        }

        //Create the map key name
        $prefixKey = 'map-' . $keyName;
        //Check if the meta-data is already in the adapter
        $data      = $this->read($prefixKey);
        if (is_null($data) === false) {
            $this->_columnMap[$keyName] = $data;
            return null;
        }

        //Get the meta-data extraction strategy
        if (is_object($strategy) === false) {
            $strategy = $this->_dependencyInjector->getStrategy();
        }

        //Get the meta-data
        $modelColumnMap = $strategy->getColumnMaps($model, $this->_dependencyInjector);

        //Update the column map locally
        $this->_columnMap[$keyName] = $modelColumnMap;

        //Write the data to the adapter
        $this->write($prefixKey, $modelColumnMap);
    }

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
            throw new Exception('Dependency Injector is invalid');
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
     * Set the meta-data extraction strategy
     *
     * @param \Phalcon\Mvc\Model\MetaData\Strategy\Introspection $strategy
     * @throws Exception
     */
    public function setStrategy(StrategyInterface $strategy)
    {
        $this->_strategy = $strategy;
    }

    /**
     * Return the strategy to obtain the meta-data
     *
     * @return \Phalcon\Mvc\Model\MetaData\Strategy\Introspection
     */
    public function getStrategy()
    {
        if (is_null($this->_strategy) === true) {
            $this->_strategy = new Introspection();
        }

        return $this->_strategy;
    }

    /**
     * Reads the complete meta-data for certain model
     *
     * <code>
     *  print_r($metaData->readMetaData(new Robots()));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function readMetaData(ModelInterface $model)
    {
        $table  = $model->getSource();
        $schema = $model->getSchema();

        //Unique key for meta-data is created using class-name-schema-table
        $key = strtolower(get_class($model)) . '-' . $schema . $table;
        if (isset($this->_metaData[$key]) === false) {
            $this->_initialize($model, $key, $table, $schema);
        }

        return $this->_metaData[$key];
    }

    /**
     * Reads meta-data for certain model using a MODEL_* constant
     *
     * <code>
     *  print_r($metaData->writeColumnMapIndex(new Robots(), MetaData::MODELS_REVERSE_COLUMN_MAP, array('leName' => 'name')));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param int $index
     * @return array
     * @throws Exception
     */
    public function readMetaDataIndex(ModelInterface $model, $index)
    {
        if (is_int($index) === false) {
            throw new Exception('Index must be a valid integer constant');
        }

        $table  = $model->getSource();
        $schema = $model->getSchema();
        //Unique key for meta-data is created using class-name-schema-table
        $key    = strtolower(get_class($model)) . '-' . $schema . $table;
        if (isset($this->_metaData[$key]) === false) {
            $this->_initialize($model, $key, $table, $schema);
        }
        return $this->_metaData[$key][$index];
    }

    /**
     * Writes meta-data for certain model using a MODEL_* constant
     *
     * <code>
     * print_r(
     *     $metaData->writeColumnMapIndex(
     *         new Robots(),
     *         MetaData::MODELS_REVERSE_COLUMN_MAP,
     *         [
     *             "leName" => "name",
     *         ]
     *     )
     * );
     * </code>
     * 
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param int $index
     * @param array|string|boolean $data
     * @param boolean $replace
     * @throws Exception
     */
    public function writeMetaDataIndex(ModelInterface $model, $index, $data, $replace)
    {
        if (is_int($index) === false) {
            throw new Exception('Index must be a valid integer constant');
        }

        if (is_array($data) === false && is_string($data) === false &&
            is_bool($data) === false) {
            throw new Exception('Invalid data for index');
        }

        if (is_bool($replace) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $table  = $model->getSource();
        $schema = $model->getSchema();

        //Unique key for meta-data is created using class-name-schema-table
        $key = strtolower(get_class($model)) . '-' . $schema . $table;

        if (isset($this->_metaData[$key]) === false) {
            $this->_initialize($model, $key, $table, $schema);
        }

        $this->_metaData[$key][$index] = $data;
    }

    /**
     * Reads the ordered/reversed column map for certain model
     *
     * <code>
     * print_r(
     *     $metaData->readColumnMap(
     *         new Robots()
     *     )
     * );
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function readColumnMap(ModelInterface $model)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false) {
            throw new Exception('A model instance is required to retrieve the meta-data');
        }

        //Check for a column map, store in _columnMap in order and reversed order
        if (!Kernel::getGlobals("orm.column_renaming")) {
            return null;
        }

        $keyName = strtolower(get_class($model));

        if (isset($this->_columnMap[$keyName]) === false) {
            $this->_initialize($model, null, null, null);
        }

        return $this->_columnMap[$keyName];
    }

    /**
     * Reads column-map information for certain model using a MODEL_* constant
     *
     * <code>
     *  print_r($metaData->readColumnMapIndex(new Robots(), MetaData::MODELS_REVERSE_COLUMN_MAP));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param int $index
     * @throws Exception
     */
    public function readColumnMapIndex(ModelInterface $model, $index)
    {
        if (is_int($index) === false) {
            throw new Exception('Index must be a valid integer constant');
        }
        if (!Kernel::getGlobals("orm.column_renaming")) {
            return null;
        }

        $keyName = strtolower(get_class($model));

        if (isset($this->_columnMap[$keyName]) === false) {
            $this->_initialize($model, null, null, null);
        }

        $columnMapModel = $this->_columnMap[$keyName];

        $map = isset($columnMapModel[$index]) ? $columnMapModel[$index] : null;
        return $map;
    }

    /**
     * Returns table attributes names (fields)
     *
     * <code>
     *  print_r($metaData->getAttributes(new Robots()));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getAttributes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, 0);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns an array of fields which are part of the primary key
     *
     * <code>
     * print_r(
     *     $metaData->getPrimaryKeyAttributes(
     *         new Robots()
     *     )
     * );
     * </code>
     * 
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getPrimaryKeyAttributes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, 1);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns an array of fields which are not part of the primary key
     *
     * <code>
     * print_r(
     *     $metaData->getNonPrimaryKeyAttributes(
     *         new Robots()
     *     )
     * );
     * </code>
     * 
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getNonPrimaryKeyAttributes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_NON_PRIMARY_KEY);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns an array of not null attributes
     *
     * <code>
     * print_r(
     *     $metaData->getNotNullAttributes(
     *         new Robots()
     *     )
     * );
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getNotNullAttributes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_NOT_NULL);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns attributes and their data types
     *
     * <code>
     *  print_r($metaData->getDataTypes(new Robots()));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getDataTypes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_DATA_TYPES);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns attributes which types are numerical
     *
     * <code>
     *  print_r($metaData->getDataTypesNumeric(new Robots()));
     * </code>
     *
     * @param  \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getDataTypesNumeric(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_DATA_TYPES_NUMERIC);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns the name of identity field (if one is present)
     *
     * <code>
     *  print_r($metaData->getIdentityField(new Robots()));
     * </code>
     *
     * @param  \Phalcon\Mvc\ModelInterface $model
     * @return string
     * @throws Exception
     */
    public function getIdentityField(ModelInterface $model)
    {
        return $this->readMetaDataIndex($model, self::MODELS_IDENTITY_COLUMN);
    }

    /**
     * Returns attributes and their bind data types
     *
     * <code>
     *  print_r($metaData->getBindTypes(new Robots()));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getBindTypes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_DATA_TYPES_BIND);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns attributes that must be ignored from the INSERT SQL generation
     *
     * <code>
     *  print_r($metaData->getAutomaticCreateAttributes(new Robots()));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getAutomaticCreateAttributes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_INSERT);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns attributes that must be ignored from the UPDATE SQL generation
     *
     * <code>
     *  print_r($metaData->getAutomaticUpdateAttributes(new Robots()));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     * @throws Exception
     */
    public function getAutomaticUpdateAttributes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_UPDATE);
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Set the attributes that must be ignored from the INSERT SQL generation
     *
     * <code>
     *  $metaData->setAutomaticCreateAttributes(new Robots(), array('created_at' => true));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param array $attributes
     * @param boolean $replace
     */
    public function setAutomaticCreateAttributes(ModelInterface $model, array $attributes)
    {
        $this->writeMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_INSERT, $attributes);
    }

    /**
     * Set the attributes that must be ignored from the UPDATE SQL generation
     *
     * <code>
     *  $metaData->setAutomaticUpdateAttributes(new Robots(), array('modified_at' => true));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param array $attributes
     * @param boolean $replace
     */
    public function setAutomaticUpdateAttributes(ModelInterface $model, array $attributes)
    {
        $this->writeMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_UPDATE, $attributes);
    }

    /**
     * Set the attributes that allow empty string values
     *
     * <code>
     * $metaData->setEmptyStringAttributes(
     *     new Robots(),
     *     [
     *         "name" => true,
     *     ]
     * );
     * </code>
     */
    public function setEmptyStringAttributes(ModelInterface $model, array $attributes)
    {
        $this->writeMetaDataIndex($model, self::MODELS_EMPTY_STRING_VALUES, $attributes);
    }

    /**
     * Returns attributes allow empty strings
     *
     * <code>
     * print_r(
     *     $metaData->getEmptyStringAttributes(
     *         new Robots()
     *     )
     * );
     * </code>
     */
    public function getEmptyStringAttributes(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex($model, self::MODELS_EMPTY_STRING_VALUES);
        if (is_array($data) === false) {
            throw new Exception("The meta-data is invalid or is corrupt");
        }
        return $data;
    }

    /**
     * Returns the column map if any
     *
     * <code>
     *  print_r($metaData->getColumnMap(new Robots()));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     */
    public function getColumnMap(ModelInterface $model)
    {
        $data = $this->readColumnMapIndex($model, self::MODELS_COLUMN_MAP);
        if (is_null($data)) {
            $data = [];
        }
        if (is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }

        return $data;
    }

    /**
     * Returns attributes (which have default values) and their default values
     *
     * <code>
     * print_r(
     *     $metaData->getDefaultValues(
     *         new Robots()
     *     )
     * );
     * </code>
     */
    public function getDefaultValues(ModelInterface $model)
    {
        $data = $this->readMetaDataIndex(model, self::MODELS_DEFAULT_VALUES);
        if (is_array($data) === false) {
            throw new Exception("The meta-data is invalid or is corrupt");
        }
        return $data;
    }

    /**
     * Returns the reverse column map if any
     *
     * <code>
     * print_r(
     *     $metaData->getReverseColumnMap(
     *         new Robots()
     *     )
     * );
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return array
     */
    public function getReverseColumnMap(ModelInterface $model)
    {
        $data = $this->readColumnMapIndex($model, self::MODELS_REVERSE_COLUMN_MAP);
        if (is_null($data) === false && is_array($data) === false) {
            throw new Exception('The meta-data is invalid or is corrupted');
        }
        return $data;
    }

    /**
     * Check if a model has certain attribute
     *
     * <code>
     *  var_dump($metaData->hasAttribute(new Robots(), 'name'));
     * </code>
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $attribute
     * @return boolean
     * @throws Exception
     */
    public function hasAttribute(ModelInterface $model, $attribute)
    {
        if (is_string($attribute) === false) {
            throw new Exception('Attribute must be a string');
        }

        $columnMap = $this->getReverseColumnMap($model);
        if (is_array($columnMap) === true) {
            return isset($columnMap[$attribute]);
        } else {
            return isset($this->readMetaData($model)[self::MODELS_DATA_TYPES][$attribute]);
        }
    }

    /**
     * Checks if the internal meta-data container is empty
     *
     * <code>
     *  var_dump($metaData->isEmpty());
     * </code>
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return count($this->_metaData) == 0;
    }

    /**
     * Resets internal meta-data in order to regenerate it
     *
     * <code>
     *  $metaData->reset();
     * </code>
     */
    public function reset()
    {
        $this->_metaData  = array();
        $this->_columnMap = array();
    }

}
