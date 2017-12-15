<?php

/**
 * Filter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon;

use \Closure;
use \Phalcon\FilterInterface;
use \Phalcon\Filter\Exception as FilterException;

/**
 * Phalcon\Filter
 *
 * The Phalcon\Filter component provides a set of commonly needed data filters. It provides
 * object oriented wrappers to the php filter extension. Also allows the developer to
 * define his/her own filters
 *
 *<code>
 * $filter = new \Phalcon\Filter();
 *
 * $filter->sanitize("some(one)@exa\\mple.com", "email"); // returns "someone@example.com"
 * $filter->sanitize("hello<<", "string"); // returns "hello"
 * $filter->sanitize("!100a019", "int"); // returns "100019"
 * $filter->sanitize("!100a019.01a", "float"); // returns "100019.01"
 *</code>
 */
class Filter implements FilterInterface
{

    const FILTER_EMAIL         = "email";
    const FILTER_ABSINT        = "absint";
    const FILTER_INT           = "int";
    const FILTER_INT_CAST      = "int!";
    const FILTER_STRING        = "string";
    const FILTER_FLOAT         = "float";
    const FILTER_FLOAT_CAST    = "float!";
    const FILTER_ALPHANUM      = "alphanum";
    const FILTER_TRIM          = "trim";
    const FILTER_STRIPTAGS     = "striptags";
    const FILTER_LOWER         = "lower";
    const FILTER_UPPER         = "upper";
    const FILTER_URL           = "url";
    const FILTER_SPECIAL_CHARS = "special_chars";

    /**
     * Filters
     *
     * @var null|array
     * @access protected
     */
    protected $_filters = null;

    /**
     * Adds a user-defined filter
     *
     * @param string $name
     * @param object|callable $handler
     * @return \Phalcon\Filter
     * @throws FilterException
     */
    public function add($name, $handler)
    {
        if (is_string($name) === false) {
            throw new FilterException('Filter name must be string');
        }

        if (!is_object($handler) && !is_callable($handler)) {
            throw new FilterException('Filter must be an object or callable');
        }

        if (is_array($this->_filters) === false) {
            $this->_filters = [];
        }

        $this->_filters[$name] = $handler;
    }

    /**
     * Sanitizes a value with a specified single or set of filters
     *
     * @param mixed $value
     * @param mixed $filters
     * @param boolean $noRecursive
     * @return mixed
     */
    public function sanitize($value, $filters, $noRecursive = false)
    {
        //Apply an array of filters
        if (is_array($filters) === true) {
            if (is_null($value) === false) {
                foreach ($filters as $filter) {
                    if (is_array($value) === true && !$noRecursive) {
                        $arrayValue = [];
                        foreach ($value as $itemKey => $itemValue) {
                            //@note no type check of $itemKey
                            $arrayValue[$itemKey] = $this->_sanitize($itemValue, $filter);
                        }

                        $value = $arrayValue;
                    } else {
                        $value = $this->_sanitize($value, $filter);
                    }
                }
            }

            return $value;
        }

        //Apply a single filter value
        if (is_array($value) === true && !$noRecursive) {
            $sanizitedValue = [];
            foreach ($value as $key => $itemValue) {
                //@note no type check of $key
                $sanizitedValue[$key] = $this->_sanitize($itemValue, $filters);
            }
        }

        return $this->_sanitize($value, $filters);
    }

    /**
     * Internal sanitize wrapper to filter_var
     *
     * @param mixed $value
     * @param string $filter
     * @return mixed
     * @throws FilterException
     */
    protected function _sanitize($value, $filter)
    {
        if (is_string($filter) === false) {
            throw new FilterException('Invalid parameter type.');
        }

        /* User-defined filter */
        if (isset($this->_filters[$filter]) === true) {
            $filterObject = $this->_filters[$filter];
            if ($filterObject instanceof Closure || is_callable($filterObject)) {
                return call_user_func_array($filterObject, array($value));
            }

            return $filterObject->filter($value);
        }

        /* Predefined filter */
        switch ($filter) {

            case Filter::FILTER_EMAIL:
                /**
                 * The 'email' filter uses the filter extension
                 */
                return filter_var($value, constant("FILTER_SANITIZE_EMAIL"));

            case Filter::FILTER_INT:
                /**
                 * 'int' filter sanitizes a numeric input
                 */
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);

            case Filter::FILTER_INT_CAST:

                return intval($value);

            case Filter::FILTER_ABSINT:

                return abs(intval($value));

            case Filter::FILTER_STRING:

                return filter_var($value, FILTER_SANITIZE_STRING);

            case Filter::FILTER_FLOAT:
                /**
                 * The 'float' filter uses the filter extension
                 */
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, ["flags" => FILTER_FLAG_ALLOW_FRACTION]);

            case Filter::FILTER_FLOAT_CAST:

                return doubleval($value);

            case Filter::FILTER_ALPHANUM:

                return preg_replace("/[^A-Za-z0-9]/", "", $value);

            case Filter::FILTER_TRIM:
                if (is_array($value)) {
                    foreach ($value as &$v) {
                        $v = trim($v);
                    }
                    return $value;
                } else {
                    $value = trim($value);
                }
                return $value;

            case Filter::FILTER_STRIPTAGS:

                return strip_tags($value);

            case Filter::FILTER_LOWER:

                if (function_exists("mb_strtolower")) {
                    /**
                     * 'lower' checks for the mbstring extension to make a correct lowercase transformation
                     */
                    return mb_strtolower($value);
                }
                return strtolower($value);

            case Filter::FILTER_UPPER:

                if (function_exists("mb_strtoupper")) {
                    /**
                     * 'upper' checks for the mbstring extension to make a correct lowercase transformation
                     */
                    return mb_strtoupper($value);
                }
                return strtoupper($value);

            case Filter::FILTER_URL:

                return filter_var($value, FILTER_SANITIZE_URL);

            case Filter::FILTER_SPECIAL_CHARS:

                return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);

            default:

                throw new Exception("Sanitize filter '" . $filter . "' is not supported");
        }
    }

    /**
     * Return the user-defined filters in the instance
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

}
