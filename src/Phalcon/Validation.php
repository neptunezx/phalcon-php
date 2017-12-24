<?php
/**
 * Created by PhpStorm.
 * User: flyfish
 * Date: 2017/12/22
 * Time: 11:06
 */

namespace Phalcon;

use Phalcon\Di\Injectable;
use Phalcon\ValidationInterface;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message\Group;
use Phalcon\Validation\MessageInterface;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\CombinedFieldsValidator;

/**
 * Phalcon\ValidationInterface
 *
 * Interface for the Phalcon\Validation component
 */
class Validation extends Injectable implements ValidationInterface
{

    /**
     * Data
     * @access protected
     */
    protected $_data;

    /**
     * Entity
     * @access protected
     */
    protected $_entity;

    /**
     * Validators
     * @var array
     * @access protected
     */
    protected $_validators = array();

    /**
     * CombinedFieldsValidators
     * @var array
     * @access protected
     */

    protected $_combinedFieldsValidators = array();

    /**
     * Filters
     * @var array
     * @access protected
     */


    protected $_filters = array();


    /**
     * Message
     * @access protected
     */

    protected $_messages;

    /**
     * DefaultMessages
     * @access protected
     */

    protected $_defaultMessages;

    /**
     * Labels
     * @var array
     * @access protected
     */

    protected $_labels = array();

    /**
     * Values
     * @var array
     * @access protected
     */

    protected $_values;

    /**
     * Phalcon\Validation constructor
     *
     * @param array $validators
     * @throws Exception
     */

    public function __construct($validators)
    {
        if(!is_array($validators)){
            throw new Exception('Invalid parameter type.');
        }
        if (count($validators)) {
            $this->_validators = array_filter($validators, function ($element) {
                return is_array($element[0]) || !($element[1] instanceof CombinedFieldsValidator);
            });
            $this->_combinedFieldsValidators = array_filter($validators, function ($element) {
                return is_array($element[0]) && $element[1] instanceof CombinedFieldsValidator;
            });
        }

        $this->setDefaultMessages();

        /**
         * Check for an 'initialize' method
         */
        if (method_exists($this, "initialize")) {
            $this->{"initialize"}();
        }
    }


    /**
     * Validate a set of data according to a set of rules
     *
     * @param array|object|null $data
     * @param object|null $entity
     * @return \Phalcon\Validation\Message\Group|bool
     * @throws \Phalcon\Validation\Exception
     */

