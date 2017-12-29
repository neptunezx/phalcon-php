<?php

namespace Phalcon\Mvc\Model\MetaData\Strategy;

use Phalcon\DiInterface;
use Phalcon\Db\Column;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaData\StrategyInterface;

/**
 * Phalcon\Mvc\Model\MetaData\Strategy\Introspection
 *
 * Queries the table meta-data in order to introspect the model's metadata
 */
class Introspection implements StrategyInterface
{

    /**
     * The meta-data is obtained by reading the column descriptions from the database information schema
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param \Phalcon\DiInterface $dependencyInjector
     * @return array
     * @throws Exception
     */
    public function getMetaData(ModelInterface $model, DiInterface $dependencyInjector)
    {
        $className      = get_class($model);
        $schema         = $model->getSchema();
        $table          = $model->getSource();
        $readConnection = $model->getReadConnection();

        //Check if the mapped table exists on the database
        if (!$readConnection->tableExists($table, $schema)) {
            //The table does not exist
            throw new Exception('Table "' . ($schema == true ? $schema . '"."' . $table : $table) . '" doesn\'t exist on database when dumping meta-data for ' . $className);
        }

        //Try to describe the table
        $columns = $readConnection->describeColumns($table, $schema);
        if (count($columns) == 0) {
            throw new Exception('Cannot obtain table columns for the mapped source "' . ($schema == true ? $schema . '"."' . $table : $table) . '" used in model "' . $className);
        }

        //Initialize meta-data
        $attributes        = [];
        $primaryKeys       = [];
        $nonPrimaryKeys    = [];
        $numericTyped      = [];
        $notNull           = [];
        $fieldTypes        = [];
        $fieldBindTypes    = [];
        $automaticDefault  = [];
        $identityField     = false;
        $defaultValues     = [];
        $emptyStringValues = [];

        foreach ($columns as $column) {
            $fieldName    = $column->getName();
            $attributes[] = $fieldName;

            //Mark fields as priamry keys
            if ($column->isPrimary() === true) {
                $primaryKeys[] = $fieldName;
            } else {
                $nonPrimaryKeys[] = $fieldName;
            }

            //Mark fields as numeric
            if ($column->isNumeric() === true) {
                $numericTyped[$fieldName] = true;
            }

            //Mark fields as not null
            if ($column->isNotNull() === true) {
                $notNull[] = $fieldName;
            }

            //Mark fields as identity columns
            if ($column->isAutoIncrement() === true) {
                $identityField = $fieldName;
            }

            //Get the internal types
            $fieldTypes[$fieldName] = $column->getType();

            //Mark how fields must be escaped
            $fieldBindTypes[$fieldName] = $column->getBindType();
            /**
             * If column has default value or column is nullable and default value is null
             */
            $defaultValue               = $column->getDefault();
            if ($defaultValue !== null || $column->isNotNull() === false) {
                if (!$column->isAutoIncrement()) {
                    $defaultValues[$fieldName] = $defaultValue;
                }
            }
        }

        /**
         * Create an array using the MODELS_* constants as indexes
         */
        return [
            MetaData::MODELS_ATTRIBUTES               => $attributes,
            MetaData::MODELS_PRIMARY_KEY              => $primaryKeys,
            MetaData::MODELS_NON_PRIMARY_KEY          => $nonPrimaryKeys,
            MetaData::MODELS_NOT_NULL                 => $notNull,
            MetaData::MODELS_DATA_TYPES               => $fieldTypes,
            MetaData::MODELS_DATA_TYPES_NUMERIC       => $numericTyped,
            MetaData::MODELS_IDENTITY_COLUMN          => $identityField,
            MetaData::MODELS_DATA_TYPES_BIND          => $fieldBindTypes,
            MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => $automaticDefault,
            MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => $automaticDefault,
            MetaData::MODELS_DEFAULT_VALUES           => $defaultValues,
            MetaData::MODELS_EMPTY_STRING_VALUES      => $emptyStringValues
        ];
    }

    /**
     * Read the model's column map, this can't be infered
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param \Phalcon\DiInterface $dependencyInjector
     * @return array
     * @throws Exception
     */
    public function getColumnMaps(ModelInterface $model, \Phalcon\DiInterface $dependencyInjector)
    {
        if (is_object($model) === false ||
            $model instanceof ModelInterface === false ||
            is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $orderedColumnMap  = null;
        $reversedColumnMap = null;

        //Check for a columnMap() method on the model
        if (method_exists($model, 'columnMap') === true) {
            $userColumnMap = $model->columnMap();
            if (is_array($userColumnMap) === false) {
                throw new Exception('columnMap() not returned an array');
            }

            $reversedColumnMap = [];
            $orderedColumnMap  = $userColumnMap;

            foreach ($userColumnMap as $name => $userName) {
                $reversedColumnMap[$userName] = $name;
            }
        }

        //Store the column map
        return [$orderedColumnMap, $reversedColumnMap];
    }

}
