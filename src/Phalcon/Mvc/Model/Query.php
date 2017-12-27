<?php

namespace Phalcon\Mvc\Model;

use Phalcon\Db\Column;
use Phalcon\Db\RawValue;
use Phalcon\Db\ResultInterface;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Mvc\Model\QueryInterface;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\Model\Query\Status;
use Phalcon\Mvc\Model\Resultset\Complex;
use Phalcon\Mvc\Model\Query\StatusInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\Model\Query\Lang;
use Phalcon\Kernel;
use Phalcon\Text;

/**
 * Phalcon\Mvc\Model\Query
 *
 * This class takes a PHQL intermediate representation and executes it.
 *
 * <code>
 * $phql = "SELECT c.price*0.16 AS taxes, c.* FROM Cars AS c JOIN Brands AS b
 *          WHERE b.name = :name: ORDER BY c.name";
 *
 * $result = $manager->executeQuery(
 *     $phql,
 *     [
 *         "name" => "Lamborghini",
 *     ]
 * );
 *
 * foreach ($result as $row) {
 *     echo "Name: ",  $row->cars->name, "\n";
 *     echo "Price: ", $row->cars->price, "\n";
 *     echo "Taxes: ", $row->taxes, "\n";
 * }
 * </code>
 */
class Query implements QueryInterface, InjectionAwareInterface
{

    /**
     * Type: Select
     *
     * @var int
     */
    const TYPE_SELECT = 309;

    /**
     * Type: Insert
     *
     * @var int
     */
    const TYPE_INSERT = 306;

    /**
     * Type: Update
     *
     * @var int
     */
    const TYPE_UPDATE = 300;

    /**
     * Type: Delete
     *
     * @var int
     */
    const TYPE_DELETE = 303;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
     */
    protected $_dependencyInjector;

    /**
     * Manager
     *
     * @var null|object
     * @access protected
     */
    protected $_manager;

    /**
     * Metadata
     *
     * @var null|object
     * @access protected
     */
    protected $_metaData;

    /**
     * Type
     *
     * @var null|int
     * @access protected
     */
    protected $_type;

    /**
     * PHQL
     *
     * @var null|string
     * @access protected
     */
    protected $_phql;

    /**
     * AST
     *
     * @var null|array
     * @access protected
     */
    protected $_ast;

    /**
     * Intermediate
     *
     * @var null|array
     * @access protected
     */
    protected $_intermediate;

    /**
     * Models
     *
     * @var null|array
     * @access protected
     */
    protected $_models;

    /**
     * SQL Aliases
     *
     * @var null|array
     * @access protected
     */
    protected $_sqlAliases;

    /**
     * SQL Aliases Models
     *
     * @var null|array
     * @access protected
     */
    protected $_sqlAliasesModels;

    /**
     * SQL Models Aliases
     *
     * @var null|array
     * @access protected
     */
    protected $_sqlModelsAliases;

    /**
     * SQL Aliases Models Instances
     *
     * @var null|array
     * @access protected
     */
    protected $_sqlAliasesModelsInstances;

    /**
     * SQL Column Aliases
     *
     * @var null|array
     * @access protected
     */
    protected $_sqlColumnAliases;

    /**
     * Model Instances
     *
     * @var null|array
     * @access protected
     */
    protected $_modelsInstances;

    /**
     * Cache
     *
     * @var null|\Phalcon\Cache\BackendInterface
     * @access protected
     */
    protected $_cache;

    /**
     * Cache Options
     *
     * @var null|array
     * @access protected
     */
    protected $_cacheOptions;

    /**
     * Unique Row
     *
     * @var null|boolean
     * @access protected
     */
    protected $_uniqueRow;

    /**
     * Bind Params
     *
     * @var null|array
     * @access protected
     */
    protected $_bindParams;

    /**
     * Bind Types
     *
     * @var null|array
     * @access protected
     */
    protected $_bindTypes;

    /**
     * Enable ImplicitJoins
     *
     * @var null|array
     * @access protected
     */
    protected $_enableImplicitJoins;

    /**
     * Shared Lock
     *
     * @var rray
     * @access protected
     */
    protected $_sharedLock;

    /**
     * IR PHQL Cache
     *
     * @var null|array
     * @access protected
     */
    protected static $_irPhqlCache;

    /**
     * \Phalcon\Mvc\Model\Query constructor
     *
     * @param string|null $phql
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @param array $options
     */
    public function __construct($phql = null, DiInterface $dependencyInjector = null, array $options = null)
    {
        if (is_string($phql) === true) {
            $this->_phql = $phql;
        }

        if (is_object($dependencyInjector) === true) {
            $this->setDi($dependencyInjector);
        }

        if (isset($options["enable_implicit_joins"])) {
            $this->_enableImplicitJoins = $options["enable_implicit_joins"] == true;
        } else {
            $this->_enableImplicitJoins = Kernel::getGlobals("orm.enable_implicit_joins");
        }
    }

    /**
     * Sets the dependency injection container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('A dependency injector container is required to obtain the ORM services');
        }

        $manager = $dependencyInjector->getShared('modelsManager');
        if (is_object($manager) === false) {
            //@note no interface validation
            throw new Exception("Injected service 'modelsManager' is invalid");
        }

        $metaData = $dependencyInjector->getShared('modelsMetadata');
        if (is_object($metaData) === false) {
            //@note no interface validation
            throw new Exception("Injected service 'modelsMetadata' is invalid");
        }

        $this->_manager            = $manager;
        $this->_metaData           = $metaData;
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the dependency injection container
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Tells to the query if only the first row in the resultset must be returned
     *
     * @param boolean $uniqueRow
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setUniqueRow($uniqueRow)
    {
        if (is_bool($uniqueRow) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_uniqueRow = $uniqueRow;

        return $this;
    }

    /**
     * Check if the query is programmed to get only the first row in the resultset
     *
     * @return boolean|null
     */
    public function getUniqueRow()
    {
        return $this->_uniqueRow;
    }

    /**
     * Replaces the model's name to its source name in a qualifed-name expression
     *
     * @param array $expr
     * @return string
     * @throws Exception
     * @todo optimize variable usage
     */
    protected function _getQualified(array $expr)
    {
        $columnName       = $expr['name'];
        $sqlColumnAliases = $this->_sqlColumnAliases;

        //Check if the qualified name is a column alias
        if (isset($sqlColumnAliases[$columnName]) && (!isset($expr["domain"]) || empty($expr["domain"]))) {
            return array('type' => 'qualified', 'name' => $columnName);
        }

        $metaData = $this->_metaData;

        //Check if the qualified name has a domain
        if (isset($expr['domain']) === true) {
            $columnDomain = $expr['domain'];
            $sqlAliases   = $this->_sqlAliases;

            //The column has a domain, we need to check if it's an alias
            if (isset($sqlAliases[$columnDomain]) === false) {
                throw new Exception("Unknown model or alias '" . $columnDomain . "' (11), when preparing: " . $this->_phql);
            }

            $source = $sqlAliases[$columnDomain];

            //Change the selected column by its real name on its mapped table
            if (Kernel::getGlobals('orm.column_renaming')) {
                //Retrieve the corresponding model by its alias
                $sqlAliasesModelsInstances = $this->_sqlAliasesModelsInstances;

                //We need the model instances to retrieve the reversed column map
                if (isset($sqlAliasesModelsInstances[$columnDomain]) === false) {
                    throw new Exception("There is no model related to model or alias '" . $columnDomain . "', when executing: " . $this->_phql);
                }

                $model     = $sqlAliasesModelsInstances[$columnDomain];
                $columnMap = $metaData->getReverseColumnMap($model);
            } else {
                $columnMap = null;
            }

            if (is_array($columnMap) === true) {
                if (isset($columnMap[$columnName]) === true) {
                    $realColumnName = $columnMap[$columnName];
                } else {
                    throw new Exception("Column '" . $columnName . "' doesn't belong to the model or alias '" . $columnDomain . "', when executing: " . $this->_phql);
                }
            } else {
                $realColumnName = $columnName;
            }
        } else {
            $number   = 0;
            $hasModel = false;

            $modelsInstances = $this->_modelsInstances;
            foreach ($modelsInstances as $model) {
                //Check if the attribute belongs to the current model
                if ($metaData->hasAttribute($model, $columnName) === true) {
                    $number++;
                    if ($number > 1) {
                        throw new Exception("The column '" . $columnName . "' is ambiguous, when preparing: " . $this->_phql);
                    }

                    $hasModel = $model;
                }
            }

            //After check in every model, the column does not belong to any of the selected models
            if ($hasModel === false) {
                throw new Exception("Column '" . $columnName . "' doesn't belong to any of the selected models (1), when preparing: " . $this->_phql);
            }

            //Check if the _models property is correctly prepared
            if (is_array($this->_models) === false) {
                throw new Exception('The models list was not loaded correctly');
            }

            //Obtain the model's source from the _models lsit
            $className = get_class($hasModel);
            if (isset($this->_models[$className]) === true) {
                $source = $this->_models[$className];
            } else {
                throw new Exception(
                "Can't obtain model's source from models list: '" . $className . "', when preparing: " . $this->_phql
                );
            }

            //Rename the column
            if (Kernel::getGlobals('orm.column_renaming')) {
                $columnMap = $metaData->getReverseColumnMap($hasModel);
            } else {
                $columnMap = null;
            }

            if (is_array($columnMap) === true) {
                //The real column name is in the column map
                if (isset($columnMap[$columnName]) === true) {
                    $realColumnName = $columnMap[$columnName];
                } else {
                    throw new Exception("Column '" . $columnName . "' doesn't belong to any of the selected models (3), when preparing: " . $this->_phql);
                }
            } else {
                $realColumnName = $columnName;
            }
        }

        //Create an array with the qualified info
        return array('type' => 'qualified', 'domain' => $source, 'name' => $realColumnName, 'balias' => $columnName);
    }

    /**
     * Resolves an expression in a single call argument
     * @param array $expr
     * @return string
     * @throws Exception
     */
    protected final function _getCaseExpression(array $expr)
    {
        $whenClauses = [];
        foreach ($expr["right"] as $whenExpr) {
            if (isset($whenExpr["right"])) {
                $whenClauses[] = [
                    "type" => "when",
                    "expr" => $this->_getExpression($whenExpr["left"]),
                    "then" => $this->_getExpression($whenExpr["right"])
                ];
            } else {
                $whenClauses[] = [
                    "type" => "else",
                    "expr" => $this->_getExpression($whenExpr["left"])
                ];
            }
        }

        return [
            "type"         => "case",
            "expr"         => $this->_getExpression($expr["left"]),
            "when-clauses" => $whenClauses
        ];
    }

    /**
     * Resolves a expression in a single call argument
     *
     * @param array $argument
     * @return string
     */
    protected function _getCallArgument(array $argument)
    {
        if ($argument["type"] == Lang::PHQL_T_STARALL) {
            return ["type" => "all"];
        }

        return $this->_getExpression($argument);
    }

    /**
     * Resolves a expression in a single call argument
     *
     * @param array $expr
     * @return string
     */
    protected function _getFunctionCall(array $expr)
    {
        if (isset($expr['arguments']) === true) {
            $arguments = $expr['arguments'];
            if (isset($expr["distinct"])) {
                $distinct = 1;
            } else {
                $distinct = 0;
            }

            if (isset($arguments[0])) {
                // There are more than one argument
                $functionArgs = [];
                foreach ($arguments as $argument) {
                    $functionArgs[] = $this->_getCallArgument($argument);
                }
            } else {
                // There is only one argument
                $functionArgs = [$this->_getCallArgument($arguments)];
            }

            if ($distinct) {
                return [
                    "type"      => "functionCall",
                    "name"      => $expr["name"],
                    "arguments" => $functionArgs,
                    "distinct"  => $distinct
                ];
            } else {
                return [
                    "type"      => "functionCall",
                    "name"      => $expr["name"],
                    "arguments" => $functionArgs
                ];
            }
        }
        return [
            "type" => "functionCall",
            "name" => $expr["name"]
        ];
    }

