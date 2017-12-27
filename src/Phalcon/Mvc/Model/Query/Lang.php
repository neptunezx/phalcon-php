<?php

namespace Phalcon\Mvc\Model\Query;

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

    const PHQL_SCANNER_RETCODE_EOF        = -1;
    const PHQL_SCANNER_RETCODE_ERR        = -2;
    const PHQL_SCANNER_RETCODE_IMPOSSIBLE = -3;
    const PHQL_T_IGNORE                   = 257;

    /* Literals & Identifiers */
    const PHQL_T_INTEGER    = 258;
    const PHQL_T_DOUBLE     = 259;
    const PHQL_T_STRING     = 260;
    const PHQL_T_IDENTIFIER = 265;
    const PHQL_T_HINTEGER   = 414;

    /* Operators */
    const PHQL_T_ADD               = '+';
    const PHQL_T_SUB               = '-';
    const PHQL_T_MUL               = '*';
    const PHQL_T_DIV               = '/';
    const PHQL_T_MOD               = '%';
    const PHQL_T_BITWISE_AND       = '&';
    const PHQL_T_BITWISE_OR        = '|';
    const PHQL_T_BITWISE_XOR       = '^';
    const PHQL_T_BITWISE_NOT       = '~';
    const PHQL_T_AND               = 266;
    const PHQL_T_OR                = 267;
    const PHQL_T_LIKE              = 268;
    const PHQL_T_ILIKE             = 275;
    const PHQL_T_AGAINST           = 276;
    const PHQL_T_DOT               = '.';
    const PHQL_T_COMMA             = 269;
    const PHQL_T_COLON             = ':';
    const PHQL_T_EQUALS            = '=';
    const PHQL_T_NOTEQUALS         = 270;
    const PHQL_T_NOT               = '!';
    const PHQL_T_LESS              = '<';
    const PHQL_T_LESSEQUAL         = 271;
    const PHQL_T_GREATER           = '>';
    const PHQL_T_GREATEREQUAL      = 272;
    const PHQL_T_PARENTHESES_OPEN  = '(';
    const PHQL_T_PARENTHESES_CLOSE = ')';

    /** Placeholders */
    const PHQL_T_NPLACEHOLDER = 273;
    const PHQL_T_SPLACEHOLDER = 274;
    const PHQL_T_BPLACEHOLDER = 277;

    /** Reserved words */
    const PHQL_T_UPDATE   = 300;
    const PHQL_T_SET      = 301;
    const PHQL_T_WHERE    = 302;
    const PHQL_T_DELETE   = 303;
    const PHQL_T_FROM     = 304;
    const PHQL_T_AS       = 305;
    const PHQL_T_INSERT   = 306;
    const PHQL_T_INTO     = 307;
    const PHQL_T_VALUES   = 308;
    const PHQL_T_SELECT   = 309;
    const PHQL_T_ORDER    = 310;
    const PHQL_T_BY       = 311;
    const PHQL_T_LIMIT    = 312;
    const PHQL_T_GROUP    = 313;
    const PHQL_T_HAVING   = 314;
    const PHQL_T_IN       = 315;
    const PHQL_T_ON       = 316;
    const PHQL_T_INNER    = 317;
    const PHQL_T_JOIN     = 318;
    const PHQL_T_LEFT     = 319;
    const PHQL_T_RIGHT    = 320;
    const PHQL_T_IS       = 321;
    const PHQL_T_NULL     = 322;
    const PHQL_T_NOTIN    = 323;
    const PHQL_T_CROSS    = 324;
    const PHQL_T_FULL     = 325;
    const PHQL_T_OUTER    = 326;
    const PHQL_T_ASC      = 327;
    const PHQL_T_DESC     = 328;
    const PHQL_T_OFFSET   = 329;
    const PHQL_T_DISTINCT = 330;
    const PHQL_T_BETWEEN  = 331;
    const PHQL_T_CAST     = 332;
    const PHQL_T_TRUE     = 333;
    const PHQL_T_FALSE    = 334;
    const PHQL_T_CONVERT  = 335;
    const PHQL_T_USING    = 336;
    const PHQL_T_ALL      = 337;
    const PHQL_T_FOR      = 338;

    /** Special Tokens */
    const PHQL_T_FCALL         = 350;
    const PHQL_T_NLIKE         = 351;
    const PHQL_T_STARALL       = 352;
    const PHQL_T_DOMAINALL     = 353;
    const PHQL_T_EXPR          = 354;
    const PHQL_T_QUALIFIED     = 355;
    const PHQL_T_ENCLOSED      = 356;
    const PHQL_T_NILIKE        = 357;
    const PHQL_T_RAW_QUALIFIED = 358;
    const PHQL_T_INNERJOIN     = 360;
    const PHQL_T_LEFTJOIN      = 361;
    const PHQL_T_RIGHTJOIN     = 362;
    const PHQL_T_CROSSJOIN     = 363;
    const PHQL_T_FULLJOIN      = 364;
    const PHQL_T_ISNULL        = 365;
    const PHQL_T_ISNOTNULL     = 366;
    const PHQL_T_MINUS         = 367;

    /** Postgresql Text Search Operators */
    const PHQL_T_TS_MATCHES          = 401;
    const PHQL_T_TS_OR               = 402;
    const PHQL_T_TS_AND              = 403;
    const PHQL_T_TS_NEGATE           = 404;
    const PHQL_T_TS_CONTAINS_ANOTHER = 405;
    const PHQL_T_TS_CONTAINS_IN      = 406;
    const PHQL_T_SUBQUERY            = 407;
    const PHQL_T_EXISTS              = 408;
    const PHQL_T_CASE                = 409;
    const PHQL_T_WHEN                = 410;
    const PHQL_T_ELSE                = 411;
    const PHQL_T_END                 = 412;
    const PHQL_T_THEN                = 413;
    const PHQL_T_WITH                = 415;

    /**
     * Parses a PHQL statement returning an intermediate representation (IR)
     *
     * @param string phql
     * @return string
     */
    public static function parsePHQL($phql)
    {
        return \Fend\Mvc\Model\Query\Lang::parsePHQL($phql);
        if (!is_string($phql)) {
            return null;
        }
        return phql_parse_phql($phql);
    }

}
