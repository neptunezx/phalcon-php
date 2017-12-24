<?php

namespace Phalcon\Validation\Message;

use \Phalcon\Validation\Message;
use \Phalcon\Validation\MessageInterface;
use \Phalcon\Validation\Exception;
use \Countable;
use \ArrayAccess;
use \Iterator;

/**
 * Phalcon\Validation\Message\Group
 *
 * Represents a group of validation messages
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/validation/message/group.c
 */
class Group implements Countable, ArrayAccess, Iterator
{

    /**
     * Position
     *
     * @var null|int
     * @access protected
     */
    protected $_position;

    /**
     * Messages
     *
     * @var null|array
     * @access protected
     */
    protected $_messages;

    /**
     * \Phalcon\Validation\Message\Group constructor
     *
     * @param array|null $messages
     */
    public function __construct($messages = null)
    {
        if (is_array($messages) === true) {
            $this->_messages = $messages;
        } else {
            $this->_messages = array();
        }
    }

    /**
     * Gets an attribute a message using the array syntax
     *
     * <code>
     * print_r($messages[0]);
     * </code>
     *
     * @param int $index
     * @return \Phalcon\Validation\Message|bool
     * @throws Exception
     */
    public function offsetGet($index)
    {
        if (is_scalar($index) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (isset($this->_messages[$index]) === true) {
            return $this->_messages[$index];
        }
        return false;
    }

    /**
     * Sets an attribute using the array-syntax
     *
     * <code>
     * $messages[0] = new \Phalcon\Validation\Message('This is a message');
     * </code>
     *
     * @param int $index
     * @param \Phalcon\Validation\Message $message
     * @throws Exception
     */
    public function offsetSet($index, $message)
    {
        if (is_scalar($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($message) === false &&
            $message instanceof Message === false) {
            throw new Exception('The message must be an object');
        }

        $this->_messages[$index] = $message;
    }

    /**
     * Checks if an index exists
     *
     * <code>
     * var_dump(isset($message['database']));
     * </code>
     *
     * @param string $index
     * @return boolean
     * @throws Exception
     */
    public function offsetExists($index)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->_messages[$index]);
    }

    /**
     * Removes a message from the list
     *
     * <code>
     * unset($message['database']);
     * </code>
     *
     * @param int $index
     * @throws Exception
     */
    public function offsetUnset($index)
    {
        if (is_scalar($index) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (isset ($this->_messages[$index])) {
            array_splice($this->_messages, $index, 1);
        }
    }

    /**
     * Appends a message to the group
     *
     * <code>
     * $messages->appendMessage(new \Phalcon\Validation\Message('This is a message'));
     * </code>
     *
     * @param \Phalcon\Validation\MessageInterface $message
     * @throws Exception
     */
    public function appendMessage($message)
    {
        if (is_object($message) === false &&
            $message instanceof MessageInterface) {
            throw new Exception('The message must be an object');
        }

        $this->_messages[] = $message;
    }

    /**
     * Appends an array of messages to the group
     *
     * <code>
     * $messages->appendMessages($messagesArray);
     * </code>
     *
     * @param \Phalcon\Validation\MessageInterface[]|array $messages
     * @throws Exception
     */
    public function appendMessages($messages)
    {
        if (is_array($messages) === false) {
            if (is_object($messages) === false) {
                throw new Exception('The message must be array or object');
            }
        }
        $currentMessages = $this->_messages;
        if (is_array($messages)) {

            /**
             * An array of messages is simply merged into the current one
             */
            if (is_array($currentMessages)) {
                $finalMessages = array_merge($currentMessages, $messages);
            } else {
                $finalMessages = $messages;
            }
            $this->_messages = $finalMessages;

        } else {

            /**
             * A group of messages is iterated and appended one-by-one to the current list
             */
            //for message in iterator(messages) {
            //	this->appendMessage(message);
            //}

            $messages->rewind();
            while ($messages->valid()) {
                $message = $messages->current();
                $this->appendMessage($message);
                $messages->next();
            }
        }
    }

    /**
     * Filters the message group by field name
     *
     * @param string $fieldName
     * @return array
     * @throws Exception
     */
    public function filter($fieldName)
    {
        if (is_string($fieldName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $filtered = array();
        $messages = $this->_messages;
        if (is_array($messages)) {
            /**
             * A group of messages is iterated and appended one-by-one to the current list
             */
            foreach ($messages as $message) {
                /**
                 * Get the field name
                 */
                if (method_exists($message, "getField")) {
                    if ($fieldName == $message->getField()) {
                        array_push($filtered, $message);
                    }
                }
            }
        }
        return $filtered;
    }

    /**
     * Returns the number of messages in the list
     *
     * @return int
     */
    public function count()
    {
        return count($this->_messages);
    }

    /**
     * Rewinds the internal iterator
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Returns the current message in the iterator
     *
     * @return \Phalcon\Validation\Message|null
     */
    public function current()
    {
        if (isset($this->_messages[$this->_position]) === true) {
            return $this->_messages[$this->_position];
        }
        return null;
    }

    /**
     * Returns the current position/key in the iterator
     *
     * @return int|null
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Moves the internal iteration pointer to the next position
     */
    public function next()
    {
        ++$this->_position;
    }

    /**
     * Check if the current message in the iterator is valid
     *
     * @return boolean
     */
    public function valid()
    {
        return isset($this->_messages[$this->_position]);
    }

    /**
     * Magic __set_state helps to re-build messages variable when exporting
     *
     * @param array $group
     * @return \Phalcon\Validation\Message\Group
     * @throws Exception
     */
    public static function __set_state($group)
    {
        if (is_array($group) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return new Group($group['_messages']);
    }

}
