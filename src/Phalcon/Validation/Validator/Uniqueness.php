<?php

namespace Phalcon\Validation\Validator;

use Phalcon\Validation\Validator;
use Phalcon\Validation;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\CollectionInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Collection;

/**
 * Phalcon\Validation\Validator\Uniqueness
 *
 * Check that a field is unique in the related table
 *
 * <code>
 * use Phalcon\Validation;
 * use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;
 *
 * $validator = new Validation();
 *
 * $validator->add(
 *     "username",
 *     new UniquenessValidator(
 *         [
 *             "model"   => new Users(),
 *             "message" => ":field must be unique",
 *         ]
 *     )
 * );
 * </code>
 *
 * Different attribute from the field:
 * <code>
 * $validator->add(
 *     "username",
 *     new UniquenessValidator(
 *         [
 *             "model"     => new Users(),
 *             "attribute" => "nick",
 *         ]
 *     )
 * );
 * </code>
 *
 * In model:
 * <code>
 * $validator->add(
 *     "username",
 *     new UniquenessValidator()
 * );
 * </code>
 *
 * Combination of fields in model:
 * <code>
 * $validator->add(
 *     [
 *         "firstName",
 *         "lastName",
 *     ],
 *     new UniquenessValidator()
 * );
 * </code>
 *
 * It is possible to convert values before validation. This is useful in
 * situations where values need to be converted to do the database lookup:
 *
 * <code>
 * $validator->add(
 *     "username",
 *     new UniquenessValidator(
 *         [
 *             "convert" => function (array $values) {
 *                 $values["username"] = strtolower($values["username"]);
 *
 *                 return $values;
 *             }
 *         ]
 *     )
 * );
 * </code>
 */
class Uniqueness extends Validator
{


    /**
     * @var null
     * @access private
     */
    private $columnMap = null;

