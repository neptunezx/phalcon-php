<?php

namespace Phalcon\Annotations;


use \ReflectionClass;

/**
 * Phalcon\Annotations\Reader
 *
 * Parses docblocks returning an array with the found annotations
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/reader.c
 */
class Reader implements ReaderInterface
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
     * Reads annotations from the class dockblocks, its methods and/or properties
     *
     * @param string $className
     * @return array
     * @throws Exception
     */
    public function parse($className)
    {
        if (is_string($className) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $annotations = [];
        $reflection = new \ReflectionClass($className);
        $comment = $reflection->getDocComment();
        if (is_string($comment)){
            $classAnnotations = phannot_parse_annotations($comment, $reflection->getFileName(), $reflection->getStartLine());
            if (is_array($classAnnotations)){
                $annotations['class'] = $classAnnotations;
            }
        }
        $properties = $reflection->getProperties();
        if (count($properties)){
            $line =1;
            $annotationsProperties = [];
            foreach ($properties as $property){
                $comment = $property->getDocComment();
                if (is_string($comment)){
                    $propertyAnnotations = phannot_parse_annotations($comment, $reflection->getFileName(), $line);
				    if (is_array($propertyAnnotations)){
				        $annotationsProperties[$property->name] = $propertyAnnotations;
                    }
                }
            }
            if (count($annotationsProperties)){
                $annotations['properties'] = $annotationsProperties;
            }
        }
        $methods = $reflection->getMethods();
        if (count($methods)){
            $annotationsMethods = [];
            foreach ($methods as $method){
                $comment = $method->getDocComment();
                if (is_string($comment)){
                    $methodAnnotations = phannot_parse_annotations($comment, $method->getFileName(), $method->getStartLine());
	                if (is_array($methodAnnotations)){
	                    $annotationsMethods[$method->name] = $methodAnnotations;
                    }
                }
            }
            if (count($annotationsMethods)){
                $annotations['methods'] = $annotationsMethods;
            }
        }
        return $annotations;

    }

    /**
     * Parses a raw doc block returning the annotations found
     *
     * @param string $docBlock
     * @param string|null $file
     * @param int|null $line
     * @return array | false
     * @throws Exception
     */
    public static function parseDocBlock($docBlock, $file = null, $line = null)
    {
        if (is_string($docBlock) === false) {
        //    throw new Exception('Invalid parameter type.');
        }
        if (is_string($file)===false){
            $file = 'eval code';
        }
        return phannot_parse_annotations(docBlock, file, line);
    }

    /**
     * Parses an associative array using tokens
     *
     * @param string $raw
     * @return array
     * @throws Exception
     */
    private static function parseAssocArray($raw)
    {
        $l = strlen($raw);

        //Remove parantheses
        $raw = substr($raw, 1, $l - 1);
        $l   = $l - 2;

        $openBraces   = 0;
        $openBrackets = 0;
        $breakpoints  = array();

        for ($i = 0; $i < $l; ++$i) {
            switch ($raw[$i]) {
                case ',':
                    if ($openBraces === 0 &&
                        $openBrackets === 0) {
                        $breakpoints[] = $i + 1;
                    }
                    break;
                case '[':
                    ++$openBrackets;
                    break;
                case ']':
                    --$openBrackets;
                    break;
                case '{':
                    ++$openBraces;
                    break;
                case '}':
                    --$openBraces;
                    break;
                case '(':
                    throw new Exception('Syntax error, unexpected token.');
                    break;
                case ')':
                    throw new Exception('Syntax error, unexpected token.');
                    break;
            }
        }

        $breakpoints[] = $l + 1;

        if ($openBraces !== 0 || $openBrackets !== 0) {
            throw new Exception('Syntax error, unexpected token.');
        }

        $parameters = array();
        $lastBreak  = 0;

        foreach ($breakpoints as $break) {
            $str  = substr($raw, $lastBreak, $break - $lastBreak - 1);
            $posC = strpos($str, ':');
            $posE = strpos($str, '=');

            if ($posC !== false && $posE === false) {
                $parameters[] = explode(':', $str, 2);
            } elseif ($posE !== false && $posC === false) {
                $parameters[] = explode('=', $str, 2);
            } elseif ($posC !== false && $posE !== false && $posC < $posE) {
                $parameters[] = explode(':', $str, 2);
            } elseif ($posE !== false && $posE !== false && $posE < $posC) {
                $parameters[] = explode('=', $str, 2);
            } else {
                $parameters[] = $str;
            }
            $lastBreak = $break;
        }

        return $parameters;
    }

    /**
     * Parses a comma-separated parameter list using tokens
     *
     * @param string $raw
     * @return array
     * @throws Exception
     */
    private static function parseParameterList($raw)
    {
        $l = strlen($raw);

        //Remove parantheses
        $raw = substr($raw, 1, $l - 1);
        $l   = $l - 2;

        $openBraces   = 0;
        $openBrackets = 0;
        $breakpoints  = array();

        for ($i = 0; $i < $l; ++$i) {
            switch ($raw[$i]) {
                case ',':
                    if ($openBraces === 0 &&
                        $openBrackets === 0) {
                        $breakpoints[] = $i + 1;
                    }
                    break;
                case '[':
                    ++$openBrackets;
                    break;
                case ']':
                    --$openBrackets;
                    break;
                case '{':
                    ++$openBraces;
                    break;
                case '}':
                    --$openBraces;
                    break;
                case '(':
                    throw new Exception('Syntax error, unexpected token.');
                    break;
                case ')':
                    throw new Exception('Syntax error, unexpected token.');
                    break;
            }
        }

        $breakpoints[] = $l + 1;

        if ($openBraces !== 0 || $openBrackets !== 0) {
            throw new Exception('Syntax error, unexpected token.');
        }

        $parameters = array();
        $lastBreak  = 0;

        foreach ($breakpoints as $break) {
            $parameters[] = substr($raw, $lastBreak, $break - $lastBreak - 1);
            $lastBreak    = $break;
        }

        return $parameters;
    }

    /**
     * Parses a raw arguments expression
     *
     * @param string $raw
     * @return array
     * @throws Exception
     */
    private static function parseDocBlockArguments($raw)
    {
        if (is_string($raw) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $raw     = trim($raw);
        $matches = array();

        if ($raw == 'null') {
            /* Type: null */
            return array('expr' => array('type' => self::PHANNOT_T_NULL));
        } elseif ($raw == 'false') {
            /* Type: boolean (false) */
            return array('expr' => array('type' => self::PHANNOT_T_FALSE));
        } elseif ($raw == 'true') {
            /* Type: boolean (true) */
            return array('expr' => array('type' => self::PHANNOT_T_TRUE));
        } elseif (preg_match('#^((?:[+-]?)(?:[0-9])+)$#', $raw, $matches) > 0) {
            /* Type: integer */
            return array('expr' => array('type' => self::PHANNOT_T_INTEGER, 'value' => (string) $matches[0]));
        } elseif (preg_match('#^((?:[+-]?)(?:[0-9.])+)$#', $raw, $matches) > 0) {
            /* Type: float */
            return array('expr' => array('type' => self::PHANNOT_T_DOUBLE, 'value' => (string) $matches[0]));
        } elseif (preg_match('#^"(.*)"$#', $raw, $matches) > 0) {
            /* Type: quoted string */
            return array('expr' => array('type' => self::PHANNOT_T_STRING, 'value' => (string) $matches[0]));
        } elseif (preg_match('#^([\w]+):(?:[\s]*)((?:(?:[\w"]+)?|(?:(?:\{(?:.*)\}))|(?:\[(?:.*)\])))$#', $raw, $matches) > 0) {
            /* Colon-divided named parameters */
            return array_merge(array('name' => (string) $matches[1]), self::parseDocBlockArguments($matches[2]));
        } elseif (preg_match('#^([\w]+)=((?:(?:[\w"]+)?|(?:(?:\{(?:.*)\}))|(?:\[(?:.*)\])))$#', $raw, $matches) > 0) {
            /* Equal-divided named parameter */
            return array_merge(array('name' => (string) $matches[1]), self::parseDocBlockArguments($matches[2]));
        } elseif (preg_match('#^\((?:(\[[^()]+\]|\{[^()]+\}|[^{}[\](),]{1,})(?:,?))*\)(?:;?)$#', $raw) > 0) {
            /* Argument list (default/root element) */
            $results   = array();
            $arguments = self::parseParameterList($raw);
            foreach ($arguments as $argument) {
                $results[] = self::parseDocBlockArguments($argument);
            }
            return $results;
        } elseif (preg_match('#^\{(?:(?:([\w"]+)(?:[:=])[\s]*([\w"])+(?:}$|,[\s]*))|(?:([\w"]+)(?:[:=])[\s]*(\[(?:.*)\])(?:}$|,[\s]*))|(?:([\w"]+)(?:[:=][\s]*(\{(?:.*)\})(?:}$|,[\s]*)))|(?:([\w"]+)[\s]*(?:}$|,[\s]*)))+#', $raw) > 0) {
            /* Type: Associative Array */
            $result    = array();
            $arguments = self::parseAssocArray($raw);
            foreach ($arguments as $argument) {
                if (is_array($argument) === true) {
                    $result[] = array_merge(array('name' => (string) $argument[0]), self::parseDocBlockArguments($argument[1]));
                } else {
                    $result[] = self::parseDocBlockArguments($argument);
                }
            }
            return array('expr' => array('type' => self::PHANNOT_T_ARRAY, 'items' => $result));
        } elseif (preg_match_all('#^\[(?:((?:["\w]+)|(?:\[(?:.*)\])|(?:\{(?:.*)\}))(?:,|\]$)[\s]*)+#', $raw, $matches) > 0) {
            /* Type: Array */
            $items    = array();
            $elements = self::parseParameterList($raw);
            foreach ($elements as $element) {
                $items[] = self::parseDocBlockArguments($element);
            }
            return array('expr' => array('type' => self::PHANNOT_T_ARRAY, 'items' => $items));
        } elseif (ctype_alnum($raw) === true) {
            /* Type: Identifier */
            return array('expr' => array('type' => self::PHANNOT_T_IDENTIFIER, 'value' => (string) $raw));
        } else {
            /* Unknown annotation format */
         //   throw new Exception('Syntax error, unexpected token.');
        }
    }

}
