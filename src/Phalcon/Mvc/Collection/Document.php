<?php

namespace Phalcon\Mvc\Collection;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Collection\Exception;

/**
 * Phalcon\Mvc\Collection\Document
 *
 * This component allows Phalcon\Mvc\Collection to return rows without an associated entity.
 * This objects implements the ArrayAccess interface to allow access the object as object->x or array[x].
 */
class Document implements EntityInterface, \ArrayAccess
{

    /**
     * Checks whether an offset exists in the document
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

        return isset($this->$index);
    }

    /**
     * Returns the value of a field using the ArrayAccess interfase
     *
     * @param string $index
     * @return mixed
     * @throws Exception
     */
    public function offsetGet($index)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->$index) === true) {
            return $this->$index;
        }

        throw new Exception('The index does not exist in the row');
    }

    /**
     * Change a value using the ArrayAccess interface
     *
     * @param string $index
     * @param mixed $value
     * @throws Exception
     */
    public function offsetSet($index, $value)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->$index = $value;
    }

    /**
     * Rows cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
     *
     * @param string $offset
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        throw new Exception('The index does not exist in the row');
    }

    /**
     * Reads an attribute value by its name
     *
     * <code>
     *  echo $robot->readAttribute('name');
     * </code>
     *
     * @param string $attribute
     * @return mixed
     * @throws Exception
     */
    public function readAttribute($attribute)
    {
        if (is_string($attribute) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->$attribute) === true) {
            return $this->$attribute;
        }

        return null;
    }

    /**
     * Writes an attribute value by its name
     *
     * <code>
     *  $robot->writeAttribute('name', 'Rosey');
     * </code>
     *
     * @param string $attribute
     * @param mixed $value
     * @throws Exception
     */
    public function writeAttribute($attribute, $value)
    {
        if (is_string($attribute) === false) {
            throw new Exception('Invalid parameter type.');
        }

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

}