    public function validate($data = null, $entity = null)
    {

        $validators = $this->_validators;
        $combinedFieldsValidators = $this->_combinedFieldsValidators;

        if (is_array($validators)) {
            throw new Exception("There are no validators to validate");
        }

        /**
         * Clear pre-calculated values
         */
        $this->_values = null;

        /**
         * Implicitly creates a Phalcon\Validation\Message\Group object
         */
        $messages = new Group();

        if ($entity !== null) {
            $this->setEntity($entity);
        }

        /**
         * Validation classes can implement the 'beforeValidation' callback
         */
        if (method_exists($this, "beforeValidation")) {
            $status = $this->{"beforeValidation"}($data, $entity, $messages);
            if ($status === false) {
                return $status;
            }
        }

        $this->_messages = $messages;

        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $this->_data = $data;
            } else {
                throw new Exception("Invalid data to validate");
            }
        }

        foreach ($validators as $scope) {

            if (is_array($scope)) {
                throw new Exception("The validator scope is not valid");
            }
            $field = $scope[0];
            $validator = $scope[1];

            if (is_object($validator)) {
                throw new Exception("One of the validators is not valid");
            }

            /**
             * Call internal validations, if it returns true, then skip the current validator
             */
            if ($this->preChecking($field, $validator)) {
                continue;
            }

            /**
             * Check if the validation must be canceled if this validator fails
             */
            if ($validator->validate($this, $field) === false) {
                if ($validator->getOption("cancelOnFail")) {
                    break;
                }
            }
        }

        foreach ($combinedFieldsValidators as $scope) {
            if (is_array($scope)) {
                throw new Exception("The validator scope is not valid");
            }

            $field = $scope[0];
            $validator = $scope[1];

            if (is_object($validator)) {
                throw new Exception("One of the validators is not valid");
            }

            /**
             * Call internal validations, if it returns true, then skip the current validator
             */
            if ($this->preChecking($field, $validator)) {
                continue;
            }

            /**
             * Check if the validation must be canceled if this validator fails
             */
            if ($validator->validate($this, $field) === false) {
                if ($validator->getOption("cancelOnFail")) {
                    break;
                }
            }
        }

        /**
         * Get the messages generated by the validators
         */
        if (method_exists($this, "afterValidation")) {
            $this->{"afterValidation"}($data, $entity, $this->_messages);
        }

        return $this->_messages;

    }

    /**
     * Adds a validator to a field
     *
     * @param string field
     * @param \Phalcon\Validation\ValidatorInterface
     * @return \Phalcon\Validation
     * @throws ValidationException
     */
    public function add($field, $validator)
    {
        if (is_array($field)) {
            // Uniqueness validator for combination of fields is handled differently
            if ($validator instanceof CombinedFieldsValidator) {
                array_push($this->_combinedFieldsValidators, $field);
                array_push($this->_combinedFieldsValidators, $validator);
            } else {
                foreach ($field as $singleField) {
                    array_push($this->_validators, $singleField);
                    array_push($this->_validators, $validator);
                }
            }
        } elseif (is_string($field)) {
            array_push($this->_validators, $field);
            array_push($this->_validators, $validator);
        } else {
            throw new Exception("Field must be passed as array of fields or string");
        }
        return $this;

    }

    /**
     * Adds the validators to a field
     * @param string $field
     * @param \Phalcon\Validation\ValidatorInterface
     * @return \Phalcon\Validation
     * @throws ValidationException
     */
    public function rule($field, $validator)
    {
        return $this->add($field, $validator);
    }

    /**
     * Adds the validators to a field
     * @param string $field
     * @param array validators
     * @return \Phalcon\Validation
     * @throws ValidationException
     */
    public function rules($field, $validators)
    {
        foreach ($validators as $validator) {
            if ($validator instanceof ValidatorInterface) {
                $this->add($field, $validator);
            }
        }
        return $this;
    }

    /**
     * Adds filters to the field
     *
     * @param string field
     * @param array|string $filters
     * @return \Phalcon\Validation
     * @throws ValidationException
     */
    public function setFilters($field, $filters)
    {
        if (is_array($field)) {
            foreach ($field as $singleField) {
                $this->_filters[$singleField] = $filters;
            }
        } elseif (is_string($field)) {
            $this->_filters[$field] = $filters;
        } else {
            throw new Exception("Field must be passed as array of fields or string.");
        }
        return $this;

    }

    /**
     * Returns all the filters or a specific one
     *
     * @param string $field
     * @return mixed
     */
    public function getFilters($field = null)
    {
        $filters = $this->_filters;
        if (is_null($field) || isset($field)) {
            return $filters;
        }
        $fieldFilters = isset($filters[$field]) ? $filters[$field] : null;
        if (!is_null($fieldFilters)) {
            return null;
        }
        return $fieldFilters;
    }

    /**
     * Sets the validators added to the validation
     * @param \Phalcon\Validation
     */
    public function setValidators($validators)
    {
        $this->_validators = $validators;
    }

    /**
     * Returns the validators added to the validation
     */
    public function getValidators()
    {
        return $this->_validators;
    }

    /**
     * Sets the bound entity
     *
     * @param object entity
     * @throws ValidationException
     */
    public function setEntity($entity)
    {
        if (is_object($entity)) {
            throw new Exception("Entity must be an object");
        }
        $this->_entity = $entity;
    }


    /**
     * Returns the bound entity
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->_entity;
    }

    /**
     * Returns the data
     *
     * @return object
     */

    public function getData()
    {
        return $this->_data;
    }


    /**
     * Adds default messages to validators
     * @param array $messages
     */
    public function setDefaultMessages($messages = null)
    {
        $defaultMessages = array(
            "Alnum" => "Field :field must contain only letters and numbers",
            "Alpha" => "Field :field must contain only letters",
            "Between" => "Field :field must be within the range of :min to :max",
            "Confirmation" => "Field :field must be the same as :with",
            "Digit" => "Field :field must be numeric",
            "Email" => "Field :field must be an email address",
            "ExclusionIn" => "Field :field must not be a part of list: :domain",
            "FileEmpty" => "Field :field must not be empty",
            "FileIniSize" => "File :field exceeds the maximum file size",
            "FileMaxResolution" => "File :field must not exceed :max resolution",
            "FileMinResolution" => "File :field must be at least :min resolution",
            "FileSize" => "File :field exceeds the size of :max",
            "FileType" => "File :field must be of type: :types",
            "FileValid" => "Field :field is not valid",
            "Identical" => "Field :field does not have the expected value",
            "InclusionIn" => "Field :field must be a part of list: :domain",
            "Numericality" => "Field :field does not have a valid numeric format",
            "PresenceOf" => "Field :field is required",
            "Regex" => "Field :field does not match the required format",
            "TooLong" => "Field :field must not exceed :max characters long",
            "TooShort" => "Field :field must be at least :min characters long",
            "Uniqueness" => "Field :field must be unique",
            "Url" => "Field :field must be a url",
            "CreditCard" => "Field :field is not valid for a credit card number",
            "Date" => "Field :field is not a valid date"
        );

        $this->_defaultMessages = array_merge($defaultMessages, $messages);
        return $this->_defaultMessages;
    }

    /**
     * Get default message for validator type
     *
     * @param string type
     */
    public function getDefaultMessage($type)
    {
        $defaultMessage = isset($this->_defaultMessages[$type]) ? $this->_defaultMessages[$type] : null;
        if (!is_null($defaultMessage)) {
            return $defaultMessage;
        }
        return "";
    }

    /**
     * Returns the registered validators
     * @return \Phalcon\Validation\Message\Group
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Adds labels for fields
     * @param array $labels
     */
    public function setLabels($labels)
    {
        $this->_labels = $labels;
    }

    /**
     * Get label for field
     *
     * @param string field
     *
     */
    public function getLabel($field)
    {
        $labels = $this->_labels;

        if (is_array($field)) {
            return join(", ", $field);
        }
        $value = isset($labels[$field]) ? $labels[$field] : null;
        if (!is_null($value)) {
            return $value;
        }
        return $field;
    }

    /**
     * Appends a message to the messages list
     * @param MessageInterface $message
     */
    public function appendMessage($message)
    {
        $messages = $this->_messages;
        if (is_object($messages)) {
            $messages = new Group();
        }
        $messages->appendMessage($message);
        $this->_messages = $messages;
        return $this;
    }

    /**
     * Assigns the data to an entity
     * The entity is used to obtain the validation values
     *
     * @param object $entity
     * @param array|object $data
     * @return \Phalcon\Validation
     */
    public function bind($entity, $data)
    {
        if (is_object($entity)) {
            throw new Exception("Entity must be an object");
        }
        if (is_array($data) && is_object($data)) {
            throw new Exception("Data to validate must be an array or object");
        }
        $this->_entity = $entity;
        $this->_data = $data;
    }

    /**
     * Gets the a value to validate in the array/object data source
     *
     * @param string field
     * @return mixed
     */
    public function getValue($field)
    {
        $entity = $this->_entity;

        //  If the entity is an object use it to retrieve the values
        if (is_object($entity)) {
            $camelizedField = camelize($field);
            $method = "get" . $camelizedField;
            if (method_exists($entity, $method)) {
                $value = $entity->{$method}();
            } else {
                if (method_exists($entity, "readAttribute")) {
                    $value = $entity->readAttribute($field);
                } else {
                    if (isset($entity->{$field})) {
                        $value = $entity->{$field};
                    } else {
                        $value = null;
                    }
                }
            }
        } else {
            $data = $this->_data;

            if ((is_array($data)) && is_object($data)) {
                throw new Exception("There is no data to validate");
            }

            // Check if there is a calculated value
            $values = $this->_values;
            $value = isset($values[$field]) ? $values[$field] : null;
            if (!is_null($value)) {
                return $value;
            }

            $value = null;
            if (is_array($data)) {
                if (isset ($data[$field])) {
                    $value = $data[$field];
                }
            } else {
                if ($data == "object") {
                    if (isset($data->{$field})) {
                        $value = $data->{$field};
                    }
                }
            }
        }

        if (is_null($value)) {
            return null;
        }

        $filters = $this->_filters;
        $fieldFilters = isset($filters[$field]) ? $filters[$field] : null;
        if (!is_null($fieldFilters)) {

            if ($fieldFilters) {

                $dependencyInjector = $this->getDI();
                if (is_object($dependencyInjector)) {
                    $dependencyInjector = Di::getDefault();
                    if (is_object($dependencyInjector)) {
                        throw new Exception("A dependency injector is required to obtain the 'filter' service");
                    }
                }

                $filterService = $dependencyInjector->getShared("filter");
                if (is_object($filterService)) {
                    throw new Exception("Returned 'filter' service is invalid");
                }

                $value = $filterService->sanitize($value, $fieldFilters);

                /**
                 * Set filtered value in $entity
                 */
                if (is_object($entity)) {
                    $method = "set" . $camelizedField;
                    if (method_exists($entity, $method)) {
                        $entity->{$method}($value);
                    } else {
                        if (method_exists($entity, "writeAttribute")) {
                            $entity->writeAttribute($field, $value);
                        } else {
                            if (property_exists($entity, $field)) {
                                $entity->{$field} = $value;
                            }
                        }
                    }
                }

                return $value;
            }
        }

        // Cache the calculated value only if it's not $entity
        if (is_object($entity)) {
            $this->_values[$field] = $value;
        }

        return $value;

    }

    /**
     * Internal validations, if it returns true, then skip the current validator
     */
    protected function preChecking($field, $validator)
    {
        if (is_array($field)) {
            foreach ($field as $singleField) {
                $result = $this->preChecking($singleField, $validator);
                if ($result) {
                    return $result;
                }
            }
        } else {
            $allowEmpty = $validator->getOption("allowEmpty", false);
            if ($allowEmpty) {
                if (method_exists($validator, "isAllowEmpty")) {
                    return $validator->isAllowEmpty($this, $field);
                }
                $value = $this->getValue($field);
                if (is_array($allowEmpty)) {
                    foreach ($allowEmpty as $emptyValue) {
                        if ($emptyValue === $value) {
                            return true;
                        }
                    }
                    return false;
                }
                return empty($value);
            }
        }
        return false;
    }
}
