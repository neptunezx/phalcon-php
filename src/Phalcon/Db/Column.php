<?php

namespace Phalcon\Db;

use function GuzzleHttp\Promise\promise_for;
use \Phalcon\Db\ColumnInterface;
use \Phalcon\Db\Exception;

/**
 * Phalcon\Db\Column
 *
 * Allows to define columns to be used on create or alter table operations
 *
 * <code>
 *  use Phalcon\Db\Column as Column;
 *
 * //column definition
 * $column = new Column("id", array(
 *   "type" => Column::TYPE_INTEGER,
 *   "size" => 10,
 *   "unsigned" => true,
 *   "notNull" => true,
 *   "autoIncrement" => true,
 *   "first" => true
 * ));
 *
 * //add column to existing table
 * $connection->addColumn("robots", null, $column);
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/db/column.c
 */
class Column implements ColumnInterface
{

    /**
     * Type: Integer
     *
     * @var int
     */
    const TYPE_INTEGER = 0;

    /**
     * Type: Date
     *
     * @var int
     */
    const TYPE_DATE = 1;

    /**
     * Type: Varchar
     *
     * @var int
     */
    const TYPE_VARCHAR = 2;

    /**
     * Type: Decimal
     *
     * @var int
     */
    const TYPE_DECIMAL = 3;

    /**
     * Type: DateTime
     *
     * @var int
     */
    const TYPE_DATETIME = 4;

    /**
     * Type: Char
     *
     * @var int
     */
    const TYPE_CHAR = 5;

    /**
     * Type: Text
     *
     * @var int
     */
    const TYPE_TEXT = 6;

    /**
     * Type: Float
     *
     * @var int
     */
    const TYPE_FLOAT = 7;

    /**
     * Type: Boolean
     *
     * @var int
     */
    const TYPE_BOOLEAN = 8;

    /**
     * Type: Double
     *
     * @var int
     */
    const TYPE_DOUBLE = 9;

    /**
     * Tinyblob abstract data type
     */
    const TYPE_TINYBLOB = 10;

    /**
     * Blob abstract data type
     */
    const TYPE_BLOB = 11;

    /**
     * Mediumblob abstract data type
     */
    const TYPE_MEDIUMBLOB = 12;

    /**
     * Longblob abstract data type
     */
    const TYPE_LONGBLOB = 13;

    /**
     * Big integer abstract data type
     */
    const TYPE_BIGINTEGER = 14;

    /**
     * Json abstract type
     */
    const TYPE_JSON = 15;

    /**
     * Jsonb abstract type
     */
    const TYPE_JSONB = 16;

    /**
     * Datetime abstract type
     */
    const TYPE_TIMESTAMP = 17;

    /**
     * Bind Param: Null
     *
     * @var int
     */
    const BIND_PARAM_NULL = 0;

    /**
     * Bind Param: Integer
     *
     * @var int
     */
    const BIND_PARAM_INT = 1;

    /**
     * Bind Param: String
     *
     * @var int
     */
    const BIND_PARAM_STR = 2;

    /**
     * Bind Param: Boolean
     *
     * @var int
     */
    const BIND_PARAM_BOOL = 5;

    /**
     * Bind Param: Decimal
     *
     * @var int
     */
    const BIND_PARAM_DECIMAL = 32;

    /**
     * Bind: Skip
     *
     * @var int
     */
    const BIND_SKIP = 1024;

    /**
     * Column Name
     *
     * @var null|string
     * @access protected
     */
    protected $_columnName;

    /**
     * Schema Name
     *
     * @var null
     * @access protected
     */
    protected $_schemaName;

    /**
     * Type
     *
     * @var null|int
     * @access protected
     */
    protected $_type;

    /**
     * Column data type reference
     *
     * @var int
     */
    protected $_typeReference = -1;

    /**
     * Column data type values
     *
     * @var array|string
     */
    protected $_typeValues;

    /**
     * Is Numeric
     *
     * @var boolean
     * @access protected
     */
    protected $_isNumeric = false;

    /**
     * Size
     *
     * @var int
     * @access protected
     */
    protected $_size = 0;

    /**
     * Scale
     *
     * @var int
     * @access protected
     */
    protected $_scale = 0;

