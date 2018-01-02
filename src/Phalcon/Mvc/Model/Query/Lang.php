<?php

namespace Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\Query\Lang
 *
 * PHQL is implemented as a parser (written in C) that translates syntax in
 * that of the target RDBMS. It allows Phalcon to offer a unified SQL language to
 * the developer, while internally doing all the work of translating PHQL
 * instructions to the most optimal SQL instructions depending on the
 * RDBMS type associated with a model.
 *
 * To achieve the highest performance possible, we wrote a parser that uses
 * the same technology as SQLite. This technology provides a small in-memory
 * parser with a very low memory footprint that is also thread-safe.
 *
 * <code>
 * $intermediate = Phalcon\Mvc\Model\Query\Lang::parsePHQL("SELECT r.* FROM Robots r LIMIT 10");
 * </code>
 */
abstract class Lang
{

    /**
     * Parses a PHQL statement returning an intermediate representation (IR)
     *
     * @param $phql string
     * @throws Exception
     * @return string
     */
    public static function parsePHQL($phql)
    {
        if (!is_string($phql)){
            throw new Exception('Invalid parameter type.');
        }
        return phql_parse_phql($phql);
    }
}