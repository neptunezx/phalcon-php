<?php

namespace Phalcon\Db\Adapter\Pdo;

use \Phalcon\Db;
use \Phalcon\Db\Column;
use \Phalcon\Db\Index;
use \Phalcon\Db\Reference;
use \Phalcon\Db\IndexInterface;
use \Phalcon\Db\Adapter\Pdo as PdoAdapter;
use \Phalcon\Application\Exception;
use \Phalcon\Db\ReferenceInterface;
use Phalcon\Text;

/**
 * Phalcon\Db\Adapter\Pdo\Mysql
 *
 * Specific functions for the Mysql database system
 *
 * <code>
 *
 * 	$config = array(
 * 		"host" => "192.168.0.11",
 * 		"dbname" => "blog",
 * 		"port" => 3306,
 * 		"username" => "sigma",
 * 		"password" => "secret"
 * 	);
 *
 * 	$connection = new Phalcon\Db\Adapter\Pdo\Mysql($config);
 *
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/adapter/pdo/mysql.c
 */
class Mysql extends PdoAdapter
{

    /**
     * Type
     *
     * @var string
     * @access protected
     */
    protected $_type = 'mysql';

    /**
     * Dialect Type
     *
     * @var string
     * @access protected
     */
    protected $_dialectType = 'Mysql';

