<?php

namespace Phalcon\Validation;

/**
 * Phalcon\Validation\Message
 *
 * Encapsulates validation info generated in the validation process
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/message.c
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
     * Code
     *
     * @var null|string
     */


    protected $_code;
    /**
     * \Phalcon\Validation\Message constructor
     *
     * @param string $message
     * @param $field
     * @param string|null $type
     * @param int $code
     * @throws Exception
     */
    public function __construct($message, $field = null, $type = null,$code = null)
    {
        if (is_string($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($type) === false && is_null($type) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if(!is_int($code) && ! is_null($code)){
            throw new Exception('Invalid parameter type.');
        }
        $this->_message = $message;
        $this->_field   = $field;
        $this->_type    = $type;
        $this->_code    = $code;
    }

    /**
     * Sets message type
     *
     * @param string $type
     * @return \Phalcon\Validation\Message
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
     * @return \Phalcon\Validation\Message
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
     * @return \Phalcon\Validation\Message
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
     * Sets code for the message
     * @param int $code
     * @return \Phalcon\Validation\Message
     */
    public function setCode($code)
	{
        $this->_code = $code;
		return $this;
	}

    /**
     * Returns the message code
     * @return int
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
     * Magic __set_state helps to recover messsages from serialization
     *
     * @param array $message
     * @return \Phalcon\Validation\Message
     * @throws Exception
     */
    public static function __set_state(array $message)
    {
        if (is_array($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return new Message($message['_message'], $message['_field'], $message['_type']);
    }

}
