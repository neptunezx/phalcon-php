<?php

namespace Phalcon\Annotations;

use Phalcon\Annotations\AdapterInterface;
use Phalcon\Annotations\Reader;
use Phalcon\Annotations\Exception;
use Phalcon\Annotations\Collection;
use Phalcon\Annotations\Reflection;
use Phalcon\Annotations\ReaderInterface;

/**
 * Phalcon\Annotations\Adapter
 *
 * This is the base class for Phalcon\Annotations adapters
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/adapter.c
 */
abstract class Adapter implements AdapterInterface
{

    /**
     * Annotations Parser
     *
     * @var null|object
     * @access protected
     */
    protected $_reader;

    /**
     * Annotations
     *
     * @var null|array
     * @access protected
     */
    protected $_annotations;

    /**
     * Sets the annotations parser
     *
     * @param \Phalcon\Annotations\ReaderInterface $reader
     * @throws Exception
     */
    public function setReader($reader)
    {
        if (is_object($reader) === false ||
            $reader instanceof ReaderInterface === false) {
            throw new Exception('Invalid annotations reader');
        }

        $this->_reader = $reader;
    }

    /**
     * Returns the annotation reader
     *
     * @return \Phalcon\Annotations\ReaderInterface
     */
    public function getReader()
    {
        if (is_object($this->_reader) === false) {
            $this->_reader = new Reader();
        }
        return $this->_reader;
    }

    /**
     * Parses or retrieves all the annotations found in a class
     *
     * @param string|object $className
     * @return \Phalcon\Annotations\Reflection
     * @throws Exception
     */
    public function get($className)
    {
        if (is_object($className)) {
            $realClassName = get_class($className);
        } else {
            $realClassName = $className;
        }
        $annotations = $this->_annotations;
        if (is_array($annotations)) {
            if (isset($annotations[$realClassName])) {
                return $annotations[$realClassName];
            }
        }

        $classAnnotations = $this->{'read'}($realClassName);
        if ($classAnnotations === null || $classAnnotations === false) {
            $reader = $this->getReader();
            $parseAnnotations = $reader->parse($realClassName);

            if (is_array($parseAnnotations)) {
                $classAnnotations = new Reflection($parseAnnotations);
                $this->_annotations[$realClassName] = $classAnnotations;
                $this->{'write'}($realClassName, $classAnnotations);
            }
        }
        return $classAnnotations;
    }

    /**
     * Returns the annotations found in all the class' methods
     *
     * @param string $className
     * @return array
     * @throws Exception
     */
    public function getMethods($className)
    {
        if (is_string($className) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $classAnnotations = $this->get($className);
        if (is_object($classAnnotations)) {
            return $classAnnotations->getPropertiesAnnotations();
        }
        return [];
    }

    /**
     * Returns the annotations found in a specific method
     *
     * @param string $className
     * @param string $methodName
     * @return \Phalcon\Annotations\Collection
     * @throws Exception
     */
    public function getMethod($className, $methodName)
    {
        if (is_string($methodName) === false ||
            is_string($className)) {
            throw new Exception('Invalid parameter type.');
        }

        $classAnnotations = $this->get($className);

        if (is_object($classAnnotations)) {
            $methods = $classAnnotations->getMethodsAnnotations();
            if (is_array($methods) === true) {
                foreach ($methods as $name => $method) {
                    if (!strcasecmp($name,$methodName)) {
                        return $method;
                    }
                }
            }
        }

        return new Collection();
    }

    /**
     * Returns the annotations found in all the class' methods
     *
     * @param string $className
     * @return array
     * @throws Exception
     */
    public function getProperties($className)
    {
        if (is_string($className)===false){
            throw new Exception('Invalid parameter type.');
        }
        $classAnnotations = $this->get($className);

        if (is_object($classAnnotations) === true) {
            return $classAnnotations->getPropertiesAnnotations();
        }

        return [];
    }

    /**
     * Returns the annotations found in a specific property
     *
     * @param string $className
     * @param string $propertyName
     * @return \Phalcon\Annotations\Collection
     * @throws Exception
     */
    public function getProperty($className, $propertyName)
    {
        if (is_string($propertyName) === false||
        is_string($className)===false) {
            throw new Exception('Invalid parameter type.');
        }

        $classAnnotations = $this->get($className);

        if (is_object($classAnnotations)){
            $properties = $classAnnotations->getPropertiesAnnotations();
            if (is_array($properties) === true) {
               if (isset($properties[$propertyName])){
                   return $properties[$propertyName];
               }
            }
        }

        return new Collection();
    }

}