    /**
     * Executes the validation
     * @param \Phalcon\Validation $validation
     * @param string $field
     * @return boolean
     * @throws Exception
     */
    public function validate($validation = null, $field = null)
    {
        if (is_object($validation) === false ||
            $validation instanceof Validation === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($field) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (!$this->isUniqueness($validation, $field)) {

            $label = $this->getOption("label");
            $message = $this->getOption("message");

            if (empty($label)) {
                $label = $validation->getLabel($field);
            }

            if (empty($message)) {
                $message = $validation->getDefaultMessage("Uniqueness");
            }

            $validation->appendMessage(
                new Message(strtr($message, array(":field" => $label)), $field, "Uniqueness", $this->getOption("code"))
            );
            return false;
        }
        return true;
    }

    /**
     * The column map is used in the case to get real column name
     * @param \Phalcon\Validation $validation
     * @param mixed $field
     * @return boolean
     * @throws Exception
     */

    protected function isUniqueness($validation, $field)
    {
        if (is_object($validation) === false ||
            $validation instanceof Validation === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($field)) {
            $singleField = $field;
            $field = array();

            $field[] = $singleField;
        }

        $values = array();
        $convert = $this->getOption("convert");

        foreach ($field as $singleField) {
            $values[$singleField] = $validation->getValue($singleField);
        }

        if ($convert != null) {
            $values = $convert($values);

            if (!is_array($values)) {
                throw new Exception("Value conversion must return an array");
            }
        }

        $record = $this->getOption("model");

        if (empty($record) || !is_object($record)) {
            // check validation getEntity() method
            $record = $validation->getEntity();
            if (empty($record)) {
                throw new Exception("Model of record must be set to property \"model\"");
            }
        }

        $isModel = $record instanceof ModelInterface;
        $isDocument = $record instanceof CollectionInterface;

        if ($isModel) {
            $params = $this->isUniquenessModel($record, $field, $values);
        } elseif ($isDocument) {
            $params = $this->isUniquenessCollection($record, $field, $values);
        } else {
            throw new Exception("The uniqueness validator works only with Phalcon\\Mvc\\Model or Phalcon\\Mvc\\Collection");
        }

        $className = get_class($record);

        return $className::count($params) == 0;
    }


    /**
     * The column map is used in the case to get real column name
     * @param mixed $record
     * @param string $field
     * @return string
     * @throws Exception
     */
    protected function getColumnNameReal($record, $field)
    {
        if (!is_string($field)) {
            throw new Exception('Invalid parameter type.');
        }
        // Caching columnMap
        if ($GLOBALS['orm.column_renaming'] && !$this->columnMap) {
            $this->columnMap = $record->getDI()
                ->getShared("modelsMetadata")
                ->getColumnMap($record);
        }

        if (is_array($this->columnMap) && isset($this->columnMap[$field])) {
            return $this->columnMap[$field];
        }

        return $field;
    }

    /**
     * Uniqueness method used for model
     * @param mixed $record
     * @param array $field
     * @param array $values
     * @throws Exception
     * @return array
     */
    protected function isUniquenessModel($record, array $field, array $values)
    {
        if (!is_array($field) || !is_array($values)) {
            throw new Exception('Invalid parameter type.');
        }
        $exceptConditions = array();
        $index = 0;
        $params = array(
            "conditions" => array(),
            "bind" => array()
        );
        $except = $this->getOption("except");

        foreach ($field as $singleField) {
            $fieldExcept = null;
            $notInValues = array();
            $value = $values[$singleField];
            $attribute = $this->getOption("attribute", $singleField);
            $attribute = $this->getColumnNameReal($record, $attribute);

            if ($value != null) {
                $params["conditions"][] = $attribute . " = ?" . $index;
                $params["bind"][] = $value;
                $index++;
            } else {
                $params["conditions"][] = $attribute . " IS NULL";
            }

            if ($except) {
                if (is_array($except) && array_keys($except) !== range(0, count($except) - 1)) {
                    foreach ($except as $singleField => $fieldExcept) {
                        $attribute = $this->getColumnNameReal($record, $this->getOption("attribute", $singleField));
                        if (is_array($fieldExcept)) {
                            foreach ($fieldExcept as $singleExcept) {
                                $notInValues[] = "?" . $index;
                                $params["bind"][] = $singleExcept;
                                $index++;
                            }
                            $exceptConditions[] = $attribute . " NOT IN (" . join(",", $notInValues) . ")";
                        } else {
                            $exceptConditions[] = $attribute . " <> ?" . $index;
                            $params["bind"][] = $fieldExcept;
                            $index++;
                        }
                    }
                } elseif (count($field) == 1) {
                    $attribute = $this->getColumnNameReal($record, $this->getOption("attribute", $field[0]));
                    if (is_array($except)) {
                        foreach ($except as $singleExcept) {
                            $notInValues[] = "?" . $index;
                            $params["bind"][] = $singleExcept;
                            $index++;
                        }
                        $exceptConditions[] = $attribute . " NOT IN (" . join(",", $notInValues) . ")";
                    } else {
                        $params["conditions"][] = $attribute . " <> ?" . $index;
                        $params["bind"][] = $except;
                        $index++;
                    }
                } elseif (count($field) > 1) {
                    foreach ($field as $singleFields) {
                        $attribute = $this->getColumnNameReal($record, $this->getOption("attribute", $singleFields));
                        if (is_array($except)) {
                            foreach ($except as $singleExcept) {
                                $notInValues[] = "?" . $index;
                                $params["bind"][] = $singleExcept;
                                $index++;
                            }
                            $exceptConditions[] = $attribute . " NOT IN (" . join(",", $notInValues) . ")";
                        } else {
                            $params["conditions"][] = $attribute . " <> ?" . $index;
                            $params["bind"][] = $except;
                            $index++;
                        }
                    }
                }
            }
        }

        /**
         * If the operation is update, there must be values in the object
         */
        if ($record->getDirtyState() == Model::DIRTY_STATE_PERSISTENT) {
            $metaData = $record->getDI()->getShared("modelsMetadata");
            foreach ($metaData->getPrimaryKeyAttributes($record) as $primaryField) {
                $params["conditions"][] = $this->getColumnNameReal($record, $primaryField) . " <> ?" . $index;
                $params["bind"][] = $record->readAttribute($primaryField);
                $index++;
            }
        }

        if (!empty ($exceptConditions)) {
            $params["conditions"][] = "(" . join(" OR ", $exceptConditions) . ")";
        }

        $params["conditions"] = join(" AND ", $params["conditions"]);

        return $params;
    }

    /**
     * Uniqueness method used for collection
     * @param array $field
     * @param array $values
     * @param mixed $record
     * @throws Exception
     * @return array
     */
    protected function isUniquenessCollection($record, $field, $values)
    {
        if (!is_array($field) || !is_array($values)) {
            throw new Exception('Invalid parameter type.');
        }

        $exceptConditions = array();
        $params = array("conditions" => array());

        foreach ($field as $singleField) {
            $fieldExcept = null;
            $notInValues = array();
            $value = $values[$singleField];

            $except = $this->getOption("except");

            if ($value != null) {
                $params["conditions"][$singleField] = $value;
            } else {
                $params["conditions"][$singleField] = null;
            }

            if ($except) {
                if (is_array($except) && count($field) > 1) {
                    if (isset($except[$singleField])) {
                        $fieldExcept = $except[$singleField];
                    }
                }

                if ($fieldExcept != null) {
                    if (is_array($fieldExcept)) {
                        foreach ($fieldExcept as $singleExcept) {
                            $notInValues[] = $singleExcept;
                        }
                        $arrayValue = array('$nin' => $notInValues);
                        $exceptConditions[$singleField] = $arrayValue;
                    } else {
                        $arrayValue = array('$ne' => $fieldExcept);
                        $exceptConditions[$singleField] = $arrayValue;
                    }
                } elseif ((is_array($except)) && count($field) == 1) {
                    foreach ($except as $singleExcept) {
                        $notInValues[] = $singleExcept;
                    }
                    $arrayValue = array('$nin' => $notInValues);
                    $params["conditions"][$singleField] = $arrayValue;
                } elseif (count($field) == 1) {
                    $arrayValue = array('$ne' => $except);
                    $params["conditions"][$singleField] = $arrayValue;
                }
            }
        }

        if ($record->getDirtyState() == Collection::DIRTY_STATE_PERSISTENT) {
            $arrayValue = array('$ne' => $record->getId());
            $params["conditions"]["_id"] = $arrayValue;
        }

        if (!empty($exceptConditions)) {
            $params["conditions"]['$or'] = [$exceptConditions];
        }

        return $params;
    }

}