    /**
     * Resolves an expression from its intermediate code into a string
     *
     * @param array $expr
     * @param boolean|null $quoting
     * @return string
     * @throws Exception
     */
    protected function _getExpression(array $expr, $quoting = true)
    {
        $quoting = (bool) $quoting;
        if (isset($expr["type"])) {
            $exprType       = $expr["type"];
            $tempNotQuoting = true;

            if ($exprType != Lang::PHQL_T_CASE) {

                /**
                 * Resolving the left part of the expression if any
                 */
                if (isset($expr["left"])) {
                    $left = $this->_getExpression($expr["left"], $tempNotQuoting);
                }

                /**
                 * Resolving the right part of the expression if any
                 */
                if (isset($expr["right"])) {
                    $right = $this->_getExpression($expr["right"], $tempNotQuoting);
                }
            }

            /**
             * $exprType小于256时是ascii码表示，C语言里可以进行隐式转换，在这里需要显示转换下
             * 不然后续switch判断将出现问题，比如当exprType是61是其实是'='(Lang::PHQL_T_EQUALS)
             */
            if ($exprType <= 256) {
                $exprType = chr($exprType);
            }

            /**
             * Every node in the AST has a unique integer type
             */
            switch ($exprType) {

                case Lang::PHQL_T_LESS:
                    $exprReturn = ["type" => "binary-op", "op" => "<", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_EQUALS:
                    $exprReturn = ["type" => "binary-op", "op" => "=", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_GREATER:
                    $exprReturn = ["type" => "binary-op", "op" => ">", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_NOTEQUALS:
                    $exprReturn = ["type" => "binary-op", "op" => "<>", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_LESSEQUAL:
                    $exprReturn = ["type" => "binary-op", "op" => "<=", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_GREATEREQUAL:
                    $exprReturn = ["type" => "binary-op", "op" => ">=", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_AND:
                    $exprReturn = ["type" => "binary-op", "op" => "AND", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_OR:
                    $exprReturn = ["type" => "binary-op", "op" => "OR", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_QUALIFIED:
                    $exprReturn = $this->_getQualified($expr);
                    break;

                case Lang::PHQL_T_ADD:
                    $exprReturn = ["type" => "binary-op", "op" => "+", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_SUB:
                    $exprReturn = ["type" => "binary-op", "op" => "-", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_MUL:
                    $exprReturn = ["type" => "binary-op", "op" => "*", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_DIV:
                    $exprReturn = ["type" => "binary-op", "op" => "/", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_MOD:
                    $exprReturn = ["type" => "binary-op", "op" => "%", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_BITWISE_AND:
                    $exprReturn = ["type" => "binary-op", "op" => "&", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_BITWISE_OR:
                    $exprReturn = ["type" => "binary-op", "op" => "|", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_ENCLOSED:
                case Lang::PHQL_T_SUBQUERY:
                    $exprReturn = ["type" => "parentheses", "left" => $left];
                    break;

                case Lang::PHQL_T_MINUS:
                    $exprReturn = ["type" => "unary-op", "op" => "-", "right" => $right];
                    break;

                case Lang::PHQL_T_INTEGER:
                case Lang::PHQL_T_DOUBLE:
                case Lang::PHQL_T_HINTEGER:
                    $exprReturn = ["type" => "literal", "value" => $expr["value"]];
                    break;

                case Lang::PHQL_T_TRUE:
                    $exprReturn = ["type" => "literal", "value" => "TRUE"];
                    break;

                case Lang::PHQL_T_FALSE:
                    $exprReturn = ["type" => "literal", "value" => "FALSE"];
                    break;

                case Lang::PHQL_T_STRING:
                    $value = $expr["value"];
                    if ($quoting === true) {
                        /**
                         * Check if static literals have single quotes and escape them
                         */
                        if (Text::memstr($value, "'")) {
                            $escapedValue = self::singleQuotes($value);
                        } else {
                            $escapedValue = $value;
                        }
                        $exprValue = "'" . $escapedValue . "'";
                    } else {
                        $exprValue = $value;
                    }
                    $exprReturn = ["type" => "literal", "value" => $exprValue];
                    break;

                case Lang::PHQL_T_NPLACEHOLDER:
                    $exprReturn = ["type" => "placeholder", "value" => str_replace("?", ":", $expr["value"])];
                    break;

                case Lang::PHQL_T_SPLACEHOLDER:
                    $exprReturn = ["type" => "placeholder", "value" => ":" . $expr["value"]];
                    break;

                case Lang::PHQL_T_BPLACEHOLDER:
                    $value = $expr["value"];
                    if (Text::memstr($value, ":")) {

                        $valueParts = explode(":", $value);
                        $name       = $valueParts[0];
                        $bindType   = $valueParts[1];

                        switch ($bindType) {

                            case "str":
                                $this->_bindTypes[$name] = Column::BIND_PARAM_STR;
                                $exprReturn              = ["type" => "placeholder", "value" => ":" . $name];
                                break;

                            case "int":
                                $this->_bindTypes[$name] = Column::BIND_PARAM_INT;
                                $exprReturn              = ["type" => "placeholder", "value" => ":" . $name];
                                break;

                            case "double":
                                $this->_bindTypes[$name] = Column::BIND_PARAM_DECIMAL;
                                $exprReturn              = ["type" => "placeholder", "value" => ":" . $name];
                                break;

                            case "bool":
                                $this->_bindTypes[$name] = Column::BIND_PARAM_BOOL;
                                $exprReturn              = ["type" => "placeholder", "value" => ":" . $name];
                                break;

                            case "blob":
                                $this->_bindTypes[$name] = Column::BIND_PARAM_BLOB;
                                $exprReturn              = ["type" => "placeholder", "value" => ":" . $name];
                                break;

                            case "null":
                                $this->_bindTypes[$name] = Column::BIND_PARAM_NULL;
                                $exprReturn              = ["type" => "placeholder", "value" => ":" . $name];
                                break;

                            case "array":
                            case "array-str":
                            case "array-int":

                                if (!isset($this->_bindParams[$name])) {
                                    throw new Exception("Bind value is required for array type placeholder: " . $name);
                                }

                                $bind = $this->_bindParams[$name];
                                if (!is_array($bind)) {
                                    throw new Exception("Bind type requires an array in placeholder: " . $name);
                                }

                                if (count($bind) < 1) {
                                    throw new Exception("At least one value must be bound in placeholder: " . $name);
                                }

                                $exprReturn = [
                                    "type"     => "placeholder",
                                    "value"    => ":" . $name,
                                    "rawValue" => $name,
                                    "times"    => count($bind)
                                ];
                                break;

                            default:
                                throw new Exception("Unknown bind type: " . $bindType);
                        }
                    } else {
                        $exprReturn = ["type" => "placeholder", "value" => ":" . $value];
                    }
                    break;

                case Lang::PHQL_T_NULL:
                    $exprReturn = ["type" => "literal", "value" => "NULL"];
                    break;

                case Lang::PHQL_T_LIKE:
                    $exprReturn = ["type" => "binary-op", "op" => "LIKE", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_NLIKE:
                    $exprReturn = ["type" => "binary-op", "op" => "NOT LIKE", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_ILIKE:
                    $exprReturn = ["type" => "binary-op", "op" => "ILIKE", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_NILIKE:
                    $exprReturn = ["type" => "binary-op", "op" => "NOT ILIKE", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_NOT:
                    $exprReturn = ["type" => "unary-op", "op" => "NOT ", "right" => $right];
                    break;

                case Lang::PHQL_T_ISNULL:
                    $exprReturn = ["type" => "unary-op", "op" => " IS NULL", "left" => $left];
                    break;

                case Lang::PHQL_T_ISNOTNULL:
                    $exprReturn = ["type" => "unary-op", "op" => " IS NOT NULL", "left" => $left];
                    break;

                case Lang::PHQL_T_IN:
                    $exprReturn = ["type" => "binary-op", "op" => "IN", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_NOTIN:
                    $exprReturn = ["type" => "binary-op", "op" => "NOT IN", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_EXISTS:
                    $exprReturn = ["type" => "unary-op", "op" => "EXISTS", "right" => $right];
                    break;

                case Lang::PHQL_T_DISTINCT:
                    $exprReturn = ["type" => "unary-op", "op" => "DISTINCT ", "right" => $right];
                    break;

                case Lang::PHQL_T_BETWEEN:
                    $exprReturn = ["type" => "binary-op", "op" => "BETWEEN", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_AGAINST:
                    $exprReturn = ["type" => "binary-op", "op" => "AGAINST", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_CAST:
                    $exprReturn = ["type" => "cast", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_CONVERT:
                    $exprReturn = ["type" => "convert", "left" => $left, "right" => $right];
                    break;

                case Lang::PHQL_T_RAW_QUALIFIED:
                    $exprReturn = ["type" => "literal", "value" => $expr["name"]];
                    break;

                case Lang::PHQL_T_FCALL:
                    $exprReturn = $this->_getFunctionCall($expr);
                    break;

                case Lang::PHQL_T_CASE:
                    $exprReturn = $this->_getCaseExpression($expr);
                    break;

                case Lang::PHQL_T_SELECT:
                    $exprReturn = ["type" => "select", "value" => $this->_prepareSelect($expr, true)];
                    break;

                default:
                    codecept_debug(json_encode($expr));
                    codecept_debug(json_encode($this->_ast));
                    throw new Exception("Unknown expression type " . $exprType);
            }

            return $exprReturn;
        }

        /**
         * It's a qualified column
         */
        if (isset($expr["domain"])) {
            return $this->_getQualified($expr);
        }

        /**
         * If the expression doesn't have a type it's a list of nodes
         */
        if (isset($expr[0])) {
            $listItems = [];
            foreach ($expr as $exprListItem) {
                $listItems[] = $this->_getExpression($exprListItem);
            }
            return ["type" => "list", $listItems];
        }

        throw new Exception("Unknown expression");
    }

    /**
     * Escapes single quotes into database single quotes
     *
     * @param string $value
     * @return string
     */
    private static function singleQuotes($value)
    {
        if (is_string($value) === false) {
            return '';
        }
        $esc = '';

        $l = strlen($value);
        $n = chr(0);
        for ($i = 0; $i < $l; ++$i) {
            if ($value[$i] === $n) {
                break;
            }

            if ($value[$i] === '\'') {
                if ($i > 0) {
                    if ($value[$i - 1] != '\\') {
                        $esc .= '\'';
                    }
                } else {
                    $esc .= '\'';
                }
            }

            $esc .= $value[$i];
        }

        return $esc;
    }

    /**
     * Resolves a column from its intermediate representation into an array used to determine
     * if the resulset produced is simple or complex
     *
     * @param array $column
     * @return array
     * @throws Exception
     */
    protected function _getSelectColumn(array $column)
    {
        if (!isset($column["type"])) {
            throw new Exception("Corrupted SELECT AST");
        }
        $columnType = $column["type"];
        $sqlColumns = [];

        /**
         * Check if column is eager loaded
         */
        $eager = isset($column["eager"]) ? $column["eager"] : null;

        /**
         * Check for select * (all)
         */
        if ($columnType == Lang::PHQL_T_STARALL) {
            foreach ($this->_models as $modelName => $source) {

                $sqlColumn = [
                    "type"   => "object",
                    "model"  => $modelName,
                    "column" => $source,
                    "balias" => lcfirst($modelName)
                ];

                if ($eager !== null) {
                    $sqlColumn["eager"]     = $eager;
                    $sqlColumn["eagerType"] = $column["eagerType"];
                }

                $sqlColumns[] = $sqlColumn;
            }
            return $sqlColumns;
        }

        if (!isset($column["column"])) {
            throw new Exception("Corrupted SELECT AST");
        }

        /**
         * Check if selected column is qualified.*, ex: robots.*
         */
        if ($columnType == Lang::PHQL_T_DOMAINALL) {

            $sqlAliases = $this->_sqlAliases;

            /**
             * We only allow the alias.*
             */
            $columnDomain = $column["column"];

            if (!isset($sqlAliases[$columnDomain])) {
                throw new Exception("Unknown model or alias '" . $columnDomain . "' (2), when preparing: " . $this->_phql);
            }
            $source         = $sqlAliases[$columnDomain];
            /**
             * Get the SQL alias if any
             */
            $sqlColumnAlias = $source;

            $preparedAlias = isset($column["balias"]) ? $column["balias"] : null;

            /**
             * Get the real source name
             */
            $sqlAliasesModels = $this->_sqlAliasesModels;
            $modelName        = $sqlAliasesModels[$columnDomain];

            if (!is_string($preparedAlias)) {
                /**
                 * If the best alias is the model name, we lowercase the first letter
                 */
                if ($columnDomain == $modelName) {
                    $preparedAlias = lcfirst($modelName);
                } else {
                    $preparedAlias = $columnDomain;
                }
            }

            /**
             * Each item is a complex type returning a complete object
             */
            $sqlColumn = [
                "type"   => "object",
                "model"  => $modelName,
                "column" => $sqlColumnAlias,
                "balias" => $preparedAlias
            ];

            if ($eager !== null) {
                $sqlColumn["eager"]     = $eager;
                $sqlColumn["eagerType"] = $column["eagerType"];
            }

            $sqlColumns[] = $sqlColumn;

            return $sqlColumns;
        }

        /**
         * Check for columns qualified and not qualified
         */
        if ($columnType == Lang::PHQL_T_EXPR) {
            /**
             * The sql_column is a scalar type returning a simple string
             */
            $sqlColumn     = ["type" => "scalar"];
            $columnData    = $column["column"];
            $sqlExprColumn = $this->_getExpression($columnData);

            /**
             * Create balias and sqlAlias
             */
            if (isset($sqlExprColumn["balias"])) {
                $balias                = $sqlExprColumn["balias"];
                $sqlColumn["balias"]   = $balias;
                $sqlColumn["sqlAlias"] = $balias;
            }

            if ($eager !== null) {
                $sqlColumn["eager"]     = $eager;
                $sqlColumn["eagerType"] = $column["eagerType"];
            }

            $sqlColumn["column"] = $sqlExprColumn;
            $sqlColumns[]        = $sqlColumn;

            return $sqlColumns;
        }

        throw new Exception("Unknown type of column " . $columnType);
    }

    /**
     * Resolves a table in a SELECT statement checking if the model exists
     *
     * @param \Phalcon\Mvc\Model\ManagerInterface $manager
     * @param array $qualifiedName
     * @return string
     * @throws Exception
     */
    protected function _getTable(ManagerInterface $manager, array $qualifiedName)
    {
        if (isset($qualifiedName['name']) === true) {
            $model = $manager->load($qualifiedName['name']);

            $schema = $model->getSchema();
            if ($schema == true) {
                return array($schema, $model->getSource());
            }
            return $model->getSource();
        }

        throw new Exception('Corrupted SELECT AST');
    }

    /**
     * Resolves a JOIN clause checking if the associated models exist
     *
     * @param \Phalcon\Mvc\Model\ManagerInterface $manager
     * @param array $join
     * @return array
     * @throws Exception
     */
    protected function _getJoin(ManagerInterface $manager, array $join)
    {
        if (isset($join["qualified"])) {

            $qualified = $join["qualified"];
            if ($qualified["type"] == Lang::PHQL_T_QUALIFIED) {

                $modelName = $qualified["name"];

                if (Text::memstr($modelName, ":")) {
                    $nsAlias       = explode(":", $modelName);
                    $realModelName = $manager->getNamespaceAlias(nsAlias[0]) . "\\" . $nsAlias[1];
                } else {
                    $realModelName = $modelName;
                }

                $model  = $manager->load($realModelName, true);
                $source = $model->getSource();
                $schema = $model->getSchema();
                return [
                    "schema"    => $schema,
                    "source"    => $source,
                    "modelName" => $realModelName,
                    "model"     => $model
                ];
            }
        }

        throw new Exception("Corrupted SELECT AST");
    }

    /**
     * Resolves a JOIN type
     *
     * @param array $join
     * @return string
     * @throws Exception
     */
    protected function _getJoinType(array $join)
    {
        if (isset($join['type']) === false) {
            throw new Exception('Corrupted SELECT AST');
        }
        $type = $join['type'];
        switch ($type) {

            case Lang::PHQL_T_INNERJOIN:
                return "INNER";

            case Lang::PHQL_T_LEFTJOIN:
                return "LEFT";

            case Lang::PHQL_T_RIGHTJOIN:
                return "RIGHT";

            case Lang::PHQL_T_CROSSJOIN:
                return "CROSS";

            case Lang::PHQL_T_FULLJOIN:
                return "FULL OUTER";
        }

        throw new Exception("Unknown join type " . $type . ", when preparing: " . $this->_phql);
    }

    /**
     * Resolves joins involving has-one/belongs-to/has-many relations
     *
     * @param string $joinType
     * @param mixed $joinSource
     * @param mixed $modelAlias
     * @param mixed $joinAlias
     * @param \Phalcon\Mvc\Model\RelationInterface $relation
     * @return array
     * @throws Exception
     */
    protected function _getSingleJoin($joinType, $joinSource, $modelAlias, $joinAlias, RelationInterface $relation)
    {
        if (is_string($joinType) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Local fields in the 'from' relation
        $fields = $relation->getFields();

        //Referenced fields in the joined relation
        $referencedFields = $relation->getReferencedFields();

        if (is_array($fields) === false) {
            /**
             * Create the left part of the expression
             * Create a binary operation for the join conditions
             * Create the right part of the expression
             */
            $sqlJoinConditions = [
                [
                    "type"  => "binary-op",
                    "op"    => "=",
                    "left"  => $this->_getQualified([
                        "type"   => Lang::PHQL_T_QUALIFIED,
                        "domain" => $modelAlias,
                        "name"   => $fields
                    ]),
                    "right" => $this->_getQualified([
                        "type"   => "qualified",
                        "domain" => $joinAlias,
                        "name"   => $referencedFields
                    ])
                ]
            ];
        } else {
            /**
             * Resolve the compound operation
             */
            $sqlJoinPartialConditions = [];
            foreach ($fields as $position => $field) {

                /**
                 * Get the referenced field in the same position
                 */
                if (!isset($referencedFields[$position])) {
                    throw new Exception(
                    "The number of fields must be equal to the number of referenced fields in join " . $modelAlias . "-" . $joinAlias . ", when preparing: " . $this->_phql
                    );
                }

                $referencedField = $referencedFields[$position];

                /**
                 * Create the left part of the expression
                 * Create the right part of the expression
                 * Create a binary operation for the join conditions
                 */
                $sqlJoinPartialConditions[] = [
                    "type"  => "binary-op",
                    "op"    => "=",
                    "left"  => $this->_getQualified([
                        "type"   => Lang::PHQL_T_QUALIFIED,
                        "domain" => $modelAlias,
                        "name"   => $field
                    ]),
                    "right" => $this->_getQualified([
                        "type"   => "qualified",
                        "domain" => $joinAlias,
                        "name"   => $referencedField
                    ])
                ];
            }
        }

        //A single join
        return [
            'type'       => $joinType,
            'source'     => $joinSource,
            'conditions' => $sqlJoinConditions
        ]; //@note sql_join_conditions is not set when $fields is an array
    }

    /**
     * Resolves joins involving many-to-many relations
     *
     * @param string $joinType
     * @param string $joinSource
     * @param string $modelAlias
     * @param string $joinAlias
     * @param \Phalcon\Mvc\Model\RelationInterface $relation
     * @return array
     * @throws Exception
     */
    protected function _getMultiJoin($joinType, $joinSource, $modelAlias, $joinAlias, RelationInterface $relation)
    {
        $sqlJoins = [];

        /**
         * Local fields in the 'from' relation
         */
        $fields = $relation->getFields();

        /**
         * Referenced fields in the joined relation
         */
        $referencedFields = $relation->getReferencedFields();

        /**
         * Intermediate model
         */
        $intermediateModelName = $relation->getIntermediateModel();

        $manager = $this->_manager;

        /**
         * Get the intermediate model instance
         */
        $intermediateModel = $manager->load(intermediateModelName);

        /**
         * Source of the related model
         */
        $intermediateSource = $intermediateModel->getSource();

        /**
         * Schema of the related model
         */
        $intermediateSchema = $intermediateModel->getSchema();

        //intermediateFullSource = array(intermediateSchema, intermediateSource);

        /**
         * Update the internal sqlAliases to set up the intermediate model
         */
        $this->_sqlAliases[$intermediateModelName] = $intermediateSource;

        /**
         * Update the internal _sqlAliasesModelsInstances to rename columns if necessary
         */
        $this->_sqlAliasesModelsInstances[$intermediateModelName] = $intermediateModel;

        /**
         * Fields that join the 'from' model with the 'intermediate' model
         */
        $intermediateFields = $relation->getIntermediateFields();

        /**
         * Fields that join the 'intermediate' model with the intermediate model
         */
        $intermediateReferencedFields = $relation->getIntermediateReferencedFields();

        /**
         * Intermediate model
         */
        $referencedModelName = $relation->getReferencedModel();

        if (is_array($fields)) {

            foreach ($fields as $field => $position) {

                if (!isset($referencedFields[$position])) {
                    throw new Exception(
                    "The number of fields must be equal to the number of referenced fields in join " . $modelAlias . "-" . $joinAlias . ", when preparing: " . $this->_phql
                    );
                }

                /**
                 * Get the referenced field in the same position
                 */
                $intermediateField = $intermediateFields[$position];

                /**
                 * Create a binary operation for the join conditions
                 */
                $sqlEqualsJoinCondition = [
                    "type"  => "binary-op",
                    "op"    => "=",
                    "left"  => $this->_getQualified([
                        "type"   => Lang::PHQL_T_QUALIFIED,
                        "domain" => $modelAlias,
                        "name"   => $field
                    ]),
                    "right" => $this->_getQualified([
                        "type"   => "qualified",
                        "domain" => $joinAlias,
                        "name"   => $referencedFields
                    ])
                ];

                //$sqlJoinPartialConditions[] = sqlEqualsJoinCondition;
            }
        } else {

            /**
             * Create the left part of the expression
             * Create the right part of the expression
             * Create a binary operation for the join conditions
             * A single join
             */
            $sqlJoins = [
                [
                    "type"       => $joinType,
                    "source"     => $intermediateSource,
                    "conditions" => [[
                        "type"  => "binary-op",
                        "op"    => "=",
                        "left"  => $this->_getQualified([
                            "type"   => Lang::PHQL_T_QUALIFIED,
                            "domain" => $modelAlias,
                            "name"   => $fields
                        ]),
                        "right" => $this->_getQualified([
                            "type"   => "qualified",
                            "domain" => $intermediateModelName,
                            "name"   => $intermediateFields
                        ])
                        ]]
                ],
                /**
                 * Create the left part of the expression
                 * Create the right part of the expression
                 * Create a binary operation for the join conditions
                 * A single join
                 */
                [
                    "type"       => $joinType,
                    "source"     => $joinSource,
                    "conditions" => [[
                        "type"  => "binary-op",
                        "op"    => "=",
                        "left"  => $this->_getQualified([
                            "type"   => Lang::PHQL_T_QUALIFIED,
                            "domain" => $intermediateModelName,
                            "name"   => $intermediateReferencedFields
                        ]),
                        "right" => $this->_getQualified([
                            "type"   => "qualified",
                            "domain" => $referencedModelName,
                            "name"   => $referencedFields
                        ])
                        ]]
                ]
            ];
        }

        return $sqlJoins;
    }

    /**
     * Processes the JOINs in the query returning an internal representation for the database dialect
     *
     * @param array $select
     * @return array
     * @throws Exception
     */
    protected function _getJoins(array $select)
    {
        $models                    = $this->_models;
        $sqlAliases                = $this->_sqlAliases;
        $sqlAliasesModels          = $this->_sqlAliasesModels;
        $sqlModelsAliases          = $this->_sqlModelsAliases;
        $sqlAliasesModelsInstances = $this->_sqlAliasesModelsInstances;
        $modelsInstances           = $this->_modelsInstances;
        $fromModels                = $models;
        $manager                   = $this->_manager;

        $sqlJoins         = [];
        $joinModels       = [];
        $joinSources      = [];
        $joinTypes        = [];
        $joinPreCondition = [];
        $joinPrepared     = [];

        if (isset($select['tables'][0]) === false) {
            $selectTables = array($select['tables']);
        } else {
            $selectTables = $select['tables'];
        }

        if (isset($select['joins'][0]) === false) {
            $selectJoins = array($select['joins']);
        } else {
            $selectJoins = $select['joins'];
        }
        foreach ($selectJoins as $joinItem) {
            //Check join alias
            $joinData       = $this->_getJoin($manager, $joinItem);
            $source         = $joinData['source'];
            $schema         = $joinData['schema'];
            $model          = $joinData['model'];
            $realModelName  = $joinData['modelName'];
            $completeSource = array($source, $schema);

            //Check join alias
            $joinType = $this->_getJoinType($joinItem);

            //Process join alias
            if (isset($joinItem['alias']) === true) {
                $alias = $joinItem['alias']['name'];

                //Check if alias is unique
                if (isset($joinModels[$alias]) === true) {
                    throw new Exception("Cannot use '" . $alias . "' as join alias because it was already used, when preparing: " . $this->_phql);
                }

                //Add the alias to the source
                $completeSource[] = $alias;

                //Set the join type
                $joinTypes[$alias] = $joinType;

                //Update alias => $alias
                $sqlAliases[$alias] = $alias;

                //Update model => alias
                $joinModels[$alias]                = $realModelName;
                $sqlModelsAliases[$realModelName]  = $alias;
                $sqlAliasesModels[$alias]          = $realModelName;
                $sqlAliasesModelsInstances[$alias] = $model;

                //Update model => alias
                $models[$realModelName] = $alias;

                //Complete source related to a model
                $joinSources[$alias]  = $completeSource;
                $joinPrepared[$alias] = $joinItem;
            } else {
                //Check if alias is unique
                if (isset($joinModels[$realModelName]) === true) {
                    throw new Exception("Cannot use '" . $realModelName . "' as join because it was already used, when preparing: " . $this->_phql);
                }

                //Set the join type
                $joinTypes[$realModelName] = $joinType;

                //Update model => source
                $sqlAliases[$realModelName] = $source;
                $joinModels[$realModelName] = $source;

                //Update model => model
                $sqlModelsAliases[$realModelName] = $realModelName;
                $sqlAliasesModels[$realModelName] = $realModelName;

                //Update model => model instances
                $sqlAliasesModelsInstances[$realModelName] = $model;

                //Update model => source
                $models[$realModelName] = $source;

                //Complete source related to a model
                $joinSources[$realModelName]  = $completeSource;
                $joinPrepared[$realModelName] = $joinItem;
            }

            $modelsInstances[$realModelName] = $model;
        }

        //Update temporary properties
        $this->_models                    = $models;
        $this->_sqlAliases                = $sqlAliases;
        $this->_sqlAliasesModels          = $sqlAliasesModels;
        $this->_sqlModelsAliases          = $sqlModelsAliases;
        $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;
        $this->_modelsInstances           = $modelsInstances;

        foreach ($joinPrepared as $joinAliasName => $joinItem) {
            //Check for predefined conditions
            if (isset($joinItem['conditions']) === true) {
                $joinPreCondition[$joinAliasName] = $this->_getExpression($joinItem['conditions']);
            }
        }

        /**
         * Skip all implicit joins if the option is not enabled
         */
        $this->_enableImplicitJoins = true;
        if (!$this->_enableImplicitJoins) {
            foreach ($joinPrepared as $joinAliasName => $_) {
                $joinType     = $joinTypes[$joinAliasName];
                $joinSource   = $joinSources[$joinAliasName];
                $preCondition = isset($joinPreCondition[$joinAliasName]) ? $joinPreCondition[$joinAliasName] : null;
                $condition    = empty($preCondition) ? [] : [$preCondition];
                $sqlJoins[]   = [
                    "type"       => $joinType,
                    "source"     => $joinSource,
                    "conditions" => $condition
                ];
            }
            return $sqlJoins;
        }

        /**
         * Build the list of tables used in the SELECT clause
         */
        $fromModels = [];
        foreach ($selectTables as $tableItem) {
            $fromModels[$tableItem["qualifiedName"]["name"]] = true;
        }


        //Create join relationships dynamically
        foreach ($fromModels as $fromModelName => $_) {
            foreach ($joinModels as $joinAlias => $joinModel) {
                //Real source name for joined model
                $joinSource = $joinSources[$joinAlias];
                //Join type is: LEFT, RIGHT, INNER, etc.
                $joinType   = $joinTypes[$joinAlias];

                //Check if the model already has pre-defined conditions
                if (isset($joinPreCondition[$joinAlias]) === false) {
                    //Get the model name from its source
                    $modelNameAlias = $sqlAliasesModels[$joinAlias];

                    //Check if the joined model is an alias
                    $relation = $manager->getRelationByAlias($fromModelName, $modelNameAlias);
                    if ($relation === false) {
                        $relations = $manager->getRelationsBetween($fromModelName, $modelNameAlias);
                        if (is_array($relations) === true) {
                            //More than one relation must throw an exception
                            $numberRelations = count($relations);
                            if ($numberRelations !== 1) {
                                throw new Exception("There is more than one relation between models '" . $realModelName . "' and '" . $joinModel . '", the join must be done using an alias, when preparing: ' . $this->_phql);
                            }

                            //Get the first relationship
                            $relation = $relations[0];
                        }
                    }

                    //Valid relations are objects
                    if (is_object($relation) === true) {
                        //Get the related model alias of the left part
                        $modelAlias = $sqlModelsAliases[$fromModelName];

                        //Generate the conditions based on the type of join
                        if (!$relation->isThrough()) {
                            $sqlJoin = $this->_getSingleJoin($joinType, $joinSource, $modelAlias, $joinAlias, $relation); //no Many-To-Many
                        } else {
                            $sqlJoin = $this->_getMultiJoin($joinType, $joinSource, $modelAlias, $joinAlias, $relation);
                        }

                        //Append or merge joins
                        if (isset($sqlJoin[0])) {
                            foreach ($sqlJoin as $sqlJoinItem) {
                                $sqlJoins[] = $sqlJoinItem;
                            }
                        } else {
                            $sqlJoins[] = $sqlJoin;
                        }
                    } else {
                        //Join without conditions because no relation has been found between the models
                        $sqlJoins[] = [
                            'type'       => $joinType,
                            'source'     => $joinSource,
                            'conditions' => []
                        ];
                    }
                } else {
                    $preCondition = $joinPreCondition[$joinAlias];
                    //Join with conditions established  by the devleoper
                    $sqlJoins[]   = [
                        'type'       => $joinType,
                        'source'     => $joinSource,
                        'conditions' => [$preCondition]
                    ];
                }
            }
        }

        return $sqlJoins;
    }

    /**
     * Returns a processed order clause for a SELECT statement
     *
     * @param array $order
     * @return string
     */
    protected function _getOrderClause(array $order)
    {
        if (isset($order[0]) === false) {
            $orderColumns = [$order];
        } else {
            $orderColumns = $order;
        }

        $orderParts = [];

        foreach ($orderColumns as $orderItem) {
            $orderPartExpr = $this->_getExpression($orderItem['column']);

            //Check if the order has a predefined ordering mode
            if (isset($orderItem['sort']) === true) {
                if ($orderItem['sort'] === Lang::PHQL_T_ASC) {
                    $orderPartSort = array($orderPartExpr, 'ASC');
                } else {
                    $orderPartSort = array($orderPartExpr, 'DESC');
                }
            } else {
                $orderPartSort = array($orderPartExpr);
            }

            $orderParts[] = $orderPartSort;
        }

        return $orderParts;
    }

    /**
     * Returns a processed group clause for a SELECT statement
     *
     * @param array $group
     * @return string
     */
    protected function _getGroupClause(array $group)
    {
        if (isset($group[0]) === true) {
            //The SELECT is grouped by several columns
            $groupParts = [];
            foreach ($group as $groupItem) {
                $groupParts[] = $this->_getExpression($groupItem);
            }
        } else {
            $groupParts = array($this->_getExpression($group));
        }

        return $groupParts;
    }

    /**
     * Returns a processed limit clause for a SELECT statement
     */
    protected final function _getLimitClause(array $limitClause)
    {
        $limit = [];

        if (isset($limitClause["number"])) {
            $limit["number"] = $this->_getExpression($limitClause["number"]);
        }

        if (isset($limitClause["offset"])) {
            $limit["offset"] = $this->_getExpression($limitClause["offset"]);
        }

        return $limit;
    }

    /**
     * Analyzes a SELECT intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareSelect($ast = null, $merge = null)
    {
        if (empty($ast)) {
            $ast = $this->_ast;
        }

        if ($merge == null) {
            $merge = false;
        }

        $select = isset($ast["select"]) ? $ast["select"] : $ast;
        if (!isset($select["tables"])) {
            throw new Exception("Corrupted SELECT AST");
        }

        $tables = $select["tables"];

        if (!isset($select["columns"])) {
            throw new Exception("Corrupted SELECT AST");
        }
        $columns = $select["columns"];

        /**
         * sqlModels is an array of the models to be used in the query
         */
        $sqlModels = [];

        /**
         * sqlTables is an array of the mapped models sources to be used in the query
         */
        $sqlTables = [];

        /**
         * sqlColumns is an array of every column expression
         */
        $sqlColumns = [];

        /**
         * sqlAliases is a map from aliases to mapped sources
         */
        $sqlAliases = [];

        /**
         * sqlAliasesModels is a map from aliases to model names
         */
        $sqlAliasesModels = [];

        /**
         * sqlAliasesModels is a map from model names to aliases
         */
        $sqlModelsAliases = [];

        /**
         * sqlAliasesModelsInstances is a map from aliases to model instances
         */
        $sqlAliasesModelsInstances = [];

        /**
         * Models information
         */
        $models          = [];
        $modelsInstances = [];

        // Convert selected models in an array
        if (!isset($tables[0])) {
            $selectedModels = [$tables];
        } else {
            $selectedModels = $tables;
        }

        // Convert selected columns in an array
        if (!isset($columns[0])) {
            $selectColumns = [$columns];
        } else {
            $selectColumns = $columns;
        }

        $manager  = $this->_manager;
        $metaData = $this->_metaData;

        if (!is_object($manager)) {
            throw new Exception("A models-manager is required to execute the query");
        }

        if (!is_object($metaData)) {
            throw new Exception("A meta-data is required to execute the query");
        }

        // Process selected models
        $number         = 0;
        $automaticJoins = [];

        foreach ($selectedModels as $selectedModel) {

            $qualifiedName = $selectedModel["qualifiedName"];
            $modelName     = $qualifiedName["name"];

            // Check if the table has a namespace alias
            if (Text::memstr($modelName, ":")) {
                $nsAlias       = explode(":", $modelName);
                $realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];
            } else {
                $realModelName = $modelName;
            }

            // Load a model instance from the models manager
            $model = $manager->load($realModelName, true);

            // Define a complete schema/source
            $schema = $model->getSchema();
            $source = $model->getSource();

            // Obtain the real source including the schema
            if ($schema) {
                $completeSource = [$source, $schema];
            } else {
                $completeSource = $source;
            }

            // If an alias is defined for a model then the model cannot be referenced in the column list
            if (isset($selectedModel["alias"])) {

                $alias = $selectedModel["alias"];
                // Check if the alias was used before
                if (isset($sqlAliases[$alias])) {
                    throw new Exception("Alias '" . $alias . "' is used more than once, when preparing: " . $this->_phql);
                }

                $sqlAliases[$alias]                = $alias;
                $sqlAliasesModels[$alias]          = $realModelName;
                $sqlModelsAliases[$realModelName]  = $alias;
                $sqlAliasesModelsInstances[$alias] = $model;

                /**
                 * Append or convert complete source to an array
                 */
                if (is_array($completeSource)) {
                    $completeSource[] = $alias;
                } else {
                    $completeSource = [$source, null, $alias];
                }
                $models[$realModelName] = $alias;
            } else {
                $alias                                     = $source;
                $sqlAliases[$realModelName]                = $source;
                $sqlAliasesModels[$realModelName]          = $realModelName;
                $sqlModelsAliases[$realModelName]          = $realModelName;
                $sqlAliasesModelsInstances[$realModelName] = $model;
                $models[$realModelName]                    = $source;
            }

            // Eager load any specified relationship(s)
            if (isset($selectedModel["with"])) {
                $with = $selectedModel["with"];
                if (!isset($with[0])) {
                    $withs = [$with];
                } else {
                    $withs = $with;
                }

                // Simulate the definition of inner joins
                foreach ($withs as $withItem) {

                    $joinAlias     = "AA" . $number;
                    $relationModel = $withItem["name"];
                    $relation      = $manager->getRelationByAlias($realModelName, $relationModel);

                    if (is_object($relation)) {
                        $bestAlias     = $relation->getOption("alias");
                        $relationModel = $relation->getReferencedModel();
                        $eagerType     = $relation->getType();
                    } else {
                        $relation = $manager->getRelationsBetween($realModelName, $relationModel);
                        if (is_object($relation)) {
                            $bestAlias     = $relation->getOption("alias");
                            $relationModel = $relation->getReferencedModel();
                            $eagerType     = $relation->getType();
                        } else {
                            throw new Exception(
                            "Can't find a relationship between '" . $realModelName . "' and '" . $relationModel . "' when preparing: " . $this->_phql
                            );
                        }
                    }

                    $selectColumns[] = [
                        "type"      => Lang::PHQL_T_DOMAINALL,
                        "column"    => $joinAlias,
                        "eager"     => $alias,
                        "eagerType" => $eagerType,
                        "balias"    => $bestAlias
                    ];

                    $automaticJoins[] = [
                        "type"      => Lang::PHQL_T_INNERJOIN,
                        "qualified" => [
                            "type" => Lang::PHQL_T_QUALIFIED,
                            "name" => $relationModel
                        ],
                        "alias"     => [
                            "type" => Lang::PHQL_T_QUALIFIED,
                            "name" => $joinAlias
                        ]
                    ];

                    $number++;
                }
            }

            $sqlModels[]                     = $realModelName;
            $sqlTables[]                     = $completeSource;
            $modelsInstances[$realModelName] = $model;
        }

        // Assign Models/Tables information
        if (!$merge) {
            $this->_models                    = $models;
            $this->_modelsInstances           = $modelsInstances;
            $this->_sqlAliases                = $sqlAliases;
            $this->_sqlAliasesModels          = $sqlAliasesModels;
            $this->_sqlModelsAliases          = $sqlModelsAliases;
            $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;
        } else {

            $tempModels                    = $this->_models;
            $tempModelsInstances           = $this->_modelsInstances;
            $tempSqlAliases                = $this->_sqlAliases;
            $tempSqlAliasesModels          = $this->_sqlAliasesModels;
            $tempSqlModelsAliases          = $this->_sqlModelsAliases;
            $tempSqlAliasesModelsInstances = $this->_sqlAliasesModelsInstances;

            $this->_models                    = array_merge($this->_models, $models);
            $this->_modelsInstances           = array_merge($this->_modelsInstances, $modelsInstances);
            $this->_sqlAliases                = array_merge($this->_sqlAliases, $sqlAliases);
            $this->_sqlAliasesModels          = array_merge($this->_sqlAliasesModels, $sqlAliasesModels);
            $this->_sqlModelsAliases          = array_merge($this->_sqlModelsAliases, $sqlModelsAliases);
            $this->_sqlAliasesModelsInstances = array_merge($this->_sqlAliasesModelsInstances, $sqlAliasesModelsInstances);
        }

        $joins = isset($select["joins"]) ? $select["joins"] : null;
        // Join existing JOINS with automatic Joins
        if (count($joins)) {
            if (count($automaticJoins)) {
                if (isset($joins[0])) {
                    $select["joins"] = array_merge($joins, $automaticJoins);
                } else {
                    $automaticJoins[] = $joins;
                    $select["joins"]  = $automaticJoins;
                }
            }
            $sqlJoins = $this->_getJoins($select);
        } else {
            if (count($automaticJoins)) {
                $select["joins"] = $automaticJoins;
                $sqlJoins        = $this->_getJoins($select);
            } else {
                $sqlJoins = [];
            }
        }
        // Resolve selected columns
        $position         = 0;
        $sqlColumnAliases = [];

        foreach ($selectColumns as $column) {

            foreach ($this->_getSelectColumn($column) as $sqlColumn) {
                /**
                 * If "alias" is set, the user defined an alias for the column
                 */
                if (isset($column["alias"])) {
                    $alias                    = $column["alias"];
                    /**
                     * The best alias is the one provided by the user
                     */
                    $sqlColumn["balias"]      = $alias;
                    $sqlColumn["sqlAlias"]    = $alias;
                    $sqlColumns[$alias]       = $sqlColumn;
                    $sqlColumnAliases[$alias] = true;
                } else {
                    /**
                     * "balias" is the best alias chosen for the column
                     */
                    if (isset($sqlColumn["balias"])) {
                        $alias              = $sqlColumn["balias"];
                        $sqlColumns[$alias] = $sqlColumn;
                    } else {
                        if ($sqlColumn["type"] == "scalar") {
                            $sqlColumns["_" . $position] = $sqlColumn;
                        } else {
                            $sqlColumns[] = $sqlColumn;
                        }
                    }
                }

                $position++;
            }
        }
        $this->_sqlColumnAliases = $sqlColumnAliases;

        // sqlSelect is the final prepared SELECT
        $sqlSelect = [
            "models"  => $sqlModels,
            "tables"  => $sqlTables,
            "columns" => $sqlColumns
        ];

        if (isset($select["distinct"])) {
            $sqlSelect["distinct"] = $select["distinct"];
        }

        if (count($sqlJoins)) {
            $sqlSelect["joins"] = $sqlJoins;
        }

        // Process "WHERE" clause if set
        if (isset($ast["where"])) {
            $sqlSelect["where"] = $this->_getExpression($ast["where"]);
        }

        // Process "GROUP BY" clause if set
        if (isset($ast["groupBy"])) {
            $sqlSelect["group"] = $this->_getGroupClause($ast["groupBy"]);
        }

        // Process "HAVING" clause if set
        if (isset($ast["having"])) {
            $sqlSelect["having"] = $this->_getExpression($ast["having"]);
        }

        // Process "ORDER BY" clause if set
        if (isset($ast["orderBy"])) {
            $sqlSelect["order"] = $this->_getOrderClause($ast["orderBy"]);
        }

        // Process "LIMIT" clause if set
        if (isset($ast["limit"])) {
            $sqlSelect["limit"] = $this->_getLimitClause($ast["limit"]);
        }

        // Process "FOR UPDATE" clause if set
        if (isset($ast["forUpdate"])) {
            $sqlSelect["forUpdate"] = true;
        }

        if ($merge) {
            $this->_models                    = $tempModels;
            $this->_modelsInstances           = $tempModelsInstances;
            $this->_sqlAliases                = $tempSqlAliases;
            $this->_sqlAliasesModels          = $tempSqlAliasesModels;
            $this->_sqlModelsAliases          = $tempSqlModelsAliases;
            $this->_sqlAliasesModelsInstances = $tempSqlAliasesModelsInstances;
        }

        return $sqlSelect;
    }

    /**
     * Analyzes an INSERT intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareInsert()
    {
        $ast = $this->_ast;
        if (isset($ast['qualifiedName']) === false ||
            isset($ast['values']) === false ||
            isset($ast['qualifiedName']['name']) === false) {
            throw new Exception('Corrupted INSERT AST');
        }

        $qualifiedName = $ast["qualifiedName"];
        $manager       = $this->_manager;
        $modelName     = $qualifiedName["name"];

        // Check if the table have a namespace alias
        if (Text::memstr($modelName, ":")) {
            $nsAlias       = explode(":", $modelName);
            $realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];
        } else {
            $realModelName = $modelName;
        }

        $model  = $manager->load($realModelName, true);
        $source = $model->getSource();
        $schema = $model->getSchema();

        if ($schema) {
            $source = [$schema, $source];
        }

        $notQuoting = false;
        $exprValues = [];

        foreach ($ast["values"] as $exprValue) {

            // Resolve every expression in the "values" clause
            $exprValues[] = [
                "type"  => $exprValue["type"],
                "value" => $this->_getExpression($exprValue, $notQuoting)
            ];
        }

        $sqlInsert = [
            "model" => $modelName,
            "table" => $source
        ];

        $metaData = $this->_metaData;

        if (isset($ast["fields"])) {
            $fields    = $ast["fields"];
            $sqlFields = [];
            foreach ($fields as $field) {
                $name = $field["name"];
                // Check that inserted fields are part of the model
                if (!$metaData->hasAttribute($model, $name)) {
                    throw new Exception(
                    "The model '" . $modelName . "' doesn't have the attribute '" . $name . "', when preparing: " . $this->_phql
                    );
                }
                // Add the file to the insert list
                $sqlFields[] = $name;
            }

            $sqlInsert["fields"] = $sqlFields;
        }

        $sqlInsert["values"] = $exprValues;

        return $sqlInsert;
    }

    /**
     * Analyzes an UPDATE intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareUpdate()
    {
        $ast = $this->_ast;

        if (!isset($ast["update"])) {
            throw new Exception("Corrupted UPDATE AST");
        }

        $update = $ast["update"];
        if (!isset($update["tables"])) {
            throw new Exception("Corrupted UPDATE AST");
        }
        $tables = $update["tables"];

        if (!isset($update["values"])) {
            throw new Exception("Corrupted UPDATE AST");
        }
        $values          = $update["values"];
        /**
         * We use these arrays to store info related to models, alias and its sources. With them we can rename columns later
         */
        $models          = [];
        $modelsInstances = [];

        $sqlTables                 = [];
        $sqlModels                 = [];
        $sqlAliases                = [];
        $sqlAliasesModelsInstances = [];

        if (!isset($tables[0])) {
            $updateTables = [$tables];
        } else {
            $updateTables = $tables;
        }

        $manager = $this->_manager;
        foreach ($updateTables as $table) {
            $qualifiedName = $table["qualifiedName"];
            $modelName     = $qualifiedName["name"];

            /**
             * Check if the table have a namespace alias
             */
            if (Text::memstr($modelName, ":")) {
                $nsAlias       = explode(":", $modelName);
                $realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];
            } else {
                $realModelName = $modelName;
            }

            /**
             * Load a model instance from the models manager
             */
            $model  = $manager->load($realModelName, true);
            $source = $model->getSource();
            $schema = $model->getSchema();

            /**
             * Create a full source representation including schema
             */
            if ($schema) {
                $completeSource = [$source, $schema];
            } else {
                $completeSource = [$source, null];
            }

            /**
             * Check if the table is aliased
             */
            if (isset($table["alias"])) {
                $alias                             = $table["alias"];
                $sqlAliases[$alias]                = $alias;
                $completeSource[]                  = $alias;
                $sqlTables[]                       = $completeSource;
                $sqlAliasesModelsInstances[$alias] = $model;
                $models[$alias]                    = $realModelName;
            } else {
                $sqlAliases[$realModelName]                = $source;
                $sqlAliasesModelsInstances[$realModelName] = $model;
                $sqlTables[]                               = $source;
                $models[$realModelName]                    = $source;
            }

            $sqlModels[]                     = $realModelName;
            $modelsInstances[$realModelName] = $model;
        }

        /**
         * Update the models/alias/sources in the object
         */
        $this->_models                    = $models;
        $this->_modelsInstances           = $modelsInstances;
        $this->_sqlAliases                = $sqlAliases;
        $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;

        $sqlFields = [];
        $sqlValues = [];

        if (!isset($values[0])) {
            $updateValues = [$values];
        } else {
            $updateValues = $values;
        }

        $notQuoting = false;
        foreach ($updateValues as $updateValue) {
            $sqlFields[] = $this->_getExpression($updateValue["column"], $notQuoting);
            $exprColumn  = $updateValue["expr"];
            $sqlValues[] = [
                "type"  => $exprColumn["type"],
                "value" => $this->_getExpression($exprColumn, $notQuoting)
            ];
        }

        $sqlUpdate = [
            "tables" => $sqlTables,
            "models" => $sqlModels,
            "fields" => $sqlFields,
            "values" => $sqlValues
        ];

        if (isset($ast["where"])) {
            $sqlUpdate["where"] = $this->_getExpression($ast["where"], true);
        }

        if (isset($ast["limit"])) {
            $sqlUpdate["limit"] = $this->_getLimitClause($ast["limit"]);
        }

        return $sqlUpdate;
    }

    /**
     * Analyzes a DELETE intermediate code and produces an array to be executed later
     *
     * @return array
     * @throws Exception
     */
    protected function _prepareDelete()
    {
        $ast = $this->_ast;

        if (!isset($ast["delete"])) {
            throw new Exception("Corrupted DELETE AST");
        }
        $delete = $ast["delete"];
        if (!isset($delete["tables"])) {
            throw new Exception("Corrupted DELETE AST");
        }
        $tables          = $delete["tables"];
        /**
         * We use these arrays to store info related to models, alias and its sources.
         * Thanks to them we can rename columns later
         */
        $models          = [];
        $modelsInstances = [];

        $sqlTables                 = [];
        $sqlModels                 = [];
        $sqlAliases                = [];
        $sqlAliasesModelsInstances = [];

        if (!isset($tables[0])) {
            $deleteTables = [$tables];
        } else {
            $deleteTables = $tables;
        }

        $manager = $this->_manager;
        foreach ($deleteTables as $table) {

            $qualifiedName = $table["qualifiedName"];
            $modelName     = $qualifiedName["name"];

            /**
             * Check if the table have a namespace alias
             */
            if (Text::memstr($modelName, ":")) {
                $nsAlias       = explode(":", $modelName);
                $realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];
            } else {
                $realModelName = $modelName;
            }

            /**
             * Load a model instance from the models manager
             */
            $model  = $manager->load($realModelName, true);
            $source = $model->getSource();
            $schema = $model->getSchema();

            if ($schema) {
                $completeSource = [$source, $schema];
            } else {
                $completeSource = [$source, null];
            }

            if (isset($table["alias"])) {
                $alias                             = $table["alias"];
                $sqlAliases[$alias]                = $alias;
                $completeSource[]                  = $alias;
                $sqlTables[]                       = $completeSource;
                $sqlAliasesModelsInstances[$alias] = $model;
                $models[$alias]                    = $realModelName;
            } else {
                $sqlAliases[$realModelName]                = $source;
                $sqlAliasesModelsInstances[$realModelName] = $model;
                $sqlTables[]                               = $source;
                $models[$realModelName]                    = $source;
            }

            $sqlModels[]                     = $realModelName;
            $modelsInstances[$realModelName] = $model;
        }

        /**
         * Update the models/alias/sources in the object
         */
        $this->_models                    = $models;
        $this->_modelsInstances           = $modelsInstances;
        $this->_sqlAliases                = $sqlAliases;
        $this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;

        $sqlDelete           = [];
        $sqlDelete["tables"] = $sqlTables;
        $sqlDelete["models"] = $sqlModels;

        if (isset($ast["where"])) {
            $sqlDelete["where"] = $this->_getExpression($ast["where"], true);
        }

        if (isset($ast["limit"])) {
            $sqlDelete["limit"] = $this->_getLimitClause($ast["limit"]);
        }

        return $sqlDelete;
    }

    /**
     * Parses the intermediate code produced by \Phalcon\Mvc\Model\Query\Lang generating another
     * intermediate representation that could be executed by \Phalcon\Mvc\Model\Query
     *
     * @return array
     * @throws Exception
     */
    public function parse()
    {
        $intermediate = $this->_intermediate;
        if (is_array($intermediate)) {
            return $intermediate;
        }

        /**
         * This function parses the PHQL statement
         */
        $phql     = $this->_phql;
        $ast      = Lang::parsePHQL($phql);
        $irPhql   = null;
        $uniqueId = null;

        if (is_array($ast)) {
            /**
             * Check if the prepared PHQL is already cached
             * Parsed ASTs have a unique id
             */
            if (isset($ast["id"])) {
                $uniqueId = $ast["id"];
                if (isset(self::$_irPhqlCache[$uniqueId])) {
                    $irPhql = self::$_irPhqlCache[$uniqueId];
                    if (is_array($irPhql)) {
                        // Assign the type to the query
                        $this->_type = $ast["type"];
                        return $irPhql;
                    }
                }
            }

            /**
             * A valid AST must have a type
             */
            if (isset($ast["type"])) {
                $type        = $ast["type"];
                $this->_ast  = $ast;
                $this->_type = $type;

                switch ($type) {

                    case Lang::PHQL_T_SELECT:
                        $irPhql = $this->_prepareSelect();
                        break;

                    case Lang::PHQL_T_INSERT:
                        $irPhql = $this->_prepareInsert();
                        break;

                    case Lang::PHQL_T_UPDATE:
                        $irPhql = $this->_prepareUpdate();
                        break;

                    case Lang::PHQL_T_DELETE:
                        $irPhql = $this->_prepareDelete();
                        break;

                    default:
                        throw new Exception("Unknown statement " . $type . ", when preparing: " . $phql);
                }
            }
        }

        if (!is_array($irPhql)) {
            throw new Exception("Corrupted AST");
        }

        /**
         * Store the prepared AST in the cache
         */
        if (is_int($uniqueId)) {
            self::$_irPhqlCache[$uniqueId] = $irPhql;
        }

        $this->_intermediate = $irPhql;
        return $irPhql;
    }

    /**
     * Executes the SELECT intermediate representation producing a \Phalcon\Mvc\Model\Resultset
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    protected function _executeSelect(array $intermediate, array $bindParams, array $bindTypes, $simulate = false)
    {
        $manager = $this->_manager;

        /**
         * Get a database connection
         */
        $connectionTypes = [];
        $models          = $intermediate["models"];

        foreach ($models as $modelName) {

            // Load model if it is not loaded
            if (!isset($this->_modelsInstances[$modelName])) {
                $model                              = $manager->load($modelName, true);
                $this->_modelsInstances[$modelName] = $model;
            } else {
                $model = $this->_modelsInstances[$modelName];
            }
            // Get database connection
            if (method_exists($model, "selectReadConnection")) {
                // use selectReadConnection() if implemented in extended Model class
                $connection = $model->selectReadConnection($intermediate, $bindParams, $bindTypes);
                if (!is_object($connection)) {
                    throw new Exception("'selectReadConnection' didn't return a valid connection");
                }
            } else {
                $connection = $model->getReadConnection();
            }

            // More than one type of connection is not allowed
            $connectionTypes[$connection->getType()] = true;
            if (count($connectionTypes) == 2) {
                throw new Exception("Cannot use models of different database systems in the same query");
            }
        }

        $columns = $intermediate["columns"];

        $haveObjects = false;
        $haveScalars = false;
        $isComplex   = false;

        // Check if the resultset have objects and how many of them have
        $numberObjects = 0;
        $columns1      = $columns;

        foreach ($columns as $column) {

            if (!is_array($column)) {
                throw new Exception("Invalid column definition");
            }

            if ($column["type"] == "scalar") {
                if (!isset($column["balias"])) {
                    $isComplex = true;
                }
                $haveScalars = true;
            } else {
                $haveObjects = true;
                $numberObjects++;
            }
        }

        // Check if the resultset to return is complex or simple
        if ($isComplex === false) {
            if ($haveObjects === true) {
                if ($haveScalars === true) {
                    $isComplex = true;
                } else {
                    if ($numberObjects == 1) {
                        $isSimpleStd = false;
                    } else {
                        $isComplex = true;
                    }
                }
            } else {
                $isSimpleStd = true;
            }
        }

        // Processing selected columns
        $instance        = null;
        $selectColumns   = [];
        $simpleColumnMap = [];
        $metaData        = $this->_metaData;

        foreach ($columns as $aliasCopy => $column) {

            $sqlColumn = $column["column"];

            // Complete objects are treated in a different way
            if ($column["type"] == "object") {

                $modelName = $column["model"];

                /**
                 * Base instance
                 */
                if (!isset($this->_modelsInstances[$modelName])) {
                    $instance                           = $manager->load($modelName);
                    $this->_modelsInstances[$modelName] = $instance;
                }
                $instance   = $this->_modelsInstances[$modelName];
                $attributes = $metaData->getAttributes($instance);
                if ($isComplex === true) {

                    // If the resultset is complex we open every model into their columns
                    if (Kernel::getGlobals("orm.column_renaming")) {
                        $columnMap = $metaData->getColumnMap($instance);
                    } else {
                        $columnMap = null;
                    }

                    // Add every attribute in the model to the generated select
                    foreach ($attributes as $attribute) {
                        $selectColumns[] = [$attribute, $sqlColumn, "_" . $sqlColumn . "_" . $attribute];
                    }

                    // We cache required meta-data to make its future access faster
                    $columns1[$aliasCopy]["instance"]   = $instance;
                    $columns1[$aliasCopy]["attributes"] = $attributes;
                    $columns1[$aliasCopy]["columnMap"]  = $columnMap;

                    // Check if the model keeps snapshots
                    $isKeepingSnapshots = (boolean) $manager->isKeepingSnapshots($instance);
                    if ($isKeepingSnapshots) {
                        $columns1[$aliasCopy]["keepSnapshots"] = $isKeepingSnapshots;
                    }
                } else {

                    /**
                     * Query only the columns that are registered as attributes in the metaData
                     */
                    foreach ($attributes as $attribute) {
                        $selectColumns[] = [$attribute, $sqlColumn];
                    }
                }
            } else {

                /**
                 * Create an alias if the column doesn't have one
                 */
                if (is_int($aliasCopy)) {
                    $columnAlias = [$sqlColumn, null];
                } else {
                    $columnAlias = [$sqlColumn, null, $aliasCopy];
                }
                $selectColumns[] = $columnAlias;
            }

            /**
             * Simulate a column map
             */
            if ($isComplex === false && $isSimpleStd === true) {
                if (isset($column["sqlAlias"])) {
                    $simpleColumnMap[$column["sqlAlias"]] = $aliasCopy;
                } else {
                    $simpleColumnMap[$aliasCopy] = $aliasCopy;
                }
            }
        }

        $bindCounts              = [];
        $intermediate["columns"] = $selectColumns;

        /**
         * Replace the placeholders
         */
        if (is_array($bindParams)) {
            $processed = [];
            foreach ($bindParams as $wildcard => $value) {
                if (is_int($wildcard)) {
                    $wildcardValue = ":" . $wildcard;
                } else {
                    $wildcardValue = $wildcard;
                }

                $processed[$wildcardValue] = $value;
                if (is_array($value)) {
                    $bindCounts[$wildcardValue] = count($value);
                }
            }
        } else {
            $processed = $bindParams;
        }

        /**
         * Replace the bind Types
         */
        if (is_array($bindTypes)) {
            $processedTypes = [];
            foreach ($bindTypes as $typeWildcard => $value) {
                if (is_int($typeWildcard)) {
                    $processedTypes[":" . $typeWildcard] = $value;
                } else {
                    $processedTypes[$typeWildcard] = $value;
                }
            }
        } else {
            $processedTypes = $bindTypes;
        }

        if (count($bindCounts)) {
            $intermediate["bindCounts"] = $bindCounts;
        }

        /**
         * The corresponding SQL dialect generates the SQL statement based accordingly with the database system
         */
        $dialect   = $connection->getDialect();
        $sqlSelect = $dialect->select($intermediate);
        if ($this->_sharedLock) {
            $sqlSelect = $dialect->sharedLock($sqlSelect);
        }

        /**
         * Return the SQL to be executed instead of execute it
         */
        if ($simulate) {
            return [
                "sql"       => $sqlSelect,
                "bind"      => $processed,
                "bindTypes" => $processedTypes
            ];
        }

        /**
         * Execute the query
         */
        $result = $connection->query($sqlSelect, $processed, $processedTypes);

        /**
         * Check if the query has data
         */
        if ($result instanceof ResultInterface && $result->numRows($result)) {
            $resultData = $result;
        } else {
            $resultData = false;
        }

        /**
         * Choose a resultset type
         */
        $cache = $this->_cache;
        if ($isComplex === false) {

            /**
             * Select the base object
             */
            if ($isSimpleStd === true) {

                /**
                 * If the result is a simple standard object use an Phalcon\Mvc\Model\Row as base
                 */
                $resultObject = new Row();

                /**
                 * Standard objects can't keep snapshots
                 */
                $isKeepingSnapshots = false;
            } else {

                if (is_object($instance)) {
                    $resultObject = $instance;
                } else {
                    $resultObject = $model;
                }

                /**
                 * Get the column map
                 */
                if (!Kernel::getGlobals("orm.cast_on_hydrate")) {
                    $simpleColumnMap = $metaData->getColumnMap($resultObject);
                } else {

                    $columnMap      = $metaData->getColumnMap($resultObject);
                    $typesColumnMap = $metaData->getDataTypes($resultObject);

                    if ($columnMap == null) {
                        $simpleColumnMap = [];
                        foreach ($metaData->getAttributes($resultObject) as $attribute) {
                            $simpleColumnMap[$attribute] = [$attribute, $typesColumnMap[$attribute]];
                        }
                    } else {
                        $simpleColumnMap = [];
                        foreach ($columnMap as $column => $attribute) {
                            $simpleColumnMap[$column] = [$attribute, $typesColumnMap[$column]];
                        }
                    }
                }

                /**
                 * Check if the model keeps snapshots
                 */
                $isKeepingSnapshots = (boolean) $manager->isKeepingSnapshots($resultObject);
            }

            if ($resultObject instanceof ModelInterface && method_exists($resultObject, "getResultsetClass")) {
                $resultsetClassName = $resultObject->getResultsetClass();

                if ($resultsetClassName) {
                    if (!class_exists($resultsetClassName)) {
                        throw new Exception("Resultset class \"" . $resultsetClassName . "\" not found");
                    }

                    if (!is_subclass_of($resultsetClassName, "Phalcon\\Mvc\\Model\\ResultsetInterface")) {
                        throw new Exception("Resultset class \"" . $resultsetClassName . "\" must be an implementation of Phalcon\\Mvc\\Model\\ResultsetInterface");
                    }

                    return new $resultsetClassName($simpleColumnMap, $resultObject, $resultData, $cache, $isKeepingSnapshots);
                }
            }

            /**
             * Simple resultsets contains only complete objects
             */
            return new Simple($simpleColumnMap, $resultObject, $resultData, $cache, $isKeepingSnapshots);
        }

        /**
         * Complex resultsets may contain complete objects and scalars
         */
        return new Complex($columns1, $resultData, $cache);
    }

    /**
     * Executes the INSERT intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query\StatusInterface
     * @throws Exception
     */
    protected function _executeInsert(array $intermediate, array $bindParams, array $bindTypes)
    {
        $modelName = $intermediate["model"];

        $manager = $this->_manager;
        /**
         * Load the model from the modelsManager or from the _modelsInstances property
         */
        if (!isset($this->_modelsInstances[$modelName])) {
            $model = $this->_manager->load($modelName, true);
        } else {
            $model = $this->_modelsInstances[$modelName];
        }

        /**
         * Get the model connection
         */
        if (method_exists($model, "selectWriteConnection")) {
            $connection = $model->selectWriteConnection($intermediate, $bindParams, $bindTypes);
            if (!is_object($connection)) {
                throw new Exception("'selectWriteConnection' didn't return a valid connection");
            }
        } else {
            $connection = $model->getWriteConnection();
        }

        $metaData   = $this->_metaData;
        $attributes = $metaData->getAttributes($model);

        $automaticFields = false;

        /**
         * The "fields" index may already have the fields to be used in the query
         */
        if (!isset($intermediate["fields"])) {
            $automaticFields = true;
            $fields          = $attributes;
            if (Kernel::getGlobals("orm.column_renaming")) {
                $columnMap = $metaData->getColumnMap($model);
            } else {
                $columnMap = null;
            }
        } else {
            $fields = $intermediate["fields"];
        }

        $values = $intermediate["values"];

        /**
         * The number of calculated values must be equal to the number of fields in the model
         */
        if (count($fields) != count($values)) {
            throw new Exception("The column count does not match the values count");
        }

        /**
         * Get the dialect to resolve the SQL expressions
         */
        $dialect = $connection->getDialect();

        $insertValues = [];
        foreach ($values as $number => $value) {
            $exprValue = $value["value"];
            switch ($value["type"]) {

                case Lang::PHQL_T_STRING:
                case Lang::PHQL_T_INTEGER:
                case Lang::PHQL_T_DOUBLE:
                    $insertValue = $dialect->getSqlExpression($exprValue);
                    break;

                case Lang::PHQL_T_NULL:
                    $insertValue = null;
                    break;

                case Lang::PHQL_T_NPLACEHOLDER:
                case Lang::PHQL_T_SPLACEHOLDER:
                case Lang::PHQL_T_BPLACEHOLDER:

                    if (!is_array($bindParams)) {
                        throw new Exception("Bound parameter cannot be replaced because placeholders is not an array");
                    }

                    $wildcard = str_replace(":", "", $dialect->getSqlExpression($exprValue));
                    if (!isset($bindParams[$wildcard])) {
                        throw new Exception(
                        "Bound parameter '" . $wildcard . "' cannot be replaced because it isn't in the placeholders list"
                        );
                    }
                    $insertValue = $bindParams[$wildcard];
                    break;

                default:
                    $insertValue = new RawValue($dialect->getSqlExpression($exprValue));
                    break;
            }

            $fieldName = $fields[$number];

            /**
             * If the user didn't define a column list we assume all the model's attributes as columns
             */
            if ($automaticFields === true) {
                if (is_array($columnMap)) {
                    if (!isset($columnMap[$fieldName])) {
                        throw new Exception("Column '" . $fieldName . "' isn't part of the column map");
                    } else {
                        $attributeName = $columnMap[$fieldName];
                    }
                } else {
                    $attributeName = $fieldName;
                }
            } else {
                $attributeName = $fieldName;
            }

            $insertValues[$attributeName] = $insertValue;
        }

        /**
         * Get a base model from the Models Manager
         * Clone the base model
         */
        $insertModel = clone $manager->load($modelName);

        /**
         * Call 'create' to ensure that an insert is performed
         * Return the insert status
         */
        return new Status($insertModel->create($insertValues), $insertModel);
    }

    /**
     * Executes the UPDATE intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query\StatusInterface
     * @throws Exception
     */
    protected function _executeUpdate(array $intermediate, array $bindParams, array $bindTypes)
    {
        $models = $intermediate["models"];

        if (isset($models[1])) {
            throw new Exception("Updating several models at the same time is still not supported");
        }

        $modelName = $models[0];

        /**
         * Load the model from the modelsManager or from the _modelsInstances property
         */
        if (!isset($this->_modelsInstances[$modelName])) {
            $model = $this->_manager->load($modelName);
        } else {
            $model = $this->_modelsInstances[$modelName];
        }

        if (method_exists($model, "selectWriteConnection")) {
            $connection = $model->selectWriteConnection($intermediate, $bindParams, $bindTypes);
            if (!is_object($connection)) {
                throw new Exception("'selectWriteConnection' didn't return a valid connection");
            }
        } else {
            $connection = $model->getWriteConnection();
        }

        $dialect = $connection->getDialect();

        $fields = $intermediate["fields"];
        $values = $intermediate["values"];

        /**
         * updateValues is applied to every record
         */
        $updateValues = [];

        /**
         * If a placeholder is unused in the update values, we assume that it's used in the SELECT
         */
        $selectBindParams = $bindParams;
        $selectBindTypes  = $bindTypes;

        foreach ($fields as $number => $field) {

            $value     = $values[$number];
            $exprValue = $value["value"];

            if (isset($field["balias"])) {
                $fieldName = $field["balias"];
            } else {
                $fieldName = $field["name"];
            }

            switch ($value["type"]) {

                case Lang::PHQL_T_STRING:
                case Lang::PHQL_T_INTEGER:
                case Lang::PHQL_T_DOUBLE:
                    $updateValue = $dialect->getSqlExpression($exprValue);
                    break;

                case Lang::PHQL_T_NULL:
                    $updateValue = null;
                    break;

                case Lang::PHQL_T_NPLACEHOLDER:
                case Lang::PHQL_T_SPLACEHOLDER:
                case Lang::PHQL_T_BPLACEHOLDER:

                    if (!is_array($bindParams)) {
                        throw new Exception("Bound parameter cannot be replaced because placeholders is not an array");
                    }

                    $wildcard = str_replace(":", "", $dialect->getSqlExpression(exprValue));
                    if (isset($bindParams[$wildcard])) {
                        $updateValue = $bindParams[$wildcard];
                        unset($selectBindParams[$wildcard]);
                        unset($selectBindTypes[$wildcard]);
                    } else {
                        throw new Exception(
                        "Bound parameter '" . $wildcard . "' cannot be replaced because it's not in the placeholders list"
                        );
                    }
                    break;

                case Lang::PHQL_T_BPLACEHOLDER:
                    throw new Exception("Not supported");

                default:
                    $updateValue = new RawValue($dialect->getSqlExpression($exprValue));
                    break;
            }

            $updateValues[$fieldName] = $updateValue;
        }

        /**
         * We need to query the records related to the update
         */
        $records = $this->_getRelatedRecords($model, $intermediate, $selectBindParams, $selectBindTypes);

        /**
         * If there are no records to apply the update we return success
         */
        if (!count($records)) {
            return new Status(true);
        }

        if (method_exists($model, "selectWriteConnection")) {
            $connection = $model->selectWriteConnection($intermediate, $bindParams, $bindTypes);
            if (!is_object($connection)) {
                throw new Exception("'selectWriteConnection' didn't return a valid connection");
            }
        } else {
            $connection = $model->getWriteConnection();
        }

        /**
         * Create a transaction in the write connection
         */
        $connection->begin();

        $records->rewind();

        //for record in iterator(records) {
        while ($records->valid()) {

            $record = $records->current();

            /**
             * We apply the executed values to every record found
             */
            if (!$record->update($updateValues)) {

                /**
                 * Rollback the transaction on failure
                 */
                $connection->rollback();

                return new Status(false, $record);
            }

            $records->next();
        }

        /**
         * Commit transaction on success
         */
        $connection->commit();

        return new Status(true);
    }

    /**
     * Executes the DELETE intermediate representation producing a \Phalcon\Mvc\Model\Query\Status
     *
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query\StatusInterface
     * @throws Exception
     */
    protected function _executeDelete(array $intermediate, array $bindParams, array $bindTypes)
    {
        $models = $intermediate['models'];
        if (isset($models[1]) === true) {
            throw new Exception('Delete from several models at the same time is still not supported');
        }

        $modelName = $models[0];

        //Load the model from the modelsManager or from the _modelsInstances property
        if (isset($this->_modelsInstances[$modelName]) === true) {
            $model = $this->_modelsInstances[$modelName];
        } else {
            $model = $this->_manager->load($modelName);
        }

        //Get the records to be deleted
        $records = $this->_getRelatedRecords($model, $intermediate, $bindParams, $bindTypes);

        //If there are no records to delete we return success
        if (count($records) == 0) {
            return new Status(true, null);
        }

        //Create a transaction in the write connection
        if (method_exists($model, "selectWriteConnection")) {
            $connection = $model->selectWriteConnection(intermediate, bindParams, bindTypes);
            if (!is_object($connection)) {
                throw new Exception("'selectWriteConnection' didn't return a valid connection");
            }
        } else {
            $connection = $model->getWriteConnection();
        }
        $connection->begin();
        $records->rewind();

        while ($records->valid() !== false) {
            $record = $records->current();

            //We delete every record found
            if ($record->delete() !== true) {
                //Rollback the transaction
                $connection->rollback();
                return new Status(false, $record);
            }

            //Move the cursor to the next record
            $records->next();
        }

        //Commit the transaction
        $connection->commit();

        //Create a status to report the deletion status
        return new Status(true, null);
    }

    /**
     * Query the records on which the UPDATE/DELETE operation well be done
     *
     * @param \Phalcon\Mvc\Model $model
     * @param array $intermediate
     * @param array $bindParams
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    protected function _getRelatedRecords(Model $model, array $intermediate, array $bindParams, array $bindTypes)
    {/**
     * Instead of create a PHQL string statement we manually create the IR representation
     */
        $selectIr = [
            "columns" => [[
                "type"   => "object",
                "model"  => get_class($model),
                "column" => $model->getSource()
                ]],
            "models"  => $intermediate["models"],
            "tables"  => $intermediate["tables"]
        ];

        /**
         * Check if a WHERE clause was specified
         */
        if (isset($intermediate["where"])) {
            $selectIr["where"] = $intermediate["where"];
        }

        /**
         * Check if a LIMIT clause was specified
         */
        if (isset($intermediate["limit"])) {
            $selectIr["limit"] = $intermediate["limit"];
        }

        /**
         * We create another Phalcon\Mvc\Model\Query to get the related records
         */
        $query = new self();
        $query->setDI($this->_dependencyInjector);
        $query->setType(Lang::PHQL_T_SELECT);
        $query->setIntermediate($selectIr);

        return $query->execute($bindParams, $bindTypes);
    }

    /**
     * Executes a parsed PHQL statement
     *
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return mixed
     * @throws Exception
     */
    public function execute($bindParams = null, $bindTypes = null)
    {
        if ((is_array($bindParams) === false &&
            is_null($bindParams) === false) ||
            (is_array($bindTypes) === false &&
            is_null($bindTypes) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        /* GET THE CACHE */
        $cacheOptions = $this->_cacheOptions;
        if (is_null($cacheOptions) === false) {
            if (is_array($cacheOptions) === false) {
                throw new Exception('Invalid caching options');
            }

            //The user must set a cache key
            if (isset($cacheOptions['key']) === true) {
                $key = $cacheOptions['key'];
            } else {
                throw new Exception('A cache key must be provided to identify the cached resultset in the cache backend');
            }

            //By default use 3600 seconds (1 hour) as cache lifetime
            if (isset($cacheOptions['lifetime']) === true) {
                $lifetime = $cacheOptions['lifetime'];
            } else {
                $lifetime = 3600;
            }

            //'modelsCache' is the default name for the models cache service
            if (isset($cacheOptions['service']) === true) {
                $cacheService = $cacheOptions['service'];
            } else {
                $cacheService = 'modelsCache';
            }

            $cache = $this->_dependencyInjector->getShared($cacheService);
            if (is_object($cache) === false) {
                //@note no interface validation
                throw new Exception('The cache service must be an object');
            }

            $result = $cache->get($key, $lifetime);
            if (is_null($result) === false) {
                if (is_object($result) === false) {
                    throw new Exception("The cache didn't return a valid resultset"); //@note (sic!)
                }

                $result->setIsFresh(false);

                //Check if only the first two rows must be returned
                if ($this->_uniqueRow == true) {
                    $preparedResult = $result->getFirst();
                } else {
                    $preparedResult = $result;
                }

                return $preparedResult;
            }

            $this->_cache = $cache;
        }

        //The statement is parsed from its PHQL string or a previously processed IR
        $intermediate = $this->parse();

        //Check for default bind parameters and merge them with the passed ones
        $defaultBindParams = $this->_bindParams;
        if (is_array($defaultBindParams) === true) {
            if (is_array($bindParams) === true) {
                $mergedParams = array_merge($defaultBindParams, $bindParams);
            } else {
                $mergedParams = $defaultBindParams;
            }
        } else {
            $mergedParams = $bindParams;
        }

        //Check for default bind types and merge them with the passed onees
        $defaultBindTypes = $this->_bindTypes;
        if (is_array($defaultBindTypes) === true) {
            if (is_array($bindTypes) === true) {
                $mergedTypes = array_merge($defaultBindTypes, $bindTypes);
            } else {
                $mergedTypes = $defaultBindTypes;
            }
        } else {
            $mergedTypes = $bindTypes;
        }

        switch ((int) $this->_type) {
            case Lang::PHQL_T_SELECT:
                $result = $this->_executeSelect($intermediate, $mergedParams, $mergedTypes);
                break;
            case Lang::PHQL_T_INSERT:
                $result = $this->_executeInsert($intermediate, $mergedParams, $mergedTypes);
                break;
            case Lang::PHQL_T_UPDATE:
                $result = $this->_executeUpdate($intermediate, $mergedParams, $mergedTypes);
                break;
            case Lang::PHQL_T_DELETE:
                $result = $this->_executeDelete($intermediate, $mergedParams, $mergedTypes);
                break;
            default:
                throw new Exception('Unknown statement ' . $this->_type);
                break;
        }

        //We store the resultset in the cache if any
        if (is_null($cacheOptions) === false) {
            //Only PHQL SELECTs can be cached
            if ($this->_type !== Lang::PHQL_T_SELECT) {
                throw new Exception('Only PHQL statements return resultsets can be cached');
            }

            $cache->save($key, $result, $lifetime);
        }

        //Check if only the first row must be returned
        if ($this->_uniqueRow == true) {
            return $result->getFirst();
        } else {
            return $result;
        }
    }

    /**
     * Executes the query returning the first result
     *
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return \Phalcon\Mvc\ModelInterface
     */
    public function getSingleResult($bindParams = null, $bindTypes = null)
    {
        //The query is already programmed to return just one row
        if ($this->_uniqueRow == true) {
            return $this->execute($bindParams, $bindTypes);
        }

        return $this->execute($bindParams, $bindTypes)->getFirst();
    }

    /**
     * Sets the type of PHQL statement to be executed
     *
     * @param int $type
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setType($type)
    {
        $this->_type = (int) $type;
        return $this;
    }

    /**
     * Gets the type of PHQL statement executed
     *
     * @return int|null
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Set default bind parameters
     *
     * @param array $bindParams
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setBindParams(array $bindParams, $merge = false)
    {
        if ($merge) {
            $currentBindParams = $this->_bindParams;
            if (is_array($currentBindParams)) {
                $this->_bindParams = $currentBindParams + $bindParams;
            } else {
                $this->_bindParams = $bindParams;
            }
        } else {
            $this->_bindParams = $bindParams;
        }

        return $this;
    }

    /**
     * Returns default bind params
     *
     * @return array|null
     */
    public function getBindParams()
    {
        return $this->bindParams;
    }

    /**
     * Set default bind parameters
     *
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setBindTypes(array $bindTypes, $merge = false)
    {
        if ($merge) {
            $currentBindTypes = $this->_bindTypes;
            if (is_array($currentBindTypes)) {
                $this->_bindTypes = $currentBindTypes + $bindTypes;
            } else {
                $this->_bindTypes = $bindTypes;
            }
        } else {
            $this->_bindTypes = $bindTypes;
        }

        return $this;
    }

    /**
     * Returns default bind types
     *
     * @return array|null
     */
    public function getBindTypes()
    {
        return $this->_bindTypes;
    }

    /**
     * Allows to set the IR to be executed
     *
     * @param array $intermediate
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function setIntermediate(array $intermediate)
    {
        $this->_intermediate = $intermediate;
        return $this;
    }

    /**
     * Returns the intermediate representation of the PHQL statement
     *
     * @return array|null
     */
    public function getIntermediate()
    {
        return $this->_intermediate;
    }

    /**
     * Sets the cache parameters of the query
     *
     * @param array $cacheOptions
     * @return \Phalcon\Mvc\Model\Query
     * @throws Exception
     */
    public function cache(array $cacheOptions)
    {
        $this->_cacheOptions = $cacheOptions;
        return $this;
    }

    /**
     * Returns the current cache options
     *
     * @param array|null
     */
    public function getCacheOptions()
    {
        return $this->_cacheOptions;
    }

    /**
     * Returns the current cache backend instance
     *
     * @return \Phalcon\Cache\BackendInterface|null
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Returns the SQL to be generated by the internal PHQL (only works in SELECT statements)
     * 
     * @return array
     */
    public function getSql()
    {
        /**
         * The statement is parsed from its PHQL string or a previously processed IR
         */
        $intermediate = $this->parse();

        if ($this->_type == Lang::PHQL_T_SELECT) {
            return $this->_executeSelect($intermediate, $this->_bindParams, $this->_bindTypes, true);
        }

        throw new Exception("This type of statement generates multiple SQL statements");
    }

    /**
     * Destroys the internal PHQL cache
     */
    public static function clean()
    {
        self::$_irPhqlCache = [];
    }

}