    /**
     * Returns an array of \Phalcon\Db\Column objects describing a table
     *
     * <code>
     * print_r($connection->describeColumns("posts")); ?>
     * </code>
     *
     * @param string $table
     * @param string|null $schema
     * @return \Phalcon\Db\Column[]
     * @throws \Phalcon\Application\Exception
     * @throws \Phalcon\Db\Exception
     */
    public function describeColumns($table, $schema = null)
    {
        $oldColumn   = null;
        $sizePattern = "#\\(([0-9]+)(?:,\\s*([0-9]+))*\\)#";

        $columns = [];

        /**
         * Get the SQL to describe a table
         * We're using FETCH_NUM to fetch the columns
         * Get the describe
         * Field Indexes: 0:name, 1:type, 2:not null, 3:key, 4:default, 5:extra
         */
        foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema), Db::FETCH_NUM) as $field) {

            /**
             * By default the bind types is two
             */
            $definition = ["bindType" => Column::BIND_PARAM_STR];

            /**
             * By checking every column type we convert it to a Phalcon\Db\Column
             */
            $columnType = $field[1];

            if (Text::memstr($columnType, "enum")) {
                /**
                 * Enum are treated as char
                 */
                $definition["type"] = Column::TYPE_CHAR;
            } elseif (Text::memstr($columnType, "bigint")) {
                /**
                 * Smallint/Bigint/Integers/Int are int
                 */
                $definition["type"]      = Column::TYPE_BIGINTEGER;
                $definition["isNumeric"] = true;
                $definition["bindType"]  = Column::BIND_PARAM_INT;
            } elseif (Text::memstr($columnType, "int")) {
                /**
                 * Smallint/Bigint/Integers/Int are int
                 */
                $definition["type"]      = Column::TYPE_INTEGER;
                $definition["isNumeric"] = true;
                $definition["bindType"]  = Column::BIND_PARAM_INT;
            } elseif (Text::memstr($columnType, "varchar")) {
                /**
                 * Varchar are varchars
                 */
                $definition["type"] = Column::TYPE_VARCHAR;
            } elseif (Text::memstr($columnType, "datetime")) {
                /**
                 * Special type for datetime
                 */
                $definition["type"] = Column::TYPE_DATETIME;
            } elseif (Text::memstr($columnType, "char")) {
                /**
                 * Chars are chars
                 */
                $definition["type"] = Column::TYPE_CHAR;
            } elseif (Text::memstr($columnType, "date")) {
                /**
                 * Date are dates
                 */
                $definition["type"] = Column::TYPE_DATE;
            } elseif (Text::memstr($columnType, "timestamp")) {
                /**
                 * Timestamp are dates
                 */
                $definition["type"] = Column::TYPE_TIMESTAMP;
            } elseif (Text::memstr($columnType, "text")) {
                /**
                 * Text are varchars
                 */
                $definition["type"] = Column::TYPE_TEXT;
            } elseif (Text::memstr($columnType, "decimal")) {
                /**
                 * Decimals are floats
                 */
                $definition["type"]      = Column::TYPE_DECIMAL;
                $definition["isNumeric"] = true;
                $definition["bindType"]  = Column::BIND_PARAM_DECIMAL;
            } elseif (Text::memstr($columnType, "double")) {
                /**
                 * Doubles
                 */
                $definition["type"]      = Column::TYPE_DOUBLE;
                $definition["isNumeric"] = true;
                $definition["bindType"]  = Column::BIND_PARAM_DECIMAL;
            } elseif (Text::memstr($columnType, "float")) {
                /**
                 * Float/Smallfloats/Decimals are float
                 */
                $definition["type"]      = Column::TYPE_FLOAT;
                $definition["isNumeric"] = true;
                $definition["bindType"]  = Column::BIND_PARAM_DECIMAL;
            } elseif (Text::memstr($columnType, "bit")) {
                /**
                 * Boolean
                 */
                $definition["type"]     = Column::TYPE_BOOLEAN;
                $definition["bindType"] = Column::BIND_PARAM_BOOL;
            } elseif (Text::memstr($columnType, "tinyblob")) {
                /**
                 * Tinyblob
                 */
                $definition["type"]     = Column::TYPE_TINYBLOB;
                $definition["bindType"] = Column::BIND_PARAM_BOOL;
            } elseif (Text::memstr($columnType, "mediumblob")) {
                /**
                 * Mediumblob
                 */
                $definition["type"] = Column::TYPE_MEDIUMBLOB;
            } elseif (Text::memstr($columnType, "longblob")) {
                /**
                 * Longblob
                 */
                $definition["type"] = Column::TYPE_LONGBLOB;
            } elseif (Text::memstr($columnType, "blob")) {
                /**
                 * Blob
                 */
                $definition["type"] = Column::TYPE_BLOB;
            } else {
                /**
                 * By default is string
                 */
                $definition["type"] = Column::TYPE_VARCHAR;
            }

            /**
             * If the column type has a parentheses we try to get the column size from it
             */
            if (Text::memstr($columnType, "(")) {
                $matches = null;
                if (preg_match($sizePattern, $columnType, $matches)) {
                    if (!empty($matches[1])) {
                        $definition["size"] = (int) $matches[1];
                    }
                    if (!empty($matches[2])) {
                        $definition["scale"] = (int) $matches[2];
                    }
                }
            }

            /**
             * Check if the column is unsigned, only MySQL support this
             */
            if (Text::memstr($columnType, "unsigned")) {
                $definition["unsigned"] = true;
            }

            /**
             * Positions
             */
            if ($oldColumn == null) {
                $definition["first"] = true;
            } else {
                $definition["after"] = $oldColumn;
            }

            /**
             * Check if the field is primary key
             */
            if ($field[3] == "PRI") {
                $definition["primary"] = true;
            }

            /**
             * Check if the column allows null values
             */
            if ($field[2] == "NO") {
                $definition["notNull"] = true;
            }

            /**
             * Check if the column is auto increment
             */
            if ($field[5] == "auto_increment") {
                $definition["autoIncrement"] = true;
            }

            /**
             * Check if the column is default values
             */
            if ($field[4] !== null) {
                $definition["default"] = $field[4];
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
     * Lists table indexes
     *
     * <code>
     * print_r(
     *     $connection->describeIndexes("robots_parts")
     * );
     * </code>
     * @param string      $table
     * @param null|string $schema
     *
     * @return \Phalcon\Db\Index[]
     * @throws \Phalcon\Db\Exception
     */
    public function describeIndexes($table, $schema = null)
    {

        $indexes = [];
        $dialect = $this->_dialect;

        //Get the SQL to describe a table
        $sql = $dialect->describeIndexes($table, $schema);

        //Get the describe
        $describe = $this->fetchAll($sql, Db::FETCH_ASSOC);
        foreach ($describe as $index) {
            $keyName   = $index["Key_name"];
            $indexType = $index["Index_type"];

            if (!isset($indexes[$keyName])) {
                $indexes[$keyName] = [];
            }

            if (!isset($indexes[$keyName]["columns"])) {
                $columns = [];
            } else {
                $columns = $indexes[$keyName]["columns"];
            }

            $columns[]                    = $index["Column_name"];
            $indexes[$keyName]["columns"] = $columns;
            if ($keyName == "PRIMARY") {
                $indexes[$keyName]["type"] = "PRIMARY";
            } elseif ($indexType == "FULLTEXT") {
                $indexes[$keyName]["type"] = "FULLTEXT";
            } elseif ($index["Non_unique"] == 0) {
                $indexes[$keyName]["type"] = "UNIQUE";
            } else {
                $indexes[$keyName]["type"] = null;
            }
        }
        $indexObjects = [];
        foreach ($indexes as $name => $value) {
            $indexObjects[$name] = new Index($name, $value["columns"], $value["type"]);
        }
        return $indexObjects;
    }

    /**
     * Lists table references
     *
     * <code>
     * print_r(
     *     $connection->describeReferences("robots_parts")
     * );
     * @param string $table
     * @param null   $schema
     *
     * @return \Phalcon\Db\Reference[]
     * @throws \Phalcon\Db\Exception
     */
    public function describeReferences($table, $schema = null)
    {
        $references = [];

        //Get the describe
        $describe = $this->fetchAll($this->_dialect->describeReferences($table, $schema), Db::FETCH_NUM);

        foreach ($describe as $reference) {
            $constraintName = $reference[2];

            if (!isset($references[$constraintName])) {
                $referencedSchema  = $reference[3];
                $referencedTable   = $reference[4];
                $referenceUpdate   = $reference[6];
                $referenceDelete   = $reference[7];
                $columns           = [];
                $referencedColumns = [];
            } else {
                $referencedSchema  = $references[$constraintName]["referencedSchema"];
                $referencedTable   = $references[$constraintName]["referencedTable"];
                $columns           = $references[$constraintName]["columns"];
                $referencedColumns = $references[$constraintName]["referencedColumns"];
                $referenceUpdate   = $references[$constraintName]["onUpdate"];
                $referenceDelete   = $references[$constraintName]["onDelete"];
            }

            $columns[]                   = $reference[1];
            $referencedColumns[]         = $reference[5];
            $references[$constraintName] = [
                "referencedSchema"  => $referencedSchema,
                "referencedTable"   => $referencedTable,
                "columns"           => $columns,
                "referencedColumns" => $referencedColumns,
                "onUpdate"          => $referenceUpdate,
                "onDelete"          => $referenceDelete
            ];
        }

        $referenceObjects = [];
        foreach ($references as $name => $arrayReference) {
            $referenceObjects[$name] = new Reference($name, [
                "referencedSchema"  => $arrayReference["referencedSchema"],
                "referencedTable"   => $arrayReference["referencedTable"],
                "columns"           => $arrayReference["columns"],
                "referencedColumns" => $arrayReference["referencedColumns"],
                "onUpdate"          => $arrayReference["onUpdate"],
                "onDelete"          => $arrayReference["onDelete"]
            ]);
        }

        return $referenceObjects;
    }

    /**
     * Adds a foreign key to a table
     */

    /**
     * @param string                         $tableName
     * @param string                         $schemaName
     * @param \Phalcon\Db\ReferenceInterface $reference
     *
     * @return bool
     * @throws \Exception
     */
    public function addForeignKey($tableName, $schemaName, $reference)
    {


        $foreignKeyCheck = $this->prepare($this->_dialect->getForeignKeyChecks());
        if (!$foreignKeyCheck->execute()) {
            throw new Exception("DATABASE PARAMETER 'FOREIGN_KEY_CHECKS' HAS TO BE 1");
        }

        return $this->execute($this->_dialect->addForeignKey($tableName, $schemaName, $reference));
    }

}
