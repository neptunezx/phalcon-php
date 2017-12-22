<?php

namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;


/**
 * Phalcon\Mvc\Model\Validator\Uniqueness
 *
 * Validates that a field or a combination of a set of fields are not
 * present more than once in the existing records of the related table
 *
 * This validator is only for use with Phalcon\Mvc\Collection. If you are using
 * Phalcon\Mvc\Model, please use the validators provided by Phalcon\Validation.
 *
 *<code>
 * use Phalcon\Mvc\Collection;
 * use Phalcon\Mvc\Model\Validator\Uniqueness;
 *
 * class Subscriptors extends Collection
 * {
 *     public function validation()
 *     {
 *         $this->validate(
 *             new Uniqueness(
 *                 [
 *                     "field"   => "email",
 *                     "message" => "Value of field 'email' is already present in another record",
 *                 ]
 *             )
 *         );
 *
 *         if ($this->validationHasFailed() === true) {
 *             return false;
 *         }
 *     }
 * }
 *</code>
 *
 * @deprecated 3.1.0
 * @see Phalcon\Validation\Validator\Uniqueness
 */
class Uniqueness extends Validator
{

    /**
     * Executes the validator
     *
     * @param \Phalcon\Mvc\EntityInterface $record
     * @return boolean
     * @throws Exception
     */
    public function validate($record)
    {
        if (is_object($record) === false ||
            $record instanceof EntityInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $record->getDi();
        $metaData = $dependencyInjector->getShared('modelsMetadata');

        /**
         * PostgreSQL check if the compared constant has the same type as the
         * column, so we make cast to the data passed to match those column types
         */
        $bindTypes = array();
        $bindDataTypes = $metaData->getBindTypes($record);
        if (isset($GLOBALS['orm.column_renaming']) === true) {
            $columnMap = $metaData->getReverseColumnMap($record);
        } else {
            $columnMap = array();
        }

        $conditions = array();
        $bindParams = array();
        $number = 0;
        $field = $this->getOption('field');

        if (is_array($field) === true) {
            /**
             * The field can be an array of values
             */
            foreach ($field as $composeField) {
                /**
                 * The reversed column map is used in the case to get real column name
                 */
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$composeField]) === true) {
                        $columnField = $columnMap[$composeField];
                    } else {
                        throw new Exception("Column '" . $composeField . '" isn\'t part of the column map');
                    }
                } else {
                    $columnField = $composeField;
                }

                /**
                 * Some database systems require that we pass the values using bind casting
                 */
                if (isset($bindDataTypes[$columnField]) === false) {
                    throw new Exception("Column '" . $columnField . '" isn\'t part of the table columns');
                }

                /**
                 * The attribute could be "protected" so we read using "readattribute"
                 */
                $conditions[] = '[' . $composeField . '] = ?' . $number;
                $bindParams[] = $record->readattribute($composeField);
                $bindTypes[] = $bindDataTypes[$columnField];
                $number++;
            }
        } else {
            /**
             * The reversed column map is used in the case to get real column name
             */
            if (is_array($columnMap) === true) {
                if (isset($columnMap[$field]) === true) {
                    $columnField = $columnMap[$field];
                } else {
                    throw new Exception("Column '" . $field . '" isn\'t part of the column map');
                }
            } else {
                $columnField = $field;
            }

            /**
             * Some database systems require that we pass the values using bind casting
             */
            if (isset($bindDataTypes[$columnField]) === false) {
                throw new Exception("Column '" . $columnField . '" isn\'t part of the table columns');
            }

            /**
             * We're checking the uniqueness with only one field
             */
            $conditions[] = '[' . $field . '] = ?0';
            $bindParams[] = $record->readAttribute($field);
            $bindTypes[] = $bindDataTypes[$columnField];
            $number++;
        }

        /**
         * If the operation is update, there must be values in the object
         */
        if ($record->getOperationMade() === 2) {
            /**
             * We build a query with the primary key attributes
             */
            if (isset($GLOBALS['orm.column_renaming']) === true) {
                $columnMap = $metaData->getColumnMap($record);
            } else {
                $columnMap = null;
            }

            $primaryFields = $metaData->getPrimaryKeyAttributes($record);
            foreach ($primaryFields as $primaryField) {
                if (isset($bindDataTypes[$primaryField]) === false) {
                    throw new Exception("Column '" . $primaryField . '" isn\'t part of the table columns');
                }

                /**
                 * Rename the column if there is a column map
                 */
                if (is_array($columnMap) === true) {
                    if (isset($columnMap[$primaryField]) === true) {
                        $attributeField = $columnMap[$primaryField];
                    } else {
                        throw new Exception("Column '" . $primaryField . '" isn\'t part of the column map');
                    }
                } else {
                    $attributeField = $primaryField;
                }

                /**
                 * Create a condition based on the renamed primary key
                 */

                $conditions[] = '[' . $attributeField . '] <> ?' . $number;
                $bindParams[] = $record->readAttribute($primaryField);
                $bindTypes[] = $bindDataTypes[$primaryField];

                $number++;
            }
        }

        $joinConditions = implode(' AND ', $conditions);

        /**
         * We don't trust the user, so we pass the parameters as bound parameters
         */
        $params = array('di' => $dependencyInjector, 'conditions' => $joinConditions, 'bind' => $bindParams, 'bindTypes' => $bindTypes);
        $className = get_class($record);

        /**
         * Check if the record does exist using a standard count
         */
        $number = $className::count($params);
        if ($number !== 0) {
            /**
             * Check if the developer has defined a custom message
             */
            $message = $this->getOption('message');
            if (is_array($field)) {
                $replacePairs = array(":fields" => join(", ", $field));
                if (empty($message)) {
                    $message = "Value of fields: :fields are already present in another record";
                }
            } else {
                $replacePairs = array(":field" => $field);
                if (empty($message)) {
                    $message = "Value of field: ':field' is already present in another record";
                }
            }

            /**
             * Append the message to the validator
             */
            $this->appendMessage(strtr($message, $replacePairs), $field, "Unique");
            return false;
        }

        return true;
    }

}
