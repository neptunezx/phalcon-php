<?php

namespace Phalcon\Db\Profiler;

use \Phalcon\Db\Exception;

/**
 * Phalcon\Db\Profiler\Item
 *
 * This class identifies each profile in a Phalcon\Db\Profiler
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/profiler/item.c
 */
class Item
{

    /**
     * SQL Statement
     *
     * @var null|string
     * @access protected
     */
    protected $_sqlStatement;

    /**
     * QL bind types related to the profile
     *
     * @var null|array
     * @access protected
     */
    protected $_sqlVariables;

    /**
     * Initial Time
     *
     * @var null|array
     * @access protected
     */
    protected $_sqlBindTypes;

    /**
     * SQL variables related to the profile
     *
     * @var null|int
     * @access protected
     */
    protected $_initialTime;

    /**
     * Final Time
     *
     * @var null|int
     * @access protected
     */
    protected $_finalTime;

    /**
     * Sets the SQL statement related to the profile
     *
     * @param string $sqlStatement
     * @throws Exception
     */
    public function setSQLStatement($sqlStatement)
    {
        if (is_string($sqlStatement) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_sqlStatement = $sqlStatement;
    }

    /**
     * Returns the SQL statement related to the profile
     *
     * @return string|null
     */
    public function getSQLStatement()
    {
        return $this->_sqlStatement;
    }

    /**
     * Sets the timestamp on when the profile started
     *
     * @param int $initialTime
     * @throws Exception
     */
    public function setInitialTime($initialTime)
    {
        if (is_int($initialTime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_initialTime = $initialTime;
    }

    /**
     * @param array $sqlvariables
     *
     * @throws \Phalcon\Db\Exception
     */
    public function setSQLVariables($sqlvariables)
    {
        if (is_array($sqlvariables) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_sqlVariables = $sqlvariables;
    }

    /**
     * @param array $sqlbindtypes
     *
     * @throws \Phalcon\Db\Exception
     */
    public function setSQLBindTypes($sqlbindtypes)
    {
        if (is_array($sqlbindtypes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_sqlBindTypes = $sqlbindtypes;
    }

    /**
     * Sets the timestamp on when the profile ended
     *
     * @param int $finalTime
     * @throws Exception
     */
    public function setFinalTime($finalTime)
    {
        if (is_int($finalTime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_finalTime = $finalTime;
    }

    /**
     * Returns the initial time in milseconds on when the profile started
     *
     * @return int|null
     */
    public function getInitialTime()
    {
        return $this->_initialTime;
    }

    /**
     * Returns the initial time in milseconds on when the profile ended
     *
     * @return int|null
     */
    public function getFinalTime()
    {
        return $this->_finalTime;
    }


    /**
     * @return array|null
     */
    public function getSQLVariables()
    {

        return $this->_sqlVariables;
    }

    /**
     * @return array|null
     */
    public function getQLBindTypes()
    {
        return $this->_sqlBindTypes;

    }

    /**
     * Returns the total time in seconds spent by the profile
     *
     * @return int
     */
    public function getTotalElapsedSeconds()
    {
        return $this->_finalTime - $this->_initialTime;
    }

}
