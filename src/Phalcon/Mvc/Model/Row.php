<?php

/**
 * Row
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\ResultInterface;

/**
 * Phalcon\Mvc\Model\Row
 *
 * This component allows Phalcon\Mvc\Model to return rows without an associated entity.
 * This objects implements the ArrayAccess interface to allow access the object as object->x or array[x].
 */
class Row implements EntityInterface, ResultInterface, \ArrayAccess, \JsonSerializable
{

    /**
     * Set the current object's state
     *
     * @param int $dirtyState
     * @return boolean
     */
    public function setDirtyState($dirtyState)
    {
        return false;
    }

    /**
     * Checks whether offset exists in the row
     *
     * @param scalar $index
     * @return boolean
     * @throws Exception
     */
    public function offsetExists($index)
    {
        if (is_scalar($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->$index);
    }

    /**
     * Gets a record in a specific position of the row
     *
     * @param scalar $index
     * @return string|\Phalcon\Mvc\ModelInterface
     * @throws Exception
     */
    public function offsetGet($index)
    {
        if (is_scalar($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->$index) === true) {
            return $this->$index;
        }

        throw new Exception('The index does not exist in the row');
    }

    /**
     * Rows cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
     *
     * @param scalar $index
     * @param \Phalcon\Mvc\ModelInterface $value
     * @throws Exception
     */
    public function offsetSet($index, $value)
    {
        throw new Exception('Row is an immutable ArrayAccess object');
    }

    /**
     * Rows cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
     *
     * @param string|int offset
     * @return 
     */
    public function offsetUnset($offset)
    {
        throw new Exception('Row is an immutable ArrayAccess object');
    }

    /**
     * Reads an attribute value by its name
     *
     * <code>
     * echo $robot->readAttribute("name");
     * </code>
     *
     * @param string attribute
     * @return mixed
     */
    public function readAttribute($attribute)
    {
        if (is_string($attribute)) {
            if (isset($this->$attribute)) {
                return $this->$attribute;
            }
        }

        return null;
    }

    /**
     * Writes an attribute value by its name
     *
     * <code>
     * $robot->writeAttribute("name", "Rosey");
     * </code>
     *
     * @param string attribute
     * @param mixed value
     * @return void
     */
    public function writeAttribute($attribute, $value)
    {
        $this->$attribute = $value;
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Serializes the object for json_encode
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

}
