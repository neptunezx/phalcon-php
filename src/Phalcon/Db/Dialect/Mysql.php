<?php

namespace Phalcon\Db\Dialect;

use \Phalcon\Db\Dialect;
use \Phalcon\Db\DialectInterface;
use \Phalcon\Db\ColumnInterface;
use \Phalcon\Db\IndexInterface;
use \Phalcon\Db\ReferenceInterface;
use \Phalcon\Db\Exception;
use Phalcon\Db\Column;
use Phalcon\Text;

/**
 * Phalcon\Db\Dialect\Mysql
 *
 * Generates database specific SQL for the MySQL RBDM
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/dialect/mysql.c
 */
class Mysql extends Dialect implements DialectInterface
{

    /**
     * Escape Char
     *
     * @var string
     * @access protected
     */
    protected $_escapeChar = '`';

    /**
     * Gets the column name in MySQL
     *
     * @param \Phalcon\Db\ColumnInterface $column
     * @return string
     * @throws Exception
     */
    public function getColumnDefinition(ColumnInterface $column)
    {
        if (is_object($column) === false ||
            $column instanceof ColumnInterface === false) {
            throw new Exception('Column definition must be an object compatible with Phalcon\\Db\\ColumnInterface');
        }

        $columnSql = "";

        $size = $column->getSize();

        $type = $column->getType();

        if (is_string($type)) {
            $columnSql .= $type;
            $type      = $column->getTypeReference();
        }

        /* switch ($type) {
          case 0:
          return 'INT(' . $size . ')' . ($column->isUnsigned() === true ? ' UNSIGNED' : '');
          break;
          case 1:
          return 'DATE';
          break;
          case 2:
          return 'VARCHAR(' . $size . ')';
          break;
          case 3:
          return 'DECIMAL(' . $size . ',' . $column->getScale() . ')' .
          ($column->isUnsigned() === true ? ' UNSIGNED' : '');
          break;
          case 4:
          return 'DATETIME';
          break;
          case 5:
          return 'CHAR(' . $size . ')';
          break;
          case 6:
          return 'TEXT';
          break;
          case 7:
          $columnSql = 'FLOAT';

          $scale = $column->getScale();
          if ($size == true) {
          $columnSql .= '(' . $size;
          if ($scale == true) {
          $columnSql .= ',' . $scale . ')';
          } else {
          $columnSql .= ')';
          }
          }

          if ($column->isUnsigned() === true) {
          $columnSql .= ' UNSIGNED';
          }
          return $columnSql;
          break;
          case 8:
          return 'TINYINT(1)';
          break;
          default:
          throw new Exception('Unrecognized MySQL data type');
          break;
          } */

        switch ($type) {
            case Column::TYPE_INTEGER:
                if (empty($columnSql)) {
                    $columnSql .= "INT";
                }
                $columnSql .= "(" . $column->getSize() . ")";
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;

            case Column::TYPE_DATE:
                if (empty($columnSql)) {
                    $columnSql .= "DATE";
                }
                break;
            case Column::TYPE_VARCHAR:
                if (empty($columnSql)) {
                    $columnSql .= "VARCHAR";
                }
                $columnSql .= "(" . $column->getSize() . ")";
                break;

            case Column::TYPE_DECIMAL:
                if (empty($columnSql)) {
                    $columnSql .= "DECIMAL";
                }
                $columnSql .= "(" . $column->getSize() . ","
                    . $column->getScale() . ")";
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;

            case Column::TYPE_DATETIME:
                if (empty($columnSql)) {
                    $columnSql .= "DATETIME";
                }
                break;

            case Column::TYPE_TIMESTAMP:
                if (empty($columnSql)) {
                    $columnSql .= "TIMESTAMP";
                }
                break;

            case Column::TYPE_CHAR:
                if (empty($columnSql)) {
                    $columnSql .= "CHAR";
                }
                $columnSql .= "(" . $column->getSize() . ")";
                break;

            case Column::TYPE_TEXT:
                if (empty($columnSql)) {
                    $columnSql .= "TEXT";
                }
                break;

            case Column::TYPE_BOOLEAN:
                if (empty($columnSql)) {
                    $columnSql .= "TINYINT(1)";
                }
                break;

            case Column::TYPE_FLOAT:
                if (empty($columnSql)) {
                    $columnSql .= "FLOAT";
                }
                $size = $column->getSize();
                if ($size) {
                    $scale = $column->getScale();
                    if ($scale) {
                        $columnSql .= "(" . $size . "," . $scale . ")";
                    } else {
                        $columnSql .= "(" . $size . ")";
                    }
                }
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;

            case Column::TYPE_DOUBLE:
                if (empty($columnSql)) {
                    $columnSql .= "DOUBLE";
                }
                $size = $column->getSize();
                if ($size) {
                    $scale     = $column->getScale();
                    $columnSql .= "(" . $size;
                    if ($scale) {
                        $columnSql .= "," . $scale . ")";
                    } else {
                        $columnSql .= ")";
                    }
                }
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;

            case Column::TYPE_BIGINTEGER:
                if (empty($columnSql)) {
                    $columnSql .= "BIGINT";
                }
                $scale = $column->getSize();
                if ($scale) {
                    $columnSql .= "(" . $column->getSize() . ")";
                }
                if ($column->isUnsigned()) {
                    $columnSql .= " UNSIGNED";
                }
                break;

            case Column::TYPE_TINYBLOB:
                if (empty($columnSql)) {
                    $columnSql .= "TINYBLOB";
                }
                break;

            case Column::TYPE_BLOB:
                if (empty($columnSql)) {
                    $columnSql .= "BLOB";
                }
                break;

            case Column::TYPE_MEDIUMBLOB:
                if (empty($columnSql)) {
                    $columnSql .= "MEDIUMBLOB";
                }
                break;

            case Column::TYPE_LONGBLOB:
                if (empty($columnSql)) {
                    $columnSql .= "LONGBLOB";
                }
                break;
            default:
                if (empty($columnSql)) {
                    throw new Exception("Unrecognized MySQL data type at column " . $column->getName());
                }
                $typeValues = $column->getTypeValues();

                if (!empty($typeValues)) {
                    if (is_array($typeValues)) {

                        $valueSql = "";

                        foreach ($typeValues as $value) {
                            $valueSql .= "\"" . addcslashes($value, "\"") . "\", ";
                        }

                        $columnSql .= "(" . substr($valueSql, 0, -2) . ")";
                    } else {
                        $columnSql .= "(\"" . addcslashes($typeValues, "\"") . "\")";
                    }
                }
        }

        return $columnSql;
    }

