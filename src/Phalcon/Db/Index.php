<?php

namespace Phalcon\Db;

use \Phalcon\Db\IndexInterface;
use \Phalcon\Db\Exception;

/**
 * Phalcon\Db\Index
 *
 * Allows to define indexes to be used on tables. Indexes are a common way
 * to enhance database performance. An index allows the database server to find
 * and retrieve specific rows much faster than it could do without an index
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/index.c
 */
class Index implements IndexInterface
{

    /**
     * Index Name
     *
     * @var null
     * @access protected
     */
    protected $_indexName;

    /**
     * Columns
     *
     * @var null
     * @access protected
     */
    protected $_columns;


    /**
     * Index type
     *
     * @var string
     */
    protected $_type;

    /**
     * \Phalcon\Db\Index constructor
     *
     * @param string  $indexName
     * @param array   $columns
     * @param null    $type
     *
     * @throws \Phalcon\Db\Exception
     */
    public function __construct($indexName,  $columns, $type = null)
    {
        if (is_string($indexName) === false ||
            is_array($columns) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_indexName = $indexName;
        $this->_columns   = $columns;
        $this->_type      = (string) $type;
    }

    /**
     * Gets the index name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_indexName;
    }

    /**
     * Gets the columns that comprends the index
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->_columns;
    }


    /**
     * Gets the columns that comprends the index
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Restore a \Phalcon\Db\Index object from export
     *
     * @param array $data
     * @return \Phalcon\Db\IndexInterface
     * @throws Exception
     */
    public static function __set_state($data)
    {
        if (is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($data['_indexName']) === false) {
            throw new Exception('_indexName parameter is required');
        }

        if (isset($data['_columns']) === false) {
            throw new Exception('_columns parameter is required');
        }

        if (isset($data['_type']) === false) {
            $data['_type'] = null;
        }

        //Return a Phalcon\Db\Index as part of the returning state
        return new Index(
            $data['_indexName'], $data['_columns'], $data['_type']
        );
    }

}