    /**
     * Default column value
     */
    protected $_default = null;

    /**
     * Unsigned
     *
     * @var boolean
     * @access protected
     */
    protected $_unsigned = false;

    /**
     * Not Null
     *
     * @var boolean
     * @access protected
     */
    protected $_notNull = false;

    /**
     * Primary
     *
     * @var boolean
     * @access protected
     */
    protected $_primary = false;

    /**
     * Auto Increment
     *
     * @var boolean
     * @access protected
     */
    protected $_autoIncrement = false;

    /**
     * First
     *
     * @var boolean
     * @access protected
     */
    protected $_first = false;

    /**
     * After
     *
     * @var null|string
     * @access protected
     */
    protected $_after;

    /**
     * Bind Type
     *
     * @var int
     * @access protected
     */
    protected $_bindType = 2;

    /**
     * \Phalcon\Db\Column constructor
     *
     * @param string $columnName
     * @param array $definition
     * @throws Exception
     */
    public function __construct($columnName, $definition)
    {
        if (is_string($columnName) === false ||
            is_array($definition) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_columnName = $columnName;

        //Get the column type, one of the TYPE_* constants
        if (isset($definition['type']) === true) {
            $this->_type = $definition['type'];
        } else {
            throw new Exception('Column type is required');
        }

        $type = (int) $definition['type'];

        //Check if the field is typeReference
        if (isset($definition['typeReference']) === true) {
            $this->_typeReference = $definition['typeReference'];
        }

        //Check if the field is typeValues
        if (isset($definition['typeValues']) === true) {
            $this->_typeValues = $definition['typeValues'];
        }


        //Check if the field is nullable
        if (isset($definition['notNull']) === true) {
            $this->_notNull = $definition['notNull'];
        }

        //Check if the field is primary key
        if (isset($definition['primary']) === true) {
            $this->_primary = $definition['primary'];
        }

        if (isset($definition['size']) === true) {
            $this->_size = $definition['size'];
        }

        //Check if the column has a decimal scale
        if (isset($definition['scale'])) {
            switch ($type) {
                case  self::TYPE_INTEGER:
                case  self::TYPE_FLOAT:
                case  self::TYPE_DECIMAL:
                case  self::TYPE_DOUBLE:
                case  self::TYPE_BIGINTEGER:
                    $this->_scale = $definition['scale'];
                    break;
                default:
                    throw new Exception(
                        "Column type does not support scale parameter"
                    );
            }
        }

        //Check if the field is default
        if (isset($definition['default']) === true) {
            $this->_default = $definition['default'];
        }

        //Check if the field is unsigned (only MySQL)
        if (isset($definition['unsigned']) === true) {
            $this->_unsigned = $definition['unsigned'];
        }

        if (isset($definition['isNumeric']) === true) {
            $this->_isNumeric = $definition['isNumeric'];
        }

        //Check if the field is numeric
        if (isset($definition['autoIncrement']) === true) {

            if (!$definition['autoIncrement']) {
                $this->_autoIncrement = false;
            }else{
                if (in_array(
                    $type, [
                    self::TYPE_INTEGER,
                    self::TYPE_BIGINTEGER,
                ])) {
                    $this->_autoIncrement = true;
                }else{
                    throw new Exception("Column type cannot be auto-increment");
                }
            }
        }

        //Check if the field is placed at the first position of the table
        if (isset($definition['first']) === true) {
            $this->_first = $definition['first'];
        }

        //Name of the column which is placed before the current field
        if (isset($definition['after']) === true) {
            $this->_after = $definition['after'];
        }

        //The bind type to cast the field when passing it to PDO
        if (isset($definition['bindType']) === true) {
            $this->_bindType = $definition['bindType'];
        }
    }

    /**
     * Returns schema's table related to column
     *
     * @return null
     */
    public function getSchemaName()
    {
        return $this->_schemaName;
    }

    /**
     * Returns column name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_columnName;
    }

    /**
     * Returns column type
     *
     * @return int
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Returns column size
     *
     * @return int|null
     */
    public function getSize()
    {
        return $this->_size;
    }

    /**
     * Returns column scale
     *
     * @return int|null
     */
    public function getScale()
    {
        return $this->_scale;
    }

    /**
     * Returns true if number column is unsigned
     *
     * @return boolean
     */
    public function isUnsigned()
    {
        return $this->_unsigned;
    }

    /**
     * Not null
     *
     * @return boolean
     */
    public function isNotNull()
    {
        return $this->_notNull;
    }

    /**
     * Column is part of the primary key?
     *
     * @return boolean
     */
    public function isPrimary()
    {
        return $this->_primary;
    }

    /**
     * Auto-Increment
     *
     * @return boolean
     */
    public function isAutoIncrement()
    {
        return $this->_autoIncrement;
    }

    /**
     * @return int|mixed|null
     */
    public function getDefault()
    {
        // TODO: Implement getDefault() method.
        return $this->_default;
    }

    /**
     * @return int|mixed
     */
    public function getTypeReference()
    {
        // TODO: Implement getTypeReference() method.
        return $this->_typeReference;
    }

    /**
     * @return array|int|mixed|string
     */
    public function getTypeValues()
    {
        // TODO: Implement getTypeValues() method.
        return $this->_typeValues;
    }

    /**
     * Check whether column have an numeric type
     *
     * @return boolean
     */
    public function isNumeric()
    {
        return $this->_isNumeric;
    }

    /**
     * Check whether column have first position in table
     *
     * @return boolean
     */
    public function isFirst()
    {
        return $this->_first;
    }

    /**
     * Check whether field absolute to position in table
     *
     * @return string|null
     */
    public function getAfterPosition()
    {
        return $this->_after;
    }

    /**
     * Returns the type of bind handling
     *
     * @return int
     */
    public function getBindType()
    {
        return $this->_bindType;
    }

    /**
     * Restores the internal state of a \Phalcon\Db\Column object
     *
     * @param array $data
     * @return \Phalcon\Db\Column
     * @throws Exception
     */
    public static function __set_state($data)
    {
        if (is_array($data) === false) {
            throw new Exception('Column state must be an array');
        }

        $columnName = isset($data['_columnName']) ? $data['_columnName'] :
            (isset($data['_name']) ? $data['_name'] : null);
        if ($columnName) {
            throw new Exception("Column name is required");
        }

        $definition = array();

        if (isset($data['_type']) === true) {
            $definition['type'] = $data['_type'];
        }

        if (isset($data["_typeReference"])) {
            $definition["typeReference"] = $data["_typeReference"];
		} else {
            $definition["typeReference"] = -1;
		}

        if (isset($data["typeValues"])) {
            $definition["typeValues"] = $data["_typeValues"];
        }

        if (isset($data['_notNull']) === true) {
            $definition['notNull'] = $data['_notNull'];
        }

        if (isset($data['_primary']) === true) {
            $definition['primary'] = $data['_primary'];
        }

        if (isset($data['_size']) === true) {
            $definition['size'] = $data['_size'];
        }

        if (isset($data['_default']) === true) {
            $definition['default'] = $data['_default'];
        }

        if (isset($data['_scale']) === true) {
            if (
            in_array($definition['type'],
                [
                    self::TYPE_INTEGER,
                    self::TYPE_FLOAT,
                    self::TYPE_DECIMAL,
                    self::TYPE_DOUBLE,
                    self::TYPE_BIGINTEGER,

                ])
            ) {
                $definition['scale'] = $data['_scale'];
            }
        }

        if (isset($data['_unsigned']) === true) {
            $definition['unsigned'] = $data['_unsigned'];
        }

        if (isset($data['_after']) === true) {
            $definition['after'] = $data['_after'];
        }

        if (isset($data['_isNumeric']) === true) {
            $definition['isNumeric'] = $data['_isNumeric'];
        }

        if (isset($data['_autoIncrement']) === true) {
            $definition['autoIncrement'] = $data['_autoIncrement'];
        }

        if (isset($data['_first']) === true) {
            $definition['first'] = $data['_first'];
        }

        if (isset($data['_bindType']) === true) {
            $definition['bindType'] = $data['_bindType'];
        }

        return new Column($columnName, $definition);
    }


    /**
     * Check whether column has default value
     * @return bool
     */
    public function hasDefault()
    {
        // TODO: Implement hasDefault() method.
        if($this->isAutoIncrement()){
			return false;
		}

		return $this->_default !== null;
    }

}
