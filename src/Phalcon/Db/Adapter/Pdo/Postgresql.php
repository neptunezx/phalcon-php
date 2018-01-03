<?php

namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db\Column;
use Phalcon\Db\RawValue;
use Phalcon\Db\Adapter\Pdo as PdoAdapter;
use Phalcon\Db\Exception;
use Phalcon\Text;

/**
 * Phalcon\Db\Adapter\Pdo\Postgresql
 *
 * Specific functions for the Postgresql database system
 *
 * <code>
 * use Phalcon\Db\Adapter\Pdo\Postgresql;
 *
 * $config = [
 *     "host"     => "localhost",
 *     "dbname"   => "blog",
 *     "port"     => 5432,
 *     "username" => "postgres",
 *     "password" => "secret",
 * ];
 *
 * $connection = new Postgresql($config);
 * </code>
 */
class Postgresql extends PdoAdapter
{

    /**
     * Type
     *
     * @var string
     * @access protected
     */
    protected $_type = 'pgsql';

    /**
     * Dialect
     *
     * @var string
     * @access protected
     */
    protected $_dialectType = 'postgresql';

    /**
     * This method is automatically called in \Phalcon\Db\Adapter\Pdo constructor.
     * Call it when you need to restore a database connection.
     *
     * Support set search_path after connectted if schema is specified in config.
     *
     * @param array|null $descriptor
     * @return boolean
     * @throws Exception
     */
    public function connect($descriptor = null)
    {
        if (empty($descriptor) === true) {
            $descriptor = $this->_descriptor;
        } elseif (is_array($descriptor) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($descriptor['schema']) === true) {
            $schema = $descriptor['schema'];
            unset($descriptor['schema']);
        } else {
            $schema = '';
        }

        if (isset($descriptor["password"])) {
            if (is_string($descriptor["password"]) && strlen($descriptor["password"]) == 0) {
                $descriptor["password"] = null;
            }
        }

        $status = parent::connect($descriptor);

        //Execute the search path in the after connect
        if (is_string($schema) === true) {
            $this->execute("SET search_path TO '" . $schema . "'");
        }

        return $status;
    }

