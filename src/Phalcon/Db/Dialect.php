<?php

namespace Phalcon\Db;

use Phalcon\Kernel;

/**
 * Phalcon\Db\Dialect
 *
 * This is the base class to each database dialect. This implements
 * common methods to transform intermediate code into its RDBMS related syntax
 */
abstract class Dialect implements DialectInterface
{

    /**
     * Escape Char
     *
     * @var null
     * @access protected
     */
    protected $_escapeChar;

    /**
     * customFunctions
     *
     * @var null
     * @access protected
     */
    protected $_customFunctions;

    /**
     * Registers custom SQL functions
     * 
     * @param          $name
     * @param callable $customFunction
     * @return $this
     */
    public function registerCustomFunction($name, callable $customFunction)
    {
        $this->_customFunctions[$name] = $customFunction;
        return $this;
    }

    /**
     * Returns registered functions
     * 
     * @return array
     */
    public function getCustomFunctions()
    {
        return $this->_customFunctions;
    }

    /**
     * Escape Schema
     * 
     * @param string     $str
     * @param string|null $escapeChar
     * @return string
     */
    public final function escapeSchema($str, $escapeChar = null)
    {
        //TODO unkown func
        if (!(isset($GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS']) === true &&
            $GLOBALS['_PHALCON_DB_ESCAPE_IDENTIFIERS'] === true )) {
            return $str;
        }

        if ($escapeChar == "") {
            $escapeChar = (string) $this->_escapeChar;
        }

        return $escapeChar . trim($str, $escapeChar) . $escapeChar;
    }

    /**
     * Escape identifiers
     * 
     * @param string      $str
     * @param string|null $escapeChar
     * @return string
     */
    public final function escape($str, $escapeChar = null)
    {
        if (!Kernel::getGlobals("db.escape_identifiers")) {
            return $str;
        }

        if ($escapeChar == "") {
            $escapeChar = (string) $this->_escapeChar;
        }

        if (!strpos($str, ".")) {

            if ($escapeChar != "" && $str != "*") {
                return $escapeChar . str_replace(
                        $escapeChar, $escapeChar . $escapeChar, $str
                    ) . $escapeChar;
            }

            return $str;
        }

        $parts = (array) explode(".", trim($str, $escapeChar));

        $newParts = $parts;

        foreach ($parts as $key => $part) {
            if ($escapeChar == "" || $part == "" || $part == "*") {
                continue;
            }
            $newParts[$key] = $escapeChar . str_replace($escapeChar, $escapeChar . $escapeChar, $part) . $escapeChar;
        }

        return implode(".", $newParts);
    }

    /**
     * Generates the SQL for LIMIT clause
     *
     * <code>
     * $sql = $dialect->limit('SELECT * FROM robots', 10);
     * echo $sql; // SELECT * FROM robots LIMIT 10
     * </code>
     *
     * @param string    $sqlQuery
     * @param array|int $number
     * @return string
     * @throws Exception
     */
    public function limit($sqlQuery, $number)
    {
        if (is_string($sqlQuery) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_array($number)) {
            $sqlQuery .= " LIMIT " . $number[0];

            if (isset($number[1]) && strlen($number[1])) {
                $sqlQuery .= " OFFSET " . $number[1];
            }
            return $sqlQuery;
        }
        if (is_numeric($number) === true) {
            return $sqlQuery . ' LIMIT ' . (int) $number;
        }

        return $sqlQuery;
    }

    /**
     * Returns a SQL modified with a FOR UPDATE clause
     *
     * <code>
     * $sql = $dialect->forUpdate('SELECT * FROM robots');
     * echo $sql; // SELECT * FROM robots FOR UPDATE
     * </code>
     *
     * @param string $sqlQuery
     * @return string
     * @throws Exception
     */
    public function forUpdate($sqlQuery)
    {
        if (is_string($sqlQuery) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return $sqlQuery . ' FOR UPDATE';
    }

    /**
     * Returns a SQL modified with a LOCK IN SHARE MODE clause
     *
     * <code>
     * $sql = $dialect->sharedLock('SELECT * FROM robots');
     * echo $sql; // SELECT * FROM robots LOCK IN SHARE MODE
     * </code>
     *
     * @param string $sqlQuery
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    public function sharedLock($sqlQuery)
    {
        if (is_string($sqlQuery) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return $sqlQuery . ' LOCK IN SHARE MODE';
    }

    /**
     * Gets a list of columns with escaped identifiers
     *
     * <code>
     * echo $dialect->getColumnList(array('column1', 'column'));
     * </code>
     *
     * @param array $columnList
     * @return string
     * @throws Exception
     */
    public function getColumnList(array $columnList, $escapeChar = null, $bindCounts = null)
    {
        $strList = array();
        foreach ($columnList as $column) {
            //$strList[] = $escapeChar . $column . $escapeChar;
            $strList[] = $this->getSqlColumn($column, $escapeChar, $bindCounts);
        }

        return implode(', ', $strList);
    }

    /**
     * Resolve Column expressions
     * 
     * @param $column
     * @param string $escapeChar
     * @param $bindCounts
     *
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    public final function getSqlColumn($column, $escapeChar = null, $bindCounts = null)
    {
        if (!is_array($column)) {
            return $this->prepareQualified($column, null, $escapeChar);
        }

        if (!isset($column["type"])) {
            $columnField = $column[0];
            if (is_array($columnField)) {
                $columnExpression = [
                    "type"  => "scalar",
                    "value" => $columnField
                ];
            } elseif ($columnField == "*") {
                $columnExpression = [
                    "type" => "all"
                ];
            } else {
                $columnExpression = [
                    "type" => "qualified",
                    "name" => $columnField
                ];
            }
            /**
             * The index "1" is the domain column
             */
            $columnDomain = isset($column[1]) ? $column[1] : null;
            if ($columnDomain != "") {
                $columnExpression["domain"] = $columnDomain;
            }

            /**
             * The index "2" is the column alias
             */
            $columnAlias = isset($column[2]) ? $column[2] : null;
            if ($columnAlias != "") {
                $columnExpression["sqlAlias"] = $columnAlias;
            }
        } else {
            $columnExpression = $column;
        }

        $column = $this->getSqlExpression($columnExpression, $escapeChar, $bindCounts);

        /**
         * Escape alias and concatenate to value SQL
         */
        $columnAlias = isset($columnExpression["sqlAlias"]) ? $columnExpression["sqlAlias"] : (isset($columnExpression["alias"]) ? $columnExpression["alias"] : null);
        if ($columnAlias) {
            return $this->prepareColumnAlias($column, $columnAlias, $escapeChar);
        }

        return $this->prepareColumnAlias($column, null, $escapeChar);
    }