    /**
     * Generates SQL to add a column to a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param \Phalcon\Db\ColumnInterface $column
     * @return string
     * @throws Exception
     */
    public function addColumn($tableName, $schemaName, ColumnInterface $column)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD `" . $column->getName() . "` " . $this->getColumnDefinition($column);

        if ($column->hasDefault()) {
            $defaultValue = $column->getDefault();
            if (Text::memstr(strtoupper($defaultValue), "CURRENT_TIMESTAMP")) {
                $sql .= " DEFAULT CURRENT_TIMESTAMP";
            } else {
                $sql .= " DEFAULT \"" . addcslashes($defaultValue, "\"") . "\"";
            }
        }

        if ($column->isNotNull() === true) {
            $sql .= ' NOT NULL';
        }

        if ($column->isAutoIncrement()) {
            $sql .= " AUTO_INCREMENT";
        }

        if ($column->isFirst() === true) {
            $sql .= ' FIRST';
        } else {
            $afterPosition = $column->getAfterPosition();

            if ($afterPosition == true) {
                $sql .= " AFTER `" . $afterPosition . "`";
            }
        }

        return $sql;
    }

    /**
     * Generates SQL to modify a column in a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param \Phalcon\Db\ColumnInterface $column
     * @param \Phalcon\Db\ColumnInterface $currentColumn
     * @return string
     * @throws Exception
     */
    public function modifyColumn($tableName, $schemaName, ColumnInterface $column, ColumnInterface $currentColumn = null)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " MODIFY `" . $column->getName() . "` " . $this->getColumnDefinition($column);

        if ($column->hasDefault()) {
            $defaultValue = $column->getDefault();
            if (Text::memstr(strtoupper($defaultValue), "CURRENT_TIMESTAMP")) {
                $sql .= " DEFAULT CURRENT_TIMESTAMP";
            } else {
                $sql .= " DEFAULT \"" . addcslashes($defaultValue, "\"") . "\"";
            }
        }

        if ($column->isNotNull() === true) {
            $sql .= ' NOT NULL';
        }

        if ($column->isAutoIncrement()) {
            $sql .= " AUTO_INCREMENT";
        }

        if ($column->isFirst() === true) {
            $sql .= ' FIRST';
        } else {
            $afterPosition = $column->getAfterPosition();
            if ($afterPosition) {
                $sql .= " AFTER `" . $afterPosition . "`";
            }
        }

        return $sql;
    }

    /**
     * Generates SQL to delete a column from a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param string $columnName
     * @return string
     * @throws Exception
     */
    public function dropColumn($tableName, $schemaName, $columnName)
    {
        if (is_string($tableName) === false ||
            is_string($columnName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP COLUMN `" . $columnName . "`";
    }

    /**
     * Generates SQL to add an index to a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param \Phalcon\Db\IndexInterface $index
     * @return string
     * @throws Exception
     */
    public function addIndex($tableName, $schemaName, IndexInterface $index)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($index) === false ||
            $index instanceof IndexInterface === false) {
            throw new Exception('Index parameter must be an instance of Phalcon\\Db\\Index');
        }

        $sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName);

        $indexType = $index->getType();
        if (!empty($indexType)) {
            $sql .= " ADD " . $indexType . " INDEX ";
        } else {
            $sql .= " ADD INDEX ";
        }

        $sql .= "`" . $index->getName() . "` (" . $this->getColumnList($index->getColumns()) . ")";
        return $sql;
    }

    /**
     * Generates SQL to delete an index from a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param string $indexName
     * @return string
     * @throws Exception
     */
    public function dropIndex($tableName, $schemaName, $indexName)
    {
        if (is_string($tableName) === false ||
            is_string($indexName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP INDEX `" . $indexName . "`";
    }

    /**
     * Generates SQL to add the primary key to a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param \Phalcon\Db\IndexInterface $index
     * @return string
     * @throws Exception
     */
    public function addPrimaryKey($tableName, $schemaName, IndexInterface $index)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($index) === false ||
            $index instanceof IndexInterface === false) {
            throw new Exception('Index parameter must be an instance of Phalcon\\Db\\Index');
        }
        return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD PRIMARY KEY (" . $this->getColumnList($index->getColumns()) . ")";
    }

    /**
     * Generates SQL to delete primary key from a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @return string
     * @throws Exception
     */
    public function dropPrimaryKey($tableName, $schemaName = null)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP PRIMARY KEY";
    }

    /**
     * Generates SQL to add an index to a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param \Phalcon\Db\ReferenceInterface $reference
     * @return string
     * @throws Exception
     */
    public function addForeignKey($tableName, $schemaName, ReferenceInterface $reference)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($reference) === false ||
            $reference instanceof ReferenceInterface === false) {
            throw new Exception('Reference parameter must be an instance of Phalcon\\Db\\Reference');
        }

        $sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD";
        if ($reference->getName()) {
            $sql .= " CONSTRAINT `" . $reference->getName() . "`";
        }
        $sql .= " FOREIGN KEY (" . $this->getColumnList($reference->getColumns()) . ") REFERENCES " . $this->prepareTable($reference->getReferencedTable(), $reference->getReferencedSchema()) . "(" . $this->getColumnList($reference->getReferencedColumns()) . ")";

        $onDelete = $reference->getOnDelete();
        if (!empty($onDelete)) {
            $sql .= " ON DELETE " . $onDelete;
        }

        $onUpdate = $reference->getOnUpdate();
        if (!empty($onUpdate)) {
            $sql .= " ON UPDATE " . $onUpdate;
        }

        return $sql;
    }

    /**
     * Generates SQL to delete a foreign key from a table
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param string $referenceName
     * @return string
     * @throws Exception
     */
    public function dropForeignKey($tableName, $schemaName, $referenceName)
    {
        if (is_string($tableName) === false ||
            is_string($referenceName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP FOREIGN KEY `" . $referenceName . "`";
    }

    /**
     * Generates SQL to add the table creation options
     *
     * @param array $definition
     * @return string|null
     * @throws Exception
     */
    protected function _getTableOptions($definition)
    {
        if (is_array($definition) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($definition['options']) === true) {
            $tableOptions = array();
            $options      = $definition['options'];

            //Check if there is an ENGINE option
            if (isset($options['ENGINE']) === true &&
                $options['ENGINE']) {
                $tableOptions[] = 'ENGINE=' . $options['ENGINE'];
            }

            //Check if there is a n AUTO_INCREMENT option
            if (isset($options['AUTO_INCREMENT']) === true &&
                $options['AUTO_INCREMENT']) {
                $tableOptions[] = 'AUTO_INCREMENT=' . $options['AUTO_INCREMENT'];
            }

            //Check if there is an TABLE_COLLATION option
            if (isset($options['TABLE_COLLATION']) === true &&
                $options['TABLE_COLLATION']) {
                $collationParts = explode('_', $options['TABLE_COLLATION']);
                $tableOptions[] = 'DEFAULT CHARSET=' . $collationParts[0];
                $tableOptions[] = 'COLLATE=' . $options['TABLE_COLLATION'];
            }

            if (count($tableOptions) > 0) {
                return implode(' ', $tableOptions);
            }
        }

        return "";
    }

    /**
     * Generates SQL to create a table in MySQL
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @param array $definition
     * @return string
     * @throws Exception
     */
    public function createTable($tableName, $schemaName, $definition)
    {
        if (is_string($tableName) === false ||
            is_array($definition) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($definition['columns']) === false) {
            throw new Exception("The index 'columns' is required in the definition array");
        } else {
            $columns = $definition["columns"];
        }
        $table     = $this->prepareTable($tableName, $schemaName);
        $temporary = false;

        if (isset($definition['options'])) {
            $options = $definition['options'];
            if (isset($options['temporary'])) {
                $temporary = $options['temporary'];
            }
        }
        if ($temporary) {
            $sql = "CREATE TEMPORARY TABLE " . $table . " (\n\t";
        } else {
            $sql = "CREATE TABLE " . $table . " (\n\t";
        }
        $createLines = [];
        foreach ($columns as $column) {
            $columnLine = "`" . $column->getName() . "` " . $this->getColumnDefinition($column);
            if ($column->hasDefault()) {
                $defaultValue = $column->getDefault();
                if (Text::memstr(strtoupper($defaultValue), "CURRENT_TIMESTAMP")) {
                    $columnLine .= " DEFAULT CURRENT_TIMESTAMP";
                } else {
                    $columnLine .= " DEFAULT \"" . addcslashes($defaultValue, "\"") . "\"";
                }
            }

            /**
             * Add a NOT NULL clause
             */
            if ($column->isNotNull()) {
                $columnLine .= " NOT NULL";
            }

            /**
             * Add an AUTO_INCREMENT clause
             */
            if ($column->isAutoIncrement()) {
                $columnLine .= " AUTO_INCREMENT";
            }

            /**
             * Mark the column as primary key
             */
            if ($column->isPrimary()) {
                $columnLine .= " PRIMARY KEY";
            }
            $createLines[] = $columnLine;
        }

        /**
         * Create related indexes
         */
        if (isset($definition["indexes"])) {
            foreach ($definition['indexes'] as $index) {
                $indexName = $index->getName();
                $indexType = $index->getType();

                if ($indexName == "PRIMARY") {
                    $indexSql = "PRIMARY KEY (" . $this->getColumnList($index->getColumns()) . ")";
                } else {
                    if (!empty($indexType)) {
                        $indexSql = $indexType . " KEY `" . $indexName . "` (" . $this->getColumnList($index->getColumns()) . ")";
                    } else {
                        $indexSql = "KEY `" . $indexName . "` (" . $this->getColumnList($index->getColumns()) . ")";
                    }
                }

                $createLines[] = $indexSql;
            }
        }

        /**
         * Create related references
         */
        if (isset($definition["references"])) {
            foreach ($definition['references'] as $reference) {
                $referenceSql = "CONSTRAINT `" . $reference->getName() . "` FOREIGN KEY (" . $this->getColumnList($reference->getColumns()) . ")"
                    . " REFERENCES `" . $reference->getReferencedTable() . "`(" . $this->getColumnList($reference->getReferencedColumns()) . ")";

                $onDelete = $reference->getOnDelete();
                if (!empty($onDelete)) {
                    $referenceSql .= " ON DELETE " . $onDelete;
                }

                $onUpdate = $reference->getOnUpdate();
                if (!empty($onUpdate)) {
                    $referenceSql .= " ON UPDATE " . $onUpdate;
                }

                $createLines[] = $referenceSql;
            }
        }

        $sql .= join(",\n\t", $createLines) . "\n)";
        if (isset($definition["options"])) {
            $sql .= " " . $this->_getTableOptions($definition);
        }
        return $sql;
    }

    /**
     * Generates SQL to truncate a table
     * @param string $tableName
     * @param string $schemaName
     * @return string
     * @throws \Phalcon\Db\Exception
     */
    public function truncateTable($tableName, $schemaName)
    {
        if (!is_string($tableName)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($schemaName)) {
            $table = "`" . $schemaName . "`.`" . $tableName . "`";
        } else {
            $table = "`" . $tableName . "`";
        }
        $sql = "TRUNCATE TABLE " . $table;
        return $sql;
    }

    /**
     * Generates SQL to drop a table
     *
     * @param  string $tableName
     * @param  string|null $schemaName
     * @param  boolean|null $ifExists
     * @return string
     * @throws Exception
     */
    public function dropTable($tableName, $schemaName, $ifExists = true)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($ifExists) === true) {
            $ifExists = true;
        } elseif (is_bool($ifExists) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $table = $this->prepareTable($tableName, $schemaName);
        if ($ifExists) {
            $sql = "DROP TABLE IF EXISTS " . $table;
        } else {
            $sql = "DROP TABLE " . $table;
        }
        return $sql;
    }

    /**
     * Generates SQL to create a view
     *
     * @param string $viewName
     * @param array $definition
     * @param string|null $schemaName
     * @return string
     * @throws Exception
     */
    public function createView($viewName, $definition, $schemaName)
    {
        if (is_string($viewName) === false ||
            is_array($definition) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($definition['sql']) === false) {
            throw new Exception("The index 'sql' is required in the definition array");
        } else {
            $viewSql = $definition['sql'];
        }
        return "CREATE VIEW " . $this->prepareTable($viewName, $schemaName) . " AS " . $viewSql;
    }

    /**
     * Generates SQL to drop a view
     *
     * @param string $viewName
     * @param string|null $schemaName
     * @param boolean|null $ifExists
     * @return string
     * @throws Exception
     */
    public function dropView($viewName, $schemaName, $ifExists = true)
    {
        if (is_string($viewName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($ifExists) === true) {
            $ifExists = true;
        } elseif (is_bool($ifExists) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $view = $this->prepareTable($viewName, $schemaName);

        if ($ifExists) {
            $sql = "DROP VIEW IF EXISTS " . $view;
        } else {
            $sql = "DROP VIEW " . $view;
        }

        return $sql;
    }

    /**
     * Generates SQL checking for the existence of a schema.table
     *
     * <code>
     * echo $dialect->tableExists("posts", "blog");
     * echo $dialect->tableExists("posts");
     * </code>
     *
     * @param string $tableName
     * @param string|null $schemaName
     * @return string
     * @throws Exception
     */
    public function tableExists($tableName, $schemaName = null)
    {
        if (is_string($tableName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($schemaName) {
            return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`= '" . $tableName . "' AND `TABLE_SCHEMA` = '" . $schemaName . "'";
        }
        return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME` = '" . $tableName . "' AND `TABLE_SCHEMA` = DATABASE()";
    }

    /**
     * Generates SQL checking for the existence of a schema.view
     *
     * @param string $viewName
     * @param string|null $schemaName
     * @return string
     * @throws Exception
     */
    public function viewExists($viewName, $schemaName = null)
    {
        if (is_string($viewName) === false) {
            throw new Exception('Invalid parameter type.');
        }
        if ($schemaName) {
            return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_NAME`= '" . $viewName . "' AND `TABLE_SCHEMA`='" . $schemaName . "'";
        }
        return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_NAME`='" . $viewName . "' AND `TABLE_SCHEMA` = DATABASE()";
    }

    /**
     * Generates SQL describing a table
     *
     * <code>
     *  print_r($dialect->describeColumns("posts")) ?>
     * </code>
     *
     * @param string $table
     * @param string|null $schema
     * @return string
     * @throws Exception
     */
    public function describeColumns($table, $schema = null)
    {
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return "DESCRIBE " . $this->prepareTable($table, $schema);
    }

    /**
     * List all tables on database
     *
     * <code>
     *  print_r($dialect->listTables("blog")) ?>
     * </code>
     *
     * @param string|null $schemaName
     * @return string
     */
    public function listTables($schemaName = null)
    {
        if (is_string($schemaName) === true) {
            return 'SHOW TABLES FROM `' . $schemaName . '`';
        }

        return 'SHOW TABLES';
    }

    /**
     * Generates the SQL to list all views of a schema or user
     *
     * @param string|null $schemaName
     * @return string
     */
    public function listViews($schemaName = null)
    {
        if ($schemaName != null && !is_string($schemaName)) {
            throw new Exception('Invalid parameter type');
        }
        if ($schemaName) {
            return "SELECT `TABLE_NAME` AS view_name FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_SCHEMA` = '" . $schemaName . "' ORDER BY view_name";
        }
        return "SELECT `TABLE_NAME` AS view_name FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_SCHEMA` = DATABASE() ORDER BY view_name";
    }

    /**
     * Generates SQL to query indexes on a table
     *
     * @param string $table
     * @param string|null $schema
     * @return string
     * @throws Exception
     */
    public function describeIndexes($table, $schema = null)
    {
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($schema) === true) {
            return "SHOW INDEXES FROM " . $this->prepareTable($table, $schema);
        }
    }

    /**
     * Generates SQL to query foreign keys on a table
     *
     * @param string $table
     * @param string|null $schema
     * @return string
     * @throws Exception
     */
    public function describeReferences($table, $schema = null)
    {
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $sql = "SELECT DISTINCT KCU.TABLE_NAME, KCU.COLUMN_NAME, KCU.CONSTRAINT_NAME, KCU.REFERENCED_TABLE_SCHEMA, KCU.REFERENCED_TABLE_NAME, KCU.REFERENCED_COLUMN_NAME, RC.UPDATE_RULE, RC.DELETE_RULE FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KCU LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS RC ON RC.CONSTRAINT_NAME = KCU.CONSTRAINT_NAME AND RC.CONSTRAINT_SCHEMA = KCU.CONSTRAINT_SCHEMA WHERE KCU.REFERENCED_TABLE_NAME IS NOT NULL AND ";
        if ($schema) {
            $sql .= "KCU.CONSTRAINT_SCHEMA = '" . $schema . "' AND KCU.TABLE_NAME = '" . $table . "'";
        } else {
            $sql .= "KCU.CONSTRAINT_SCHEMA = DATABASE() AND KCU.TABLE_NAME = '" . $table . "'";
        }
        return $sql;
    }

    /**
     * Generates the SQL to describe the table creation options
     *
     * @param string $table
     * @param string|null $schema
     * @return string
     * @throws Exception
     */
    public function tableOptions($table, $schema = null)
    {
        if (is_string($table) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $sql = "SELECT TABLES.TABLE_TYPE AS table_type,TABLES.AUTO_INCREMENT AS auto_increment,TABLES.ENGINE AS engine,TABLES.TABLE_COLLATION AS table_collation FROM INFORMATION_SCHEMA.TABLES WHERE ";
        if ($schema) {
            return $sql . "TABLES.TABLE_SCHEMA = '" . $schema . "' AND TABLES.TABLE_NAME = '" . $table . "'";
        }
        return $sql . "TABLES.TABLE_SCHEMA = DATABASE() AND TABLES.TABLE_NAME = '" . $table . "'";
    }

    /**
     * Generates SQL to check DB parameter FOREIGN_KEY_CHECKS.
     * @return string
     */
    public function getForeignKeyChecks()
    {
        return "SELECT @@foreign_key_checks";
    }

}