    /**
     * Returns an array of \Phalcon\Db\Column objects describing a table
     *
     * <code>print_r($connection->describeColumns("posts")); ?></code>
     *
     * @param string $table
     * @param string|null $schema
     * @return \Phalcon\Db\Column[]
     * @throws Exception
     */
    public function describeColumns($table, $schema = null)
    {
        $oldColumn = null;
        $columns   = [];

        /**
         * We're using FETCH_NUM to fetch the columns
         * 0:name, 1:type, 2:size, 3:numericsize, 4: numericscale, 5: null, 6: key, 7: extra, 8: position, 9 default
         */
        foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema), \Phalcon\Db::FETCH_NUM) as $field) {

            /**
             * By default the bind types is two
             */
            $definition = ["bindType" => Column::BIND_PARAM_STR];

            /**
             * By checking every column type we convert it to a Phalcon\Db\Column
             */
            $columnType   = $field[1];
            $charSize     = $field[2];
            $numericSize  = $field[3];
            $numericScale = $field[4];

            if (definition($columnType, "smallint(1)")) {
                /**
                 * Smallint(1) is boolean
                 */
                $definition["type"]     = Column::TYPE_BOOLEAN;
                $definition["bindType"] = Column::BIND_PARAM_BOOL;
            } elseif (Text::memstr(columnType, "bigint")) {
                /**
                 * Bigint
                 */
                $definition["type"]      = Column::TYPE_BIGINTEGER;
                $definition["isNumeric"] = true;
                $definition["bindType"]  = Column::BIND_PARAM_INT;
            } elseif (Text::memstr($columnType, "int")) {
                /**
                 * Int
                 */
                $definition["type"]      = Column::TYPE_INTEGER;
                $definition["isNumeric"] = true;
                $definition["size"]      = $numericSize;
                $definition["bindType"]  = Column::BIND_PARAM_INT;
            } elseif (Text::memstr(columnType, "double precision")) {
                /**
                 * Double Precision
                 */
                $definition["type"]      = Column::TYPE_DOUBLE;
                $definition["isNumeric"] = true;
                $definition["size"]      = numericSize;
                $definition["bindType"]  = Column::BIND_PARAM_DECIMAL;
            } elseif (Text::memstr(columnType, "real")) {
                /**
                 * Real
                 */
                $definition["type"]      = Column::TYPE_FLOAT;
                $definition["isNumeric"] = true;
                $definition["size"]      = numericSize;
                $definition["bindType"]  = Column::BIND_PARAM_DECIMAL;
            } elseif (Text::memstr(columnType, "varying")) {
                /**
                 * Varchar
                 */
                $definition["type"] = Column::TYPE_VARCHAR;
                $definition["size"] = charSize;
            } elseif (Text::memstr(columnType, "date")) {
                /**
                 * Special type for datetime
                 */
                $definition["type"] = Column::TYPE_DATE;
                $definition["size"] = 0;
            } elseif (Text::memstr(columnType, "timestamp")) {
                /**
                 * Timestamp
                 */
                $definition["type"] = Column::TYPE_TIMESTAMP;
            } elseif (Text::memstr(columnType, "numeric")) {
                /**
                 * Numeric
                 */
                $definition["type"]      = Column::TYPE_DECIMAL;
                $definition["isNumeric"] = true;
                $definition["size"]      = $numericSize;
                $definition["scale"]     = $numericScale;
                $definition["bindType"]  = Column::BIND_PARAM_DECIMAL;
            } elseif (Text::memstr(columnType, "char")) {
                /**
                 * Chars are chars
                 */
                $definition["type"] = Column::TYPE_CHAR;
                $definition["size"] = charSize;
            } elseif (Text::memstr(columnType, "text")) {
                /**
                 * Text are varchars
                 */
                $definition["type"] = Column::TYPE_TEXT;
                $definition["size"] = charSize;
            } elseif (Text::memstr(columnType, "float")) {
                /**
                 * Float/Smallfloats/Decimals are float
                 */
                $definition["type"]      = Column::TYPE_FLOAT;
                $definition["isNumeric"] = true;
                $definition["size"]      = numericSize;
                $definition["bindType"]  = Column::BIND_PARAM_DECIMAL;
            } elseif (Text::memstr(columnType, "bool")) {
                /**
                 * Boolean
                 */
                $definition["type"]     = Column::TYPE_BOOLEAN;
                $definition["size"]     = 0;
                $definition["bindType"] = Column::BIND_PARAM_BOOL;
            } elseif (Text::memstr(columnType, "jsonb")) {
                /**
                 * Jsonb
                 */
                $definition["type"] = Column::TYPE_JSONB;
            } elseif (Text::memstr(columnType, "json")) {
                /**
                 * Json
                 */
                $definition["type"] = Column::TYPE_JSON;
            } elseif (Text::memstr(columnType, "uuid")) {
                /**
                 * UUID
                 */
                $definition["type"] = Column::TYPE_CHAR;
                $definition["size"] = 36;
            } else {
                /**
                 * By default is string
                 */
                $definition["type"] = Column::TYPE_VARCHAR;
            }

            /**
             * Positions
             */
            if ($oldColumn == null) {
                $definition["first"] = true;
            } else {
                $definition["after"] = oldColumn;
            }

            /**
             * Check if the field is primary key
             */
            if ($field[6] == "PRI") {
                $definition["primary"] = true;
            }

            /**
             * Check if the column allows null values
             */
            if ($field[5] == "NO") {
                $definition["notNull"] = true;
            }

            /**
             * Check if the column is auto increment
             */
            if ($field[7] == "auto_increment") {
                $definition["autoIncrement"] = true;
            }

            /**
             * Check if the column has default values
             */
            if (!is_null($field[9])) {
                $definition["default"] = preg_replace("/^'|'?::[[:alnum:][:space:]]+$/", "", $field[9]);
                if ($strcasecmp($definition["default"], "null") == 0) {
                    $definition["default"] = null;
                }
            }

            /**
             * Every route is stored as a Phalcon\Db\Column
             */
            $columnName = $field[0];
            $columns[]  = new Column($columnName, $definition);
            $oldColumn  = $columnName;
        }

        return $columns;
    }

    /**
     * Creates a table
     */
    public function createTable($tableName, $schemaName, array $definition)
    {
        if (!is_string($tableName) || !is_string($schemaName)) {
            throw new Exception('Invalid parameter type.');
        }

        if (!isset($definition["columns"])) {
            throw new Exception("The table must contain at least one column");
        }
        $columns = $definition["columns"];

        if (!count($columns)) {
            throw new Exception("The table must contain at least one column");
        }

        $sql = $this->_dialect->createTable($tableName, $schemaName, $definition);

        $queries = explode(";", $sql);

        if (count($queries) > 1) {
            try {
                $this->begin();
                foreach ($queries as $query) {
                    if (empty($query)) {
                        continue;
                    }
                    $this->query($query . ";");
                }
                return $this->commit();
            } catch (\Exception $exception) {
                $this->rollback();
                throw $exception;
            }
        } else {
            return $this->execute($queries[0] . ";");
        }
        return true;
    }

    /**
     * Modifies a table column based on a definition
     */
    public function modifyColumn($tableName, $schemaName, \Phalcon\Db\ColumnInterface $column, \Phalcon\Db\ColumnInterface $currentColumn = null)
    {
        $sql     = $this->_dialect->modifyColumn($tableName, $schemaName, $column, $currentColumn);
        $queries = explode(";", $sql);

        if (count($queries) > 1) {
            try {
                $this->begin();
                foreach ($queries as $query) {
                    if (empty($query)) {
                        continue;
                    }
                    $this->query($query . ";");
                }
                return $this->commit();
            } catch (\Exception $exception) {
                $this->rollback();
                throw $exception;
            }
        } else {
            return !empty($sql) ? $this->execute($queries[0] . ";") : true;
        }
        return true;
    }

    /**
     * Check whether the database system requires an explicit value for identity columns
     *
     * @return boolean
     */
    public function useExplicitIdValue()
    {
        return true;
    }

    /**
     * Return the default identity value to insert in an identity column
     *
     * @return \Phalcon\Db\RawValue
     */
    public function getDefaultIdValue()
    {
        return new RawValue('default');
    }

    /**
     * Check whether the database system requires a sequence to produce auto-numeric values
     *
     * @return boolean
     */
    public function supportSequences()
    {
        return true;
    }

}