    /**
     * Transforms an intermediate representation for a expression into a database system valid expression
     *
     * @param array       $expression
     * @param string|null $escapeChar
     * @param null        $bindCounts
     * @return string
     * @throws Exception
     */
    public function getSqlExpression(array $expression, $escapeChar = null, $bindCounts = null)
    {
        if (isset($expression['type']) === false) {
            throw new Exception('Invalid SQL expression');
        }

        $type = $expression['type'];

        switch ($type) {
            /**
             * Resolve scalar column expressions
             */
            case "scalar":
                return $this->getSqlExpressionScalar($expression, $escapeChar, $bindCounts);

            /**
             * Resolve object expressions
             */
            case "object":
                return $this->getSqlExpressionObject($expression, $escapeChar, $bindCounts);

            /**
             * Resolve qualified expressions
             */
            case "qualified":
                return $this->getSqlExpressionQualified($expression, $escapeChar);

            /**
             * Resolve literal OR placeholder expressions
             */
            case "literal":
                return $expression["value"];

            case "placeholder":
                $times = isset($expression['times']) ? $expression['times'] : null;
                if ($times) {
                    $placeholders = [];
                    $rawValue     = $expression["rawValue"];
                    $value        = $expression["value"];
                    if (isset($bindCounts[$rawValue])) {
                        $times = $bindCounts[$rawValue];
                    }
                    for ($i = 1; $i <= $times; $i++) {
                        $placeholders[] = $value . ($i - 1);
                    }
                    return join(", ", $placeholders);
                }
                return $expression['value'];
            /**
             * Resolve binary operations expressions
             */
            case "binary-op":
                return $this->getSqlExpressionBinaryOperations($expression, $escapeChar, $bindCounts);

            /**
             * Resolve unary operations expressions
             */
            case "unary-op":
                return $this->getSqlExpressionUnaryOperations($expression, $escapeChar, $bindCounts);

            /**
             * Resolve parentheses
             */
            case "parentheses":
                return "(" . $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts) . ")";

            /**
             * Resolve function calls
             */
            case "functionCall":
                return $this->getSqlExpressionFunctionCall($expression, $escapeChar, $bindCounts);

            /**
             * Resolve lists
             */
            case "list":
                return $this->getSqlExpressionList($expression, $escapeChar, $bindCounts);

            /**
             * Resolve *
             */
            case "all":
                return $this->getSqlExpressionAll($expression, $escapeChar);

            /**
             * Resolve SELECT
             */
            case "select":
                return "(" . $this->select($expression["value"]) . ")";

            /**
             * Resolve CAST of values
             */
            case "cast":
                return $this->getSqlExpressionCastValue($expression, $escapeChar, $bindCounts);

            /**
             * Resolve CONVERT of values encodings
             */
            case "convert":
                return $this->getSqlExpressionConvertValue($expression, $escapeChar, $bindCounts);

            case "case":
                return $this->getSqlExpressionCase($expression, $escapeChar, $bindCounts);
        }

