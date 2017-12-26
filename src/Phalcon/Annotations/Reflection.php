<?php

namespace Phalcon\Annotations;

/**
 * Phalcon\Annotations\Reflection
 *
 * Allows to manipulate the annotations reflection in an OO manner
 *
 * <code>
 * //Parse the annotations in a class
 * $reader = new \Phalcon\Annotations\Reader();
 * $parsing = $reader->parse('MyComponent');
 *
 * //Create the reflection
 * $reflection = new \Phalcon\Annotations\Reflection($parsing);
 *
 * //Get the annotations in the class docblock
 * $classAnnotations = $reflection->getClassAnnotations();
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/reflection.c
 */
class Reflection
{

    /**
     * Reflection Data
     *
     * @var null|array
     * @access protected
     */
    protected $_reflectionData;

    /**
     * Class Annotations
     *
     * @var null
     * @access protected
     */
    protected $_classAnnotations;

    /**
     * Method Annotations
     *
     * @var null
     * @access protected
     */
    protected $_methodAnnotations;

    /**
     * Property Annotations
     *
     * @var null
     * @access protected
     */
    protected $_propertyAnnotations;

    /**
     * \Phalcon\Annotations\Reflection constructor
     *
     * @param array|null $reflectionData
     */
    public function __construct($reflectionData = null)
    {
        if (is_array($reflectionData) === true) {
            $this->_reflectionData = $reflectionData;
        }
    }

    /**
     * Returns the annotations found in the class docblock
     *
     * @return Collection|boolean
     */
    public function getClassAnnotations()
    {
        $annotations = $this->_classAnnotations;
        if (is_object($annotations) === false) {
            if (isset($this->_reflectionData['class'])) {
                $collection = new Collection($this->_reflectionData['class']);
                $this->_classAnnotations = $collection;
                return $collection;
            }
            $this->_classAnnotations = false;
            return false;
        }
        return $annotations;
    }

    /**
     * Returns the annotations found in the methods' docblocks
     *
     * @return Collection[]|boolean
     */
    public function getMethodsAnnotations()
    {
        $annotations = $this->_methodAnnotations;
        if (is_object($annotations) === false) {
            if (isset($this->_reflectionData['methods'])) {
                $reflectionMethods = $this->_reflectionData['methods'];
                if (count($reflectionMethods)) {
                    $collections = [];
                    foreach ($reflectionMethods as $methodName => $reflectionMethod) {
                        $collections[$methodName] = new Collection($reflectionMethod);
                    }
                    $this->_methodAnnotations = $collections;
                    return $collections;
                }
            }
            $this->_methodAnnotations = false;
            return false;
        }
        return $annotations;
    }

    /**
     * Returns the annotations found in the properties' docblocks
     *
     * @return Collection[]|boolean
     */
    public function getPropertiesAnnotations()
    {
        $annotations = $this->_propertyAnnotations;
        if (is_object($annotations) === false) {
            if (isset($this->_reflectionData['properties'])) {
                $reflectionProperties = $this->_reflectionData['properties'];
                if (count($reflectionProperties)) {
                    $collections = [];
                    foreach ($reflectionProperties as $property => $reflectionProperty) {
                        $collections[$property] = new Collection($reflectionProperty);
                    }
                    $this->_propertyAnnotations = $collections;
                    return $collections;
                }
            }
            $this->_propertyAnnotations = false;
            return false;
        }
        return $annotations;
    }

    /**
     * Returns the raw parsing intermediate definitions used to construct the reflection
     *
     * @return array|null
     */
    public function getReflectionData()
    {
        return $this->_reflectionData;
    }

    /**
     * Restores the state of a \Phalcon\Annotations\Reflection variable export
     *
     * @param array $data
     * @return Reflection
     */
    public static function __set_state($data)
    {
        if (is_array($data)) {
            if (isset($data['_reflectionData'])) {
                return new self($data['_reflectionData']);
            }
        }

        return new self();
    }

}
