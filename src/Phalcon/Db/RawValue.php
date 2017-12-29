<?php

namespace Phalcon\Db;


/**
 * Phalcon\Db\RawValue
 *
 * This class allows to insert/update raw data without quoting or formating.
 *
 * The next example shows how to use the MySQL now() function as a field value.
 *
 * <code>
 *  $subscriber = new Subscribers();
 *  $subscriber->email = 'andres@phalconphp.com';
 *  $subscriber->created_at = new Phalcon\Db\RawValue('now()');
 *  $subscriber->save();
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/rawvalue.c
 */
class RawValue
{

    /**
     * Value
     *
     * @var null|string
     * @access protected
     */
    protected $_value;

    /**
     * RawValue constructor.
     *
     * @param $value
     *
     * @throws \Phalcon\Db\Exception
     */

    public function __construct($value)
    {
        if (is_string($value) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if ($value == "") {
            $this->_value = "''";
            return;
        }
        if ($value === null) {
            $this->_value = "NULL";
            return;
        }

        $this->_value = (string) $value;
    }

    /**
     * Returns internal raw value without quoting or formating
     *
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Magic method __toString returns raw value without quoting or formating
     */
    public function __toString()
    {
        return $this->_value;
    }

}
