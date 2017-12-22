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
     */
    public function __construct($validators)
    {
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


    public function setValidators($validators){
        $this->_validators = $validators;
    }

    /**
     * Validate a set of data according to a set of rules
     *
     * @param array|object|null $data
     * @param object|null $entity
     * @return \Phalcon\Validation\Message\Group
     * @throws ValidationException
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
    public function add($field, $validator){

    }

    /**
     * Adds the validators to a field
     * @param string $field
     * @param \Phalcon\Validation\ValidatorInterface
     * @return \Phalcon\Validation
     * @throws ValidationException
     */
    public function rule($field, $validator){

    }

    /**
     * Adds the validators to a field
     * @param string $field
     * @param array validators
     * @return \Phalcon\Validation
     * @throws ValidationException
     */
    public function rules($field, $validators){

    }

    /**
     * Adds filters to the field
     *
     * @param string field
     * @param array|string $filters
     * @return \Phalcon\Validation
     * @throws ValidationException
     */
    public function setFilters($field, $filters){

    }

    /**
     * Returns all the filters or a specific one
     *
     * @param string $field
     * @return mixed
     */
    public function getFilters($field = null){

    }

    /**
     * Returns the validators added to the validation
     */
    public function getValidators(){

    }

    /**
     * Returns the bound entity
     *
     * @return object
     */
    public function getEntity(){
        return $this->_entity;
    }

    /**
     * Adds default messages to validators
     * @param array $messages
     */
    public function setDefaultMessages($messages = null){

    }

    /**
     * Get default message for validator type
     *
     * @param string type
     */
    public function getDefaultMessage($type){

    }

    /**
     * Returns the registered validators
     * @return \Phalcon\Validation\Message\Group
     */
    public function getMessages(){

    }

    /**
     * Adds labels for fields
     * @param array $labels
     */
    public function setLabels($labels){

    }

    /**
     * Get label for field
     *
     * @param string field
     *
     */
    public function getLabel($field){

    }

    /**
     * Appends a message to the messages list
     * @param MessageInterface $message
     */
    public function appendMessage($message){

    }

    /**
     * Assigns the data to an entity
     * The entity is used to obtain the validation values
     *
     * @param object $entity
     * @param array|object $data
     * @return \Phalcon\Validation
     */
    public function bind($entity, $data){

    }

    /**
     * Gets the a value to validate in the array/object data source
     *
     * @param string field
     * @return mixed
     */
    public function getValue($field){

    }
}
