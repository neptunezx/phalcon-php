<?php

namespace Phalcon\Annotations;

use \Iterator;
use \Countable;
use \Phalcon\Annotations\Exception;
use \Phalcon\Annotations\Annotation;

class Collection implements Iterator, Countable
{

    /**
     * Position
     *
     * @var int
     * @access protected
     */
    protected $_position = 0;

    /**
     * Annotations
     *
     * @var null|array
     * @access protected
     */
    protected $_annotations;

    /**
     * \Phalcon\Annotations\Collection constructor
     *
     * @param array|null $reflectionData
     * @throws Exception
     */
    public function __construct($reflectionData = null)
    {
        if (is_array($reflectionData) === false && $reflectionData !== null) {
            throw new Exception('Reflection data must be an array');
        }
        $annotations = [];
        if (is_array($reflectionData)) {
            foreach ($reflectionData as $annotationData) {
                $annotations[] = new Annotation($annotationData);
            }
        }
        $this->_annotations = $annotations;
    }

    /**
     * Returns the number of annotations in the collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->_annotations);
    }

    /**
     * Rewinds the internal iterator
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Returns the current annotation in the iterator
     *
     * @return Annotation|boolean
     */
    public function current()
    {
        if (isset($this->_annotations[$this->_position])) {
            return $this->_annotations[$this->_position];
        } else {
            return false;
        }
    }

    /**
     * Returns the current position/key in the iterator
     *
     * @return int
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Moves the internal iteration pointer to the next position
     *
     */
    public function next()
    {
        $this->_position++;
    }

    /**
     * Check if the current annotation in the iterator is valid
     *
     * @return boolean
     */
    public function valid()
    {
        return isset($this->_annotations[$this->_position]);
    }

    /**
     * Returns the internal annotations as an array
     *
     * @return Annotation[]
     */
    public function getAnnotations()
    {
        return $this->_annotations;
    }

    /**
     * Returns the first annotation that match a name
     *
     * @param string $name
     * @return \Phalcon\Annotations\Annotation
     * @throws Exception
     */
    public function get($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $annotations = $this->_annotations;
        if (is_array($annotations)) {
            foreach ($annotations as $annotation) {
                if ($name == $annotation->getName()) {
                    return $annotation;
                }
            }
        }

        throw new Exception('The collection doesn\'t have an annotation called ' . $name . '\'');
    }

    /**
     * Returns all the annotations that match a name
     *
     * @param string $name
     * @return Annotation[]
     * @throws Exception
     */
    public function getAll($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $found = [];
        $annotations = $this->_annotations;
        if (is_array($annotations)) {
            foreach ($annotations as $annotation) {
                if ($name == $annotation->getName()) {
                    $found[] = $annotation;
                }
            }
        }

        return $found;
    }

    /**
     * Check if an annotation exists in a collection
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public function has($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $annotations = $this->_annotations;
        if (is_array($annotations)) {
            foreach ($annotations as $annotation) {
                if ($name == $annotation->geName()) {
                    return true;
                }
            }
        }
        return false;

    }

}
