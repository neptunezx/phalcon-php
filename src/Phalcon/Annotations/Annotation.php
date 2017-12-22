<?php

namespace Phalcon\Annotations;

/**
 * Phalcon\Annotations\Annotation
 *
 * Represents a single annotation in an annotations collection
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/annotation.c
 */
class Annotation
{

    /**
     * Integer Type
     *
     * @var int
     */
    const PHANNOT_T_INTEGER = 301;

    /**
     * Double Type
     *
     * @var int
     */
    const PHANNOT_T_DOUBLE = 302;

    /**
     * String Type
     *
     * @var int
     */
    const PHANNOT_T_STRING = 303;

    /**
     * Null Type
     *
     * @var int
     */
    const PHANNOT_T_NULL = 304;

    /**
     * False Type
     *
     * @var int
     */
    const PHANNOT_T_FALSE = 305;

    /**
     * True Type
     *
     * @var int
     */
    const PHANNOT_T_TRUE = 306;

    /**
     * Identifer Type
     *
     * @var int
     */
    const PHANNOT_T_IDENTIFIER = 307;

    /**
     * Array Type
     *
     * @var int
     */
    const PHANNOT_T_ARRAY = 308;

    /**
     * Annotation Type
     *
     * @var int
     */
    const PHANNOT_T_ANNOTATION = 300;

    /**
     * Name
     *
     * @var null|string
     * @access protected
     */
    protected $_name;

    /**
     * Arguments
     *
     * @var null|array
     * @access protected
     */
    protected $_arguments;

    /**
     * Expression Arguments
     *
     * @var null|array
     * @access protected
     */
    protected $_exprArguments;

    /**
     * \Phalcon\Annotations\Annotation constructor
     *
     * @param array $reflectionData
     * @throws Exception
     */
    public function __construct(array $reflectionData)
    {
        if (is_array($reflectionData) === false) {
            throw new Exception('Reflection data must be an array');
        }

        $this->_name = $reflectionData['name'];

        if (isset($reflectionData['arguments'])) {
            $exprArguments = $reflectionData['arguments'];

            $arguments = array();

            foreach ($exprArguments as $argument) {
                $resolvedArgument = $this->getExpression($argument['expr']);
                if (isset($argument['name'])) {
                    $arguments[$argument['name']] = $resolvedArgument;
                } else {
                    $arguments[] = $resolvedArgument;
                }
            }

            $this->_arguments = $arguments;
            $this->_exprArguments = $exprArguments;
        }
    }

    /**
     * Returns the annotation's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Resolves an annotation expression
     *
     * @param array $expr
     * @return mixed
     * @throws Exception
     */
    public function getExpression(array $expr)
    {
        if (is_array($expr) === false) {
            throw new Exception('The expression is not valid.');
        }
        $type = $expr['type'];
        switch ($type) {
            case self::PHANNOT_T_INTEGER:
            case self::PHANNOT_T_DOUBLE:
            case self::PHANNOT_T_STRING:
            case self::PHANNOT_T_IDENTIFIER:
                $value = $expr['value'];
                break;
            case self::PHANNOT_T_NULL:
                $value = null;
                break;
            case self::PHANNOT_T_FALSE:
                $value = false;
                break;
            case self::PHANNOT_T_TRUE:
                $value = true;
                break;
            case self::PHANNOT_T_ARRAY:
                $arrayValue = [];
                foreach ($expr['items'] as $item) {
                    $resolvedItem = $this->getExpression($item['expr']);
                    if (isset($item['name'])) {
                        $arrayValue[$item['name']] = $resolvedItem;
                    } else {
                        $arrayValue[] = $resolvedItem;
                    }
                }
                return $arrayValue;
            case self::PHANNOT_T_ANNOTATION:
                return new Annotation($expr);
            default:
                throw new Exception('The expression ' . (int)$expr['type'] . 'is unknown.');

        }
        return $value;
    }

    /**
     * Returns the expression arguments without resolving
     *
     * @return array|null
     */
    public function getExprArguments()
    {
        return $this->_exprArguments;
    }

    /**
     * Returns the expression arguments
     *
     * @return array|null
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Returns the number of arguments that the annotation has
     *
     * @return int
     */
    public function numberArguments()
    {
        return (int)count($this->_arguments);
    }

    /**
     * Returns an argument in a specific position
     *
     * @param int|string $position
     * @return mixed
     * @throws Exception
     */
    public function getArgument($position)
    {

        if (isset($this->_arguments[$position])) {
            return $this->_arguments[$position];
        }
    }

    /**
     * Checks if the annotation has a specific argument
     * @param $position
     * @return boolean
     * @throws Exception
     */
    public function hasArgument($position)
    {
        return isset($this->_arguments[$position]);
    }

    /**
     * Returns a named argument
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getNamedArgument($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (isset($this->_arguments[$name])) {
            return $this->getArgument($name);
        }
    }

    /**
     * Returns a named argument (deprecated)
     *
     * @deprecated
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getNamedParameter($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }
        return $this->getArgument($name);
    }

    /**
     * Checks if the annotation has a specific named argument
     *
     * @param string $position
     * @return boolean
     */
    public function hasNamedArgument($position)
    {
        return $this->hasArgument($position);
    }

}
