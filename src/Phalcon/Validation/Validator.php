<?php

namespace Phalcon\Validation;

use Codeception\Lib\Connector\Phalcon;
use Phalcon\Validation;

/**
 * Phalcon\Validation\Validator
 *
 * This is a base class for validators
 */
abstract class Validator implements ValidatorInterface
{

    /**
     * Options
     *
     * @var null
     * @access protected
     */
    protected $_options;

    /**
     * \Phalcon\Validation\Validator constructor
     *
     * @param array|null $options
     * @throws Exception
     */
    public function __construct(array $options = null)
    {
        if (is_array($options) === true) {
            $this->_options = $options;
        } elseif (is_null($options) === false) {
            //@note this exception message is nonsence
            throw new Exception('The attribute must be a string');
        }
    }

    /**
     * Checks if an option is defined
     * @deprecated since 2.1.0
     * @see \Phalcon\Validation\Validator::hasOption()
     *
     * @param string $key
     * @return bool
     * @throws Exception
     */
    public function isSetOption($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }
        return isset($this->_options[$key]);
    }

    /**
     * Checks if an option is defined
     * @param string $key
     * @return bool
     * @throws Exception
     */
    public function hasOption($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }
        return isset($this->_options[$key]);
    }


    /**
     * Returns an option in the validator's options
     * Returns null if the option hasn't been set
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     * @throws Exception
     */
    public function getOption($key,$defaultValue = null)
    {

        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $defaultValue = null;
        $options = $this->_options;

        if (is_array($options)) {
            $value = isset($options[$key]) ? $options[$key] : null;
            if (!is_null($value)) {
                /*
                 * If we have attribute it means it's Uniqueness validator, we
                 * can have here multiple fields, so we need to check it
                 */
                if ($key == "attribute" && (is_array($value))) {
                    $fieldValue = isset($value[$key]) ? $value[$key] : null;
                    if (!is_null($fieldValue)) {
                        return $fieldValue;
                    }
                }
                return $value;
            }
        }

        return $defaultValue;
    }

    /**
     * Sets an option in the validator
     *
     * @param string $key
     * @param mixed $value
     * @throws Exception
     */
    public function setOption($key, $value)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_options) === false) {
            $this->_options = array();
        }

        $this->_options[$key] = $value;
    }

    /**
     * Executes the validation
     * @param \Phalcon\Validation $validation
     * @param string $attribute
     * @return boolean
     */
    abstract public function validate($validation = null, $attribute = null);

    /**
     * Prepares a label for the field.
     * @param \Phalcon\Validation $validation
     * @param string $field
     * @return mixed
     * @throws Exception
     */
    protected function prepareLabel($validation, $field)
    {
        if(($validation instanceof Validation) === false || !is_string($field)){
            throw new Exception('Invalid parameter type.');
        }
        $label = $this->getOption("label");
        if (is_array($label)) {
            $label = $label[$field];
        }
        if (empty($label)) {
            $label = $validation->getLabel($field);
        }
        return $label;
    }

    /**
     * Prepares a validation message.
     * @param \Phalcon\Validation $validation
     * @param string $field
     * @param string $type
     * @param string $option
     * @return mixed
     * @throws Exception
     */
    protected function prepareMessage($validation, $field, $type, $option = "message")
    {
        if (!($validation instanceof Validation) || !is_string($field) || !is_string($type) || !is_string($option)) {
            throw new Exception('Invalid parameter type.');
        }

        $message = $this->getOption($option);
        if (is_array($message)) {
            $message = $message[$field];
        }

        if (empty($message)) {
            $message = $validation->getDefaultMessage($type);
        }

        return $message;
    }

    /**
     * Prepares a validation code.
     * @param string $field
     * @return int|null
     * @throws Exception
     */
    protected function prepareCode($field)
    {
        if (!is_string($field)) {
            throw new Exception('Invalid parameter type.');
        }
        $code = $this->getOption("code");
        if (is_array($code)) {
            $code = $code[$field];
        }

        return $code;
    }

}
