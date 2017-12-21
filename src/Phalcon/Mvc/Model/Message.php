<?php

namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\MessageInterface;

/**
 * Phalcon\Mvc\Model\Message
 *
 * Encapsulates validation info generated before save/delete records fails
 *
 * <code>
 * use Phalcon\Mvc\Model\Message as Message;
 *
 * class Robots extends \Phalcon\Mvc\Model
 * {
 *     public function beforeSave()
 *     {
 *         if ($this->name === "Peter") {
 *             $text  = "A robot cannot be named Peter";
 *             $field = "name";
 *             $type  = "InvalidValue";
 *
 *             $message = new Message($text, $field, $type);
 *
 *             $this->appendMessage($message);
 *         }
 *     }
 * }
 * </code>
 *
 */
class Message implements MessageInterface
{

    /**
     * Type
     *
     * @var null|string
     * @access protected
     */
    protected $_type;

    /**
     * Message
     *
     * @var null|string
     * @access protected
     */
    protected $_message;

    /**
     * Field
     *
     * @var null|string
     * @access protected
     */
    protected $_field;

    /**
     * Model
     *
     * @var null|\Phalcon\Mvc\ModelInterface
     * @access protected
     */
    protected $_model;

    /**
     * Code
     *
     * @var null|\Phalcon\Mvc\ModelInterface
     * @access protected
     */
    protected $_code;

    /**
     * Phalcon\Mvc\Model\Message constructor
     *
     * @param string message
     * @param string|array field
     * @param string type
     * @param \Phalcon\Mvc\ModelInterface model
     * @param int|null code
     */
    public function __construct($message, $field = null, $type = null, ModelInterface $model = null, $code = null)
    {
        if (is_string($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($field) === false &&
            is_null($field) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($type) === false &&
            is_null($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($code) === false &&
            is_object($code) === false) {
            throw new Exception('Invalid parameter type.');
        }
        
        $this->_message = $message;
        $this->_field   = $field;
        $this->_type    = $type;

        if (is_object($model) === true) {
            $this->_model = $model;
        }
    }

    /**
     * Sets message type
     *
     * @param string $type
     * @return \Phalcon\Mvc\Model\Message
     * @throws Exception
     */
    public function setType($type)
    {
        if (is_string($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_type = $type;

        return $this;
    }

    /**
     * Returns message type
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets verbose message
     *
     * @param string $message
     * @return \Phalcon\Mvc\Model\Message
     * @throws Exception
     */
    public function setMessage($message)
    {
        if (is_string($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_message = $message;

        return $this;
    }

    /**
     * Returns verbose message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * Sets field name related to message
     *
     * @param string $field
     * @return \Phalcon\Mvc\Model\Message
     * @throws Exception
     */
    public function setField($field)
    {
        if (is_string($field) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_field = $field;

        return $this;
    }

    /**
     * Returns field name related to message
     *
     * @return string|null
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * Set the model who generates the message
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @return \Phalcon\Mvc\Model\Message
     * @throws Exception
     */
    public function setModel(ModelInterface $model)
    {
        $this->_model = $model;

        return $this;
    }

    /**
     * Returns the model that produced the message
     *
     * @return \Phalcon\Mvc\ModelInterface|null
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Sets code for the message
     */
    public function setCode($code)
    {
        $this->_code = (int) $code;
        return $this;
    }

    /**
     * Returns the message code
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Magic __toString method returns verbose message
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_message;
    }

    /**
     * Magic __set_state helps to re-build messages variable exporting
     *
     * @param array $message
     * @return \Phalcon\Mvc\Model\Message
     */
    public static function __set_state(array $message)
    {
        if (is_array($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return new self($message["_message"], $message["_field"], $message["_type"], $message["_code"]);
    }

}
