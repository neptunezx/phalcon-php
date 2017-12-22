<?php

namespace Phalcon;

use \Phalcon\Db\Exception as DbException;

/**
 * Phalcon\Db
 *
 * Phalcon\Db and its related classes provide a simple SQL database interface for Phalcon Framework.
 * The Phalcon\Db is the basic class you use to connect your PHP application to an RDBMS.
 * There is a different adapter class for each brand of RDBMS.
 *
 * This component is intended to lower level database operations. If you want to interact with databases using
 * higher level of abstraction use Phalcon\Mvc\Model.
 *
 * Phalcon\Db is an abstract class. You only can use it with a database adapter like Phalcon\Db\Adapter\Pdo
 *
 * <code>
 *
 * try {
 *
 *  $connection = new Phalcon\Db\Adapter\Pdo\Mysql(array(
 *     'host' => '192.168.0.11',
 *     'username' => 'sigma',
 *     'password' => 'secret',
 *     'dbname' => 'blog',
 *     'port' => '3306',
 *  ));
 *
 *  $result = $connection->query("SELECT * FROM robots LIMIT 5");
 *  $result->setFetchMode(Phalcon\Db::FETCH_NUM);
 *  while ($robot = $result->fetch()) {
 *    print_r($robot);
 *  }
 *
 * } catch (Phalcon\Db\Exception $e) {
 *  echo $e->getMessage(), PHP_EOL;
 * }
 *
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/1.2.6/master/ext/db.c
 */
abstract class Db
{

    const FETCH_LAZY       = \Pdo::FETCH_LAZY;

    const FETCH_ASSOC      = \Pdo::FETCH_ASSOC;

    const FETCH_NAMED      = \Pdo::FETCH_NAMED;

    const FETCH_NUM        = \Pdo::FETCH_NUM;

    const FETCH_BOTH       = \Pdo::FETCH_BOTH;

    const FETCH_OBJ        = \Pdo::FETCH_OBJ;

    const FETCH_BOUND      = \Pdo::FETCH_BOUND;

    const FETCH_COLUMN     = \Pdo::FETCH_COLUMN;

    const FETCH_CLASS      = \Pdo::FETCH_CLASS;

    const FETCH_INTO       = \Pdo::FETCH_INTO;

    const FETCH_FUNC       = \Pdo::FETCH_FUNC;

    const FETCH_GROUP      = \Pdo::FETCH_GROUP;

    const FETCH_UNIQUE     = \Pdo::FETCH_UNIQUE;

    const FETCH_KEY_PAIR   = \Pdo::FETCH_KEY_PAIR;

    const FETCH_CLASSTYPE  = \Pdo::FETCH_CLASSTYPE;

    const FETCH_SERIALIZE  = \Pdo::FETCH_SERIALIZE;

    const FETCH_PROPS_LATE = \Pdo::FETCH_PROPS_LATE;

    /**
     * Enables/disables options in the Database component
     *
     * @param array $options
     * @throws DbException
     */
    public static function setup($options)
    {
        if (is_array($options) === false) {
            throw new DbException('Options must be an array');
        }

        if (isset($options['escapeSqlIdentifiers']) === true) {
            $GLOBALS['__phalcon_db__escape_identifiers'] = (bool) $options['escapeSqlIdentifiers'];
        }

        if (isset($options['forceCasting']) === true) {
            $GLOBALS['__phalcon_db__force_casting'] = (bool) $options['forceCasting'];
        }
    }

}