        //Expression type wasn't found
        throw new Exception("Invalid SQL expression type '" . $type . "'");
    }

    /**
     * Transform an intermediate representation for a schema/table into a database system valid expression
     *
     * @param array|string $table
     * @param string|null $escapeChar
     * @return string
     * @throws Exception
     */
    public function getSqlTable($table, $escapeChar = null)
    {

        if (is_array($table) === true) {
            //The index '0' is the table name
            $tableName = $table[0];

            /**
             * The index "1" is the schema name
             */
            $schemaName = isset($table[1]) ? $table[1] : null;

            /**
             * The index "2" is the table alias
             */
            $aliasName = isset($table[2]) ? $table[2] : null;

            return $this->prepareTable($tableName, $schemaName, $aliasName, $escapeChar);
        } elseif (is_string($table) === true) {

            return $this->escape($table, $escapeChar);
        } else {
            throw new Exception('Invalid parameter type.');
        }
    }

    /**
     * Builds a SELECT statement
     *
     * @param array $definition
     * @return string
     * @throws Exception
     */
    public function select($definition)
    {
        if (is_array($definition) === false) {
            throw new Exception('Invalid SELECT definition');
        }

        if (isset($definition['tables']) === false) {
            throw new Exception("The index 'tables' is required in the definition array");
        } else {
            $tables = $definition['tables'];
        }

        if (isset($definition['columns']) === false) {
            throw new Exception("The index 'columns' is required in the definition array");
        } else {
            $columns = $definition['columns'];
        }

        if (isset($definition["distinct"])) {
            if ($definition["distinct"]) {
                $sql = "SELECT DISTINCT";
            } else {
                $sql = "SELECT ALL";
            }
        } else {
            $sql = "SELECT";
        }

        $bindCounts = isset($definition["bindCounts"]) ? $definition["bindCounts"] : null;

        $escapeChar = $this->_escapeChar;

        /**
         * Resolve COLUMNS
         */
        $sql .= " " . $this->getColumnList($columns, $escapeChar, $bindCounts);

        /**
         * Resolve FROM
         */
        $sql .= " " . $this->getSqlExpressionFrom($tables, $escapeChar);

        /**
         * Resolve JOINs
         */
        if (isset($definition["joins"]) && $definition["joins"]) {
            $sql .= " " . $this->getSqlExpressionJoins($definition["joins"], $escapeChar, $bindCounts);
        }

        /**
         * Resolve WHERE
         */
        if (isset($definition["where"]) && $definition["where"]) {
            $sql .= " " . $this->getSqlExpressionWhere($definition["where"], $escapeChar, $bindCounts);
        }

        /**
         * Resolve GROUP BY
         */
        if (isset($definition["group"]) && $definition["group"]) {
            $sql .= " " . $this->getSqlExpressionGroupBy($definition["group"], $escapeChar);
        }

        /**
         * Resolve HAVING
         */
        if (isset($definition["having"]) && $definition["having"]) {
            $sql .= " " . $this->getSqlExpressionHaving($definition["having"], $escapeChar, $bindCounts);
        }

        /**
         * Resolve ORDER BY
         */
        if (isset($definition["order"]) && $definition["order"]) {
            $sql .= " " . $this->getSqlExpressionOrderBy($definition["order"], $escapeChar, $bindCounts);
        }

        /**
         * Resolve LIMIT
         */
        if (isset($definition["limit"]) && $definition["limit"]) {
            $sql = $this->getSqlExpressionLimit(["sql" => $sql, "value" => $definition["limit"]], $escapeChar, $bindCounts);
        }

        /**
         * Resolve FOR UPDATE
         */
        if (isset($definition["forUpdate"]) && $definition["forUpdate"]) {
            $sql .= " FOR UPDATE";
        }

        return $sql;
    }

    /**
     * Checks whether the platform supports savepoints
     *
     * @return boolean
     */
    public function supportsSavepoints()
    {
        return true;
    }

    /**
     * Checks whether the platform supports releasing savepoints.
     *
     * @return boolean
     */
    public function supportsReleaseSavepoints()
    {
        return $this->supportsSavepoints();
    }

    /**
     * Generate SQL to create a new savepoint
     *
     * @param string $name
     * @return string
     * @throws Exception
     */
    public function createSavepoint($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return 'SAVEPOINT ' . $name;
    }

    /**
     * Generate SQL to release a savepoint
     *
     * @param string $name
     * @return string
     * @throws Exception
     */
    public function releaseSavepoint($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return 'RELEASE SAVEPOINT ' . $name;
    }

    /**
     * Generate SQL to rollback a savepoint
     *
     * @param string $name
     * @return string
     * @throws Exception
     */
    public function rollbackSavepoint($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * Resolve Column expressions
     * 
     * @param array        $expression
     * @param string|null  $escapeChar
     * @param string|null  $bindCounts
     * @return null|string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionScalar($expression, $escapeChar = null, $bindCounts = null)
    {
        if (is_array($expression) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($expression["column"])) {
            return $this->getSqlColumn($expression["column"]);
        }
        $value = isset($expression["value"]) ? $expression["value"] : null;
        if ($value) {
            throw new Exception("Invalid SQL expression");
        }

        if (is_array($value)) {
            return $this->getSqlExpression($value, $escapeChar, $bindCounts);
        }

        return $value;
    }

    /**
     * Resolve object expressions
     * 
     * @param array     $expression
     * @param string|null $escapeChar
     * @param string|null $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionObject(array $expression, $escapeChar = null, $bindCounts = null)
    {
        $objectExpression = [
            "type" => "all"
        ];

        $domain = isset($expression["column"]) ? $expression["column"] :
            (isset($expression["domain"]) ? $expression["domain"] : null);
        if ($domain != "") {
            $objectExpression["domain"] = $domain;
        }

        return $this->getSqlExpression($objectExpression, $escapeChar, $bindCounts);
    }

    /**
     * Resolve qualified expressions
     * @param array       $expression
     * @param string|null $escapeChar
     *
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionQualified(array $expression, $escapeChar = null)
    {
        $column = $expression["name"];

        /**
         * A domain could be a table/schema
         */
        $domain = isset($expression["domain"]) ? $expression["domain"] : null;

        if (!$domain) {
            $domain = null;
        }

        return $this->prepareQualified($column, $domain, $escapeChar);
    }

    /**
     * Resolve binary operations expressions
     * @param array        $expression
     * @param string|null  $escapeChar
     * @param string|null         $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionBinaryOperations(array $expression, $escapeChar = null, $bindCounts = null)
    {
        $left  = $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts);
        $right = $this->getSqlExpression($expression["right"], $escapeChar, $bindCounts);

        return $left . " " . $expression["op"] . " " . $right;
    }

    /**
     * Resolve unary operations expressions
     */

    /**
     * @param array          $expression
     * @param string|null    $escapeChar
     * @param string|null    $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionUnaryOperations(array $expression, $escapeChar = null, $bindCounts = null)
    {
        /**
         * Some unary operators use the left operand...
         */
        $left = isset($expression["left"]) ? $expression["left"] : null;
        if ($left) {
            return $this->getSqlExpression($left, $escapeChar, $bindCounts) . " " . $expression["op"];
        }

        /**
         * ...Others use the right operand
         */
        $right = isset($expression["right"]) ? $expression["right"] : null;
        if ($right) {
            return $this->getSqlExpression($right, $escapeChar, $bindCounts) . " " . $expression["op"];
        }

        throw new Exception("Invalid SQL-unary expression");
    }

    /**
     * Resolve function calls
     * 
     * @param array      $expression
     * @param null $escapeChar
     * @param      $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionFunctionCall(array $expression, $escapeChar = null, $bindCounts)
    {
        $name = $expression["name"];

        if (isset($this->_customFunctions[$name])) {
            return $this->_customFunctions[$name]($this, $expression, $escapeChar);
        }

        if (isset($expression["arguments"]) && is_array($expression["arguments"])) {
            $arguments = $this->getSqlExpression([
                "type"        => "list",
                "parentheses" => false,
                "value"       => $expression["arguments"]
                ], $escapeChar, $bindCounts);

            if (isset($expression["distinct"]) && $expression["distinct"]) {
                return $name . "(DISTINCT " . $arguments . ")";
            }

            return $name . "(" . $arguments . ")";
        }

        return $name . "()";
    }

    /**
     * Resolve Lists
     * 
     * @param array         $expression
     * @param string|null   $escapeChar
     * @param string|null   $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionList(array $expression, $escapeChar = null, $bindCounts = null)
    {
        $items     = [];
        $separator = ", ";

        if (isset($expression["separator"])) {
            $separator = $expression["separator"];
        }
        $values = isset($expression[0]) ? $expression[0] : isset($expression['value']) ? $expression['value'] : null;
        if (is_array($values)) {
            foreach ($values as $item) {
                $items[] = $this->getSqlExpression(
                    $item, $escapeChar, $bindCounts
                );
            }

            if (isset($expression["parentheses"]) && $expression["parentheses"] === false) {
                return join($separator, $items);
            }

            return "(" . join($separator, $items) . ")";
        }

        throw new Exception("Invalid SQL-list expression");
    }

    /**
     * Resolve *
     * 
     * @param      $expression
     * @param null $escapeChar
     * @return mixed
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionAll(array $expression, $escapeChar = null)
    {
        if (is_array($expression) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $domain = isset($expression["domain"]) ? $expression["domain"] : null;

        return $this->prepareQualified("*", $domain, $escapeChar);
    }

    /**
     * Resolve CAST of values
     * @param array $expression
     * @param string|null $escapeChar
     * @param string|null $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionCastValue(array $expression, $escapeChar = null, $bindCounts = null)
    {
        $left  = $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts);
        $right = $this->getSqlExpression($expression["right"], $escapeChar, $bindCounts);

        return "CAST(" . $left . " AS " . $right . ")";
    }

    /**
     * Resolve CONVERT of values encodings
     * @param array       $expression
     * @param string|null $escapeChar
     * @param string|null $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionConvertValue(array $expression, $escapeChar = null, $bindCounts = null)
    {
        $left  = $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts);
        $right = $this->getSqlExpression($expression["right"], $escapeChar, $bindCounts);

        return "CONVERT(" . $left . " USING " . $right . ")";
    }

    /**
     * Resolve CASE expressions
     * @param array      $expression
     * @param string|null $escapeChar
     * @param string|null $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionCase(array $expression, $escapeChar = null, $bindCounts = null)
    {
        $sql = "CASE " . $this->getSqlExpression($expression["expr"], $escapeChar, $bindCounts);

        foreach ($expression["when-clauses"] as $whenClause) {
            if ($whenClause["type"] == "when") {
                $sql .= " WHEN " .
                    $this->getSqlExpression($whenClause["expr"], $escapeChar, $bindCounts) .
                    " THEN " .
                    $this->getSqlExpression($whenClause["then"], $escapeChar, $bindCounts);
            } else {
                $sql .= " ELSE " . $this->getSqlExpression($whenClause["expr"], $escapeChar, $bindCounts);
            }
        }

        return $sql . " END";
    }

    /**
     * Resolve a FROM clause
     * 
     * @param array|string  $expression
     * @param string|null   $escapeChar
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionFrom($expression, $escapeChar = null)
    {
        if (is_array($expression)) {

            $tables = [];
            foreach ($expression as $table) {
                $tables[] = $this->getSqlTable($table, $escapeChar);
            }
            $tables = join(", ", $tables);
        } else {
            $tables = $expression;
        }

        return "FROM " . $tables;
    }

    /**
     * Resolve a JOINs clause
     */

    /**
     * Resolve a JOINs clause
     * 
     * @param array     $expression
     * @param string|null $escapeChar
     * @param string|null $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionJoins($expression, $escapeChar = null, $bindCounts = null)
    {
        $sql = "";
        foreach ($expression as $join) {
            if (!empty($join["conditions"])) {
                if (!isset($join['conditions'][0])) {
                    $joinCondition = $this->getSqlExpression($join["conditions"], $escapeChar, $bindCounts);
                } else {
                    $joinCondition = [];
                    foreach ($join['conditions'] as $condition) {
                        $joinCondition[] = $this->getSqlExpression($condition, $escapeChar, $bindCounts);
                    }
                    $joinCondition = join(" AND ", $joinCondition);
                }
            } else {
                $joinCondition = 1;
            }
            $joinType = isset($join['type']) ? $join['type'] : "";
            if ($joinType) {
                $joinType .= " ";
            }

            $joinTable = $this->getSqlTable($join["source"], $escapeChar);

            $sql .= " " . $joinType . "JOIN " . $joinTable . " ON " . $joinCondition;
        }

        return $sql;
    }

    /**
     * Resolve a WHERE clause
     * 
     * @param      $expression
     * @param string|null $escapeChar
     * @param string|null $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionWhere($expression, $escapeChar = null, $bindCounts = null)
    {
        if (is_array($expression)) {
            $whereSql = $this->getSqlExpression($expression, $escapeChar, $bindCounts);
        } else {
            $whereSql = $expression;
        }
        return "WHERE " . $whereSql;
    }

    /**
     * Resolve a GROUP BY clause
     * 
     * @param      $expression
     * @param null $escapeChar
     * @param null $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionGroupBy($expression, $escapeChar = null, $bindCounts = null)
    {

        if (is_array($expression)) {

            $fields = [];
            foreach ($expression as $field) {
                if (!is_array($field)) {
                    throw new Exception("Invalid SQL-GROUP-BY expression");
                }
                $fields[] = $this->getSqlExpression($field, $escapeChar, $bindCounts);
            }
            $fields = join(", ", $fields);
        } else {
            $fields = $expression;
        }

        return "GROUP BY " . $fields;
    }

    /**
     * Resolve a HAVING clause
     * 
     * @param array       $expression
     * @param string|null $escapeChar
     * @param null        $bindCounts
     * @return string
     * @throws  \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionHaving(array $expression, $escapeChar = null, $bindCounts = null)
    {
        return "HAVING " . $this->getSqlExpression($expression, $escapeChar, $bindCounts);
    }

    /**
     * Resolve an ORDER BY clause
     * 
     * @param             $expression
     * @param string|null $escapeChar
     * @param null        $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionOrderBy($expression, $escapeChar = null, $bindCounts = null)
    {
        if (is_array($expression)) {
            $fields = [];
            foreach ($expression as $field) {
                if (!is_array($field)) {
                    throw new Exception("Invalid SQL-ORDER-BY expression");
                }
                $fieldSql = $this->getSqlExpression($field[0], $escapeChar, $bindCounts);

                /**
                 * In the numeric 1 position could be a ASC/DESC clause
                 */
                if (isset($field[1]) && $field[1] != "") {
                    $fieldSql .= " " . $field[1];
                }
                $fields[] = $fieldSql;
            }
            $fields = join(", ", $fields);
        } else {
            $fields = $expression;
        }
        return "ORDER BY " . $fields;
    }

    /**
     * Resolve a LIMIT clause
     * 
     * @param array       $expression
     * @param string|null $escapeChar
     * @param null        $bindCounts
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected final function getSqlExpressionLimit($expression, $escapeChar = null, $bindCounts = null)
    {
        $sql    = "";
        $offset = null;
        $value  = $expression["value"];

        if (isset($expression["sql"])) {
            $sql = $expression["sql"];
        }

        if (is_array($value)) {

            if (is_array($value["number"])) {
                $limit = $this->getSqlExpression(
                    $value["number"], $escapeChar, $bindCounts
                );
            } else {
                $limit = $value["number"];
            }

            /**
             * Check for an OFFSET condition
             */
            if (isset($value["offset"]) && is_array($value["offset"])) {
                $offset = $this->getSqlExpression(
                    $offset, $escapeChar, $bindCounts
                );
            }
        } else {
            $limit = $value;
        }

        return $this->limit($sql, [$limit, $offset]);
    }

    /**
     * Prepares column for this RDBMS
     * 
     * @param string       $qualified
     * @param string|null $alias
     * @param string|null $escapeChar
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected function prepareColumnAlias($qualified, $alias = null, $escapeChar = null)
    {
        if (is_string($qualified) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if ($alias != "") {
            return $qualified . " AS " . $this->escape($alias, $escapeChar);
        }
        return $qualified;
    }

    /**
     * Prepares table for this RDBMS
     */

    /**
     * Prepares table for this RDBMS
     * 
     * @param string     $table
     * @param string|null $schema
     * @param string|null $alias
     * @param string|null $escapeChar
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected function prepareTable($table, $schema = null, $alias = null, $escapeChar = null)
    {
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $table = $this->escape($table, $escapeChar);

        /**
         * Schema
         */
        if ($schema != "") {
            $table = $this->escapeSchema($schema, $escapeChar) . "." . $table;
        }

        /**
         * Alias
         */
        if ($alias != "") {
            $table = $table . " AS " . $this->escape($alias, $escapeChar);
        }
        return $table;
    }

    /**
     * Prepares qualified for this RDBMS
     * 
     * @param string      $column
     * @param string|null $domain
     * @param string|null $escapeChar
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    protected function prepareQualified($column, $domain = null, $escapeChar = null)
    {
        codecept_debug('$column = ' . json_encode($column));
        if (is_string($column) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($domain != "") {
            return $this->escape($domain . "." . $column, $escapeChar);
        }

        return $this->escape($column, $escapeChar);
    }

}
