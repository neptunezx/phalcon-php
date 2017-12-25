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
        if (is_string($table) === false ||
            (is_string($schema) === false &&
            is_null($schema) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $dialect = $this->_dialect;

        //Get the SQL to describe a table
        $sql = $dialect->describeColumns($table, $schema);

        //Get the describe
        $describe  = $this->fetchAll($sql, DB::FETCH_NUM);
        $oldColumn = null;
        $sizePattern = "#\\(([0-9]+)(?:,\\s*([0-9]+))*\\)#";
        $columns   = array();

        //Field Indexes: 0 - Name, 1 - Type, 2 - Not Null, 3 - Key, 4 - Default, 5 - Extra
        foreach ($describe as $field) {
            //By default the bind type is two
            $definition = array('bindType' => Column::BIND_PARAM_STR);

            //By checking every column type we convert it to a Phalcon\Db\Column
            $columnType = $field[1];

            //Check the column type to get the current Phalcon type
            while (true) {
                //Point are varchars
                if (strpos($columnType, 'point') !== false) {
                    $definition['type'] = 2;
                    break;
                }

                //Enum are treated as char
                if (strpos($columnType, 'enum') !== false) {
                    $definition['type'] = 5;
                    break;
                }

                //Tinyint(1) is boolean
                if (strpos($columnType, 'tinyint(1)') !== false) {
                    $definition['type']     = 8;
                    $definition['bindType'] = 5;
                    $columnType             = 'boolean';
                    break;
                }

                //Smallint/Bigint/Integer/Int are int
                if (strpos($columnType, 'int') !== false) {
                    $definition['type']      = 0;
                    $definition['isNumeric'] = true;
                    $definition['bindType']  = Column::BIND_PARAM_INT;
                    break;
                }

                //Varchar are varchars
                if (strpos($columnType, 'varchar') !== false) {
                    $definition['type'] = 2;
                    break;
                }

                //Special type for datetime
                if (strpos($columnType, 'datetime') !== false) {
                    $definition['type'] = 4;
                    break;
                }

                //Decimals are floats
                if (strpos($columnType, 'decimal') !== false) {
                    $definition['type']      = 3;
                    $definition['isNumeric'] = true;
                    $definition['bindType']  = 32;
                    break;
                }

                //Chars are chars
                if (strpos($columnType, 'char') !== false) {
                    $definition['type'] = 5;
                    break;
                }

                //Date/Datetime are varchars
                if (strpos($columnType, 'date') !== false) {
                    $definition['type'] = 1;
                    break;
                }

                //Timestamp as date
                if (strpos($columnType, 'timstamp') !== false) {
                    $definition['type'] = 1;
                    break;
                }

                //Text are varchars
                if (strpos($columnType, 'text') !== false) {
                    $definition['type'] = 6;
                    break;
                }

                //Floats/Smallfloats/Decimals are float
                if (strpos($columnType, 'float') !== false) {
                    $definition['type']      = 7;
                    $definition['isNumeric'] = true;
                    $definition['bindType']  = 32;
                    break;
                }

                //Doubles are floats
                if (strpos($columnType, 'double') !== false) {
                    $definition['type']      = 9;
                    $definition['isNumeric'] = true;
                    $definition['bindType']  = 32;
                    break;
                }

                //By default: String
                $definition['type'] = 2;
                break;
            }

            //If the column type has a parentheses we try to get the column size from it
            if (strpos($columnType, '(') !== false) {
                $matches = null;
                $pos     = preg_match($sizePattern, $columnType, $matches);
                if ($pos == true) {
                    if (isset($matches[1]) === true) {
                        $definition['size'] = (int) $matches[1];
                    }

                    if (isset($matches[2]) === true) {
                        $definition['scale'] = (int) $matches[2];
                    }
                }
            }

            //Check if the column is unsigned, only MySQL supports this
            if (strpos($columnType, 'unsigned') !== false) {
                $definition['unsigned'] = true;
            }

            //Positions
            if ($oldColumn == null ) {
                $definition['first'] = true;
            } else {
                $definition['after'] = $oldColumn;
            }

            //Check if the field is primary key
            if ($field[3] === 'PRI') {
                $definition['primary'] = true;
            }

            //Check if the column allows null values
            if ($field[2] == 'NO') {
                $definition['notNull'] = true;
            }


            //Check if the column is auto increment
            if ($field[5] === 'auto_increment') {
                $definition['autoIncrement'] = true;
            }

            if ($field[4] === null) {
                $definition['default'] = $field[4];
            }


            $column    = new Column($field[0], $definition);
            $columns[] = $column;
            $oldColumn = $field[0];
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
    public function describeIndexes( $table, $schema = null )
	{

		$indexes = [];
        $dialect = $this->_dialect;

        //Get the SQL to describe a table
        $sql = $dialect->describeIndexes($table, $schema);

        //Get the describe
        $describe  = $this->fetchAll($sql, Db::FETCH_ASSOC);
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

            $columns[] = $index["Column_name"];
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
     *<code>
     * print_r(
     *     $connection->describeReferences("robots_parts")
     * );
     * @param string $table
     * @param null   $schema
     *
     * @return \Phalcon\Db\Reference[]
     * @throws \Phalcon\Db\Exception
     */
	public function describeReferences($table,$schema = null)
    {
        $references = [];
        $dialect = $this->_dialect;

        //Get the SQL to describe a table
        $sql = $dialect->describeIndexes($table, $schema);

        //Get the describe
        $describe  = $this->fetchAll($sql, Db::FETCH_NUM);

        foreach ($describe as $reference) {
            $constraintName = $reference[2];

            if (!isset ($references[$constraintName])) {
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

            $columns[] = $reference[1];
            $referencedColumns[] = $reference[5];
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
		foreach ($references as $name => $arrayReference)  {
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
