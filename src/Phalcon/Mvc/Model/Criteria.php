<?php

namespace Phalcon\Mvc\Model;

use Phalcon\Di;
use Phalcon\Db\Column;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\CriteriaInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Query\BuilderInterface;

/**
 * Phalcon\Mvc\Model\Criteria
 *
 * This class is used to build the array parameter required by
 * Phalcon\Mvc\Model::find() and Phalcon\Mvc\Model::findFirst()
 * using an object-oriented interface.
 *
 * <code>
 * $robots = Robots::query()
 *     ->where("type = :type:")
 *     ->andWhere("year < 2000")
 *     ->bind(["type" => "mechanical"])
 *     ->limit(5, 10)
 *     ->orderBy("name")
 *     ->execute();
 * </code>
 */
class Criteria implements CriteriaInterface, InjectionAwareInterface
{

    protected $_model;
    protected $_params;
    protected $_bindParams;
    protected $_bindTypes;
    protected $_hiddenParamNumber = 0;

    /**
     * Sets the DependencyInjector container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Dependency Injector is invalid');
        }

        $this->_params['di'] = $dependencyInjector;
    }

    /**
     * Returns the DependencyInjector container
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        if (isset($this->_params['di']) === true) {
            return $this->_params['di'];
        }
    }

    /**
     * Set a model on which the query will be executed
     *
     * @param string $modelName
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function setModelName($modelName)
    {
        if (is_string($modelName) === false) {
            throw new Exception('Model name must be string');
        }

        $this->_model = $modelName;

        return $this;
    }

    /**
     * Returns an internal model name on which the criteria will be applied
     *
     * @return string|null
     */
    public function getModelName()
    {
        return $this->_model;
    }

    /**
     * Sets the bound parameters in the criteria
     * This method replaces all previously set bound parameters
     * 
     * @param array $bindParams
     * @param boolean $merge
     * @return \Phalcon\Mvc\Model\Criteria
     */
    public function bind(array $bindParams, $merge = false)
    {
        if ($merge) {
            if (isset($this->_params["bind"])) {
                $bind = $this->_params["bind"];
            } else {
                $bind = null;
            }
            if (is_array($bind)) {
                $this->_params["bind"] = $bind + $bindParams;
            } else {
                $this->_params["bind"] = $bindParams;
            }
        } else {
            $this->_params["bind"] = $bindParams;
        }

        return $this;
    }

    /**
     * Sets the bind types in the criteria
     * This method replaces all previously set bound parameters
     *
     * @param array $bindTypes
     * @return \Phalcon\Mvc\Model\Criteria
     */
    public function bindTypes($bindTypes)
    {
        if (is_array($bindTypes) === false) {
            throw new Exception('Bind types must be an Array');
        }

        $this->_params = $bindTypes;

        return $this;
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     * 
     * @param mixed $distinct
     * @return \Phalcon\Mvc\Model\Criteria
     */
    public function distinct($distinct)
    {
        $this->_params["distinct"] = $distinct;
        return $this;
    }

    /**
     * Sets the columns to be queried
     *
     * <code>
     * $criteria->columns(
     *     [
     *         "id",
     *         "name",
     *     ]
     * );
     * </code>
     *
     * @param string|array columns
     * @return \Phalcon\Mvc\Model\Criteria
     */
    public function columns($columns)
    {
        if (is_array($columns) === false &&
            is_string($columns) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_params['columns'] = $columns;

        return $this;
    }

    /**
     * Adds a INNER join to the query
     *
     * <code>
     *  $criteria->join('Robots');
     *  $criteria->join('Robots', 'r.id = RobotsParts.robots_id');
     *  $criteria->join('Robots', 'r.id = RobotsParts.robots_id', 'r');
     *  $criteria->join('Robots', 'r.id = RobotsParts.robots_id', 'r', 'LEFT');
     * </code>
     *
     * @param string $model
     * @param string|null $conditions
     * @param string|null $alias
     * @param string|null $type
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function join($model, $conditions = null, $alias = null, $type = null)
    {
        if (is_string($model) === false ||
            (is_string($conditions) === false &&
            is_null($conditions) === false) ||
            (is_string($alias) === false &&
            is_null($alias) === false) ||
            (is_string($type) === false &&
            is_null($type) === false)) {
            throw new Exception('Invalid parameter type.');
        }

        $join = array($model, $conditions, $alias, $type);
        if (isset($this->_params['joins']) === true) {
            $joins = $this->_params['joins'];
            if (is_array($joins) === true) {
                $joins = array_merge($this->_params['joins'], $join);
            } else {
                $joins = $join;
            }
        } else {
            $joins = array($join);
        }

        $this->_params['joins'] = $joins;

        return $this;
    }

    /**
     * Adds a INNER join to the query
     *
     * <code>
     *  $criteria->innerJoin('Robots');
     *  $criteria->innerJoin('Robots', 'r.id = RobotsParts.robots_id');
     *  $criteria->innerJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     * </code>
     *
     * @param string $model
     * @param string|null $conditions
     * @param string|null $alias
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     */
    public function innerJoin($model, $conditions = null, $alias = null)
    {
        return $this->join($model, $conditions, $alias, 'INNER');
    }

    /**
     * Adds a LEFT join to the query
     *
     * <code>
     *  $criteria->leftJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     * </code>
     *
     * @param string $model
     * @param string|null $conditions
     * @param string|null $alias
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     */
    public function leftJoin($model, $conditions = null, $alias = null)
    {
        return $this->join($model, $conditions, $alias, 'LEFT');
    }

    /**
     * Adds a RIGHT join to the query
     *
     * <code>
     *  $criteria->rightJoin('Robots', 'r.id = RobotsParts.robots_id', 'r');
     * </code>
     *
     * @param string $model
     * @param string|null $conditions
     * @param string|null $alias
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     */
    public function rightJoin($model, $conditions = null, $alias = null)
    {
        return $this->join($model, $conditions, $alias, 'RIGHT');
    }

    /**
     * Sets the conditions parameter in the criteria
     *
     * @param string $conditions
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function where($conditions, $bindParams = null, $bindTypes = null)
    {
        if (is_string($conditions) === false) {
            throw new Exception('Conditions must be string');
        }

        $this->_params['conditions'] = $conditions;

        //Update or merge existing bound parameters
        if (is_array($bindParams) === true) {
            if (isset($this->_params['bind']) === true) {
                $bindParams = array_merge($this->_params['bind'], $bindParams);
            }

            $this->_params['bind'] = $bindParams;
        } elseif (is_null($bindParams) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Update or merge existing bind types
        if (is_array($bindTypes) === true) {
            if (isset($this->_params['bindTypes']) === true) {
                $bindTypes = array_merge($this->_params['bindTypes'], $bindTypes);
            }

            $this->_params['bindTypes'] = $bindTypes;
        } elseif (is_null($bindTypes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return $this;
    }

    /**
     * Appends a condition to the current conditions using an AND operator (deprecated)
     *
     * @deprecated
     * @param string $conditions
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     */
    public function addWhere($conditions, $bindParams = null, $bindTypes = null)
    {
        return $this->andWhere($conditions, $bindParams, $bindTypes);
    }

    /**
     * Appends a condition to the current conditions using an AND operator
     *
     * @param string $conditions
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function andWhere($conditions, $bindParams = null, $bindTypes = null)
    {
        if (isset($this->_params["conditions"])) {
            $conditions = "(" . $this->_params["conditions"] . ") AND (" . $conditions . ")";
        }

        return $this->where($conditions, $bindParams, $bindTypes);
    }

    /**
     * Appends a condition to the current conditions using an OR operator
     *
     * @param string $conditions
     * @param array|null $bindParams
     * @param array|null $bindTypes
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function orWhere($conditions, $bindParams = null, $bindTypes = null)
    {
        if (isset($this->_params["conditions"])) {
            $conditions = "(" . $this->_params["conditions"] . ") OR (" . $conditions . ")";
        }

        return $this->where($conditions, $bindParams, $bindTypes);
    }

    /**
     * Appends a BETWEEN condition to the current conditions
     *
     * <code>
     *  $criteria->betweenWhere('price', 100.25, 200.50);
     * </code>
     *
     * @param string $expr
     * @param mixed $minimum
     * @param mixed $maximum
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function betweenWhere($expr, $minimum, $maximum)
    {
        if (is_string($expr) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $hiddenParam     = $this->_hiddenParamNumber;
        $nextHiddenParam = $hiddenParam++;

        //Minimum key with auto bind-params
        $minimumKey = 'ACP' . $hiddenParam;

        //Maximum key with auto bind-params
        $maximumKey = 'ACP' . $nextHiddenParam;

        /**
         * Create a standard BETWEEN condition with bind params
         * Append the BETWEEN to the current conditions using and "and"
         */
        $this->andWhere(
            $expr . " BETWEEN :" . $minimumKey . ": AND :" . $maximumKey . ":", [$minimumKey => $minimum, $maximumKey => $maximum]
        );

        $nextHiddenParam++;
        $this->_hiddenParamNumber = $nextHiddenParam;

        return $this;
    }

    /**
     * Appends a NOT BETWEEN condition to the current conditions
     *
     * <code>
     *  $criteria->notBetweenWhere('price', 100.25, 200.50);
     * </code>
     *
     * @param string $expr
     * @param mixed $minimum
     * @param mixed $maximum
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function notBetweenWhere($expr, $minimum, $maximum)
    {
        if (is_string($expr) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $hiddenParam     = $this->_hiddenParamNumber;
        $nextHiddenParam = $hiddenParam++;

        //Minimum key with auto bind-params
        $minimumKey = 'ACP' . $hiddenParam;

        //Maximum key with auto bind-params
        $maximumKey = 'ACP' . $nextHiddenParam;
        /**
         * Create a standard BETWEEN condition with bind params
         * Append the BETWEEN to the current conditions using and "and"
         */
        $this->andWhere(
            $expr . " NOT BETWEEN :" . $minimumKey . ": AND :" . $maximumKey . ":", [$minimumKey => $minimum, $maximumKey => $maximum]
        );

        $nextHiddenParam++;

        $this->_hiddenParamNumber = $nextHiddenParam;

        return $this;
    }

    /**
     * Appends an IN condition to the current conditions
     *
     * <code>
     *  $criteria->inWhere('id', [1, 2, 3]);
     * </code>
     *
     * @param string $expr
     * @param array $values
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function inWhere($expr, array $values)
    {
        if (is_string($expr) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!count($values)) {
            $this->andWhere($expr . " != " . $expr);
            return $this;
        }

        $hiddenParam = $this->_hiddenParamNumber;
        $bindParams  = array();
        $bindKeys    = array();


        $bindParams = [];
        $bindKeys   = [];
        foreach ($values as $value) {

            /**
             * Key with auto bind-params
             */
            $key = "ACP" . $hiddenParam;

            $queryKey = ":" . $key . ":";

            $bindKeys[]       = $queryKey;
            $bindParams[$key] = $value;

            $hiddenParam++;
        }

        /**
         * Create a standard IN condition with bind params
         * Append the IN to the current conditions using and "and"
         */
        $this->andWhere($expr . " IN (" . join(", ", $bindKeys) . ")", $bindParams);

        $this->_hiddenParamNumber = $hiddenParam;

        return $this;
    }

    /**
     * Appends a NOT IN condition to the current conditions
     *
     * <code>
     *  $criteria->notInWhere('id', [1, 2, 3]);
     * </code>
     *
     * @param string $expr
     * @param array $values
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function notInWhere($expr, $values)
    {
        if (is_array($values) === false) {
            throw new Exception('Values must be an array');
        }

        $hiddenParam = $this->_hiddenParamNumber;

        $bindParams = [];
        $bindKeys   = [];
        foreach ($values as $value) {

            /**
             * Key with auto bind-params
             */
            $key              = "ACP" . $hiddenParam;
            $bindKeys[]       = ":" . $key . ":";
            $bindParams[$key] = $value;

            $hiddenParam++;
        }
    }

    /**
     * Adds the conditions parameter to the criteria
     *
     * @param string $conditions
     * @return \Phalcon\Mvc\Model\CriteriaIntreface
     * @throws Exception
     */
    public function conditions($conditions)
    {
        if (is_string($conditions) === false) {
            throw new Exception('Conditions must be string');
        }

        $this->_params['conditions'] = $conditions;

        return $this;
    }

    /**
     * Adds the order-by parameter to the criteria (deprecated)
     *
     * @deprecated
     * @param string $orderColumns
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function order($orderColumns)
    {
        if (is_string($orderColumns) === false) {
            throw new Exception('Order columns must be string');
        }

        $this->_params['order'] = $orderColumns;

        return $this;
    }

    /**
     * Adds the order-by parameter to the criteria
     *
     * @param string $orderColumns
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function orderBy($orderColumns)
    {
        if (is_string($orderColumns) === false) {
            throw new Exception('Order columns must be string');
        }

        $this->_params['order'] = $orderColumns;

        return $this;
    }

    /**
     * Adds the group-by clause to the criteria
     * 
     * @param mixed $group
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     */
    public function groupBy($group)
    {
        $this->_params["group"] = $group;
        return $this;
    }

    /**
     * Adds the having clause to the criteria
     * 
     * @param mixed $having
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     */
    public function having($having)
    {
        $this->_params["having"] = $having;
        return $this;
    }

    /**
     * Adds the limit parameter to the criteria.
     *
     * <code>
     * $criteria->limit(100);
     * $criteria->limit(100, 200);
     * $criteria->limit("100", "200");
     * </code>
     * 
     * @param int $limit
     * @param int|null $offset
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     */
    public function limit($limit, $offset = null)
    {
        $limit = abs((int) $limit);
        if ($limit == 0) {
            return $this;
        }
        if (is_numeric($offset)) {
            $offset                 = abs((int) $offset);
            $this->_params['limit'] = array('number' => $limit, 'offset' => $offset);
        } else {
            $this->_params['limit'] = $limit;
        }

        return $this;
    }

    /**
     * Adds the "for_update" parameter to the criteria
     *
     * @param boolean|null $forUpdate
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function forUpdate($forUpdate = null)
    {
        $this->_params['for_update'] = (boolean) $forUpdate;

        return $this;
    }

    /**
     * Adds the "shared_lock" parameter to the criteria
     *
     * @param boolean|null $sharedLock
     * @return \Phalcon\Mvc\Model\CriteriaInterface
     * @throws Exception
     */
    public function sharedLock($sharedLock = null)
    {
        $this->_params['shared_lock'] = (boolean) $sharedLock;

        return $this;
    }

    /**
     * Sets the cache options in the criteria
     * This method replaces all previously set cache options
     */
    public function cache(array $cache)
    {
        $this->_params["cache"] = $cache;
        return $this;
    }

    /**
     * Returns the conditions parameter in the criteria
     *
     * @return string|null
     */
    public function getWhere()
    {
        if (isset($this->_params['conditions']) === true) {
            return $this->_params['conditions'];
        }

        return null;
    }

    /**
     * Return the columns to be queried
     *
     * @return string|array|null
     */
    public function getColumns()
    {
        if (isset($this->_params['columns']) === true) {
            return $this->_params['columns'];
        }
        return null;
    }

    /**
     * Returns the conditions parameter in the criteria
     *
     * @return string|null
     */
    public function getConditions()
    {
        if (isset($this->_params['conditions']) === true) {
            return $this->_params['conditions'];
        }
        return null;
    }

    /**
     * Returns the limit parameter in the criteria
     *
     * @return string|array|null
     */
    public function getLimit()
    {
        if (isset($this->_params['limit']) === true) {
            return $this->_params['limit'];
        }
        return null;
    }

    /**
     * Returns the order parameter in the criteria
     *
     * @return string|null
     */
    public function getOrderBy()
    {
        if (isset($this->_params['order']) === true) {
            return $this->_params['order'];
        }
        return null;
    }

    /**
     * Returns the group clause in the criteria
     *
     * @return string|null
     */
    public function getGroupBy()
    {
        if (isset($this->_params['group']) === true) {
            return $this->_params['group'];
        }
        return null;
    }

    /**
     * Returns the having clause in the criteria
     *
     * @return string|null
     */
    public function getHaving()
    {
        if (isset($this->_params['having']) === true) {
            return $this->_params['having'];
        }
        return null;
    }

    /**
     * Returns all the parameters defined in the criteria
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Builds a \Phalcon\Mvc\Model\Criteria based on an input array like $_POST
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @param string $modelName
     * @param array $data
     * @return \Phalcon\Mvc\Model\Criteria
     * @throws Exception
     */
    public static function fromInput(DiInterface $dependencyInjector, $modelName, array $data, $operator = "AND")
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('A dependency injector container is required to obtain the ORM services');
        }

        if (is_string($modelName) === false || is_string($operator) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (count($data)) {
            $conditions = array();
            $metaData   = $dependencyInjector->getShared('modelsMetadata');
            $model      = new $modelName();
            $dataTypes  = $metaData->getDataTypes($model);
            $columnMap  = $metaData->getReverseColumnMap($model);
            $bind       = array();

            //We look for attributes in the array passed as data
            foreach ($data as $field => $value) {
                if (is_array($columnMap) && count($columnMap)) {
                    $attribute = $columnMap[$field];
                } else {
                    $attribute = $field;
                }
                if (isset($dataTypes[$field]) === true &&
                    $value !== null && $value !== '') {
                    if ($dataTypes[$field] === Column::TYPE_VARCHAR) {
                        //For varchar types we use LIKE operator
                        $condition    = $field . ' LIKE :' . $field . ':';
                        $bind[$field] = '%' . $value . '%';
                    } else {
                        //For the rest of data types we use a plain = operator
                        $condition    = $field . '=:' . $field . ':';
                        $bind[$field] = $value;
                    }

                    $conditions[] = $condition;
                }
            }
        }

        //Create an object instance and pass the parameters to it
        $criteria = new self();
        if (count($conditions)) {
            $criteria->where(join(" " . $operator . " ", $conditions));
            $criteria->bind($bind);
        }

        $criteria->setModelName($modelName);
        return $criteria;
    }

    /**
     * Creates a query builder from criteria.
     *
     * <code>
     * $builder = Robots::query()
     *     ->where("type = :type:")
     *     ->bind(["type" => "mechanical"])
     *     ->createBuilder();
     * </code>
     * 
     * @return BuilderInterface
     */
    public function createBuilder()
    {
        $dependencyInjector = $this->getDI();
        if (is_object($dependencyInjector)) {
            $dependencyInjector = Di::getDefault();
            $this->setDI($dependencyInjector);
        }

        $manager = $dependencyInjector->getShared("modelsManager");
        if (!$manager instanceof ManagerInterface) {
            throw new Exception("Service modelsManager invalid");
        }

        /**
         * Builds a query with the passed parameters
         */
        $builder = $manager->createBuilder($this->_params);
        $builder->from($this->_model);

        return $builder;
    }

    /**
     * Executes a find using the parameters built with the criteria
     *
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public function execute()
    {
        $model = $this->getModelName();
        if (!is_string($model)) {
            throw new Exception("Model name must be string");
        }

        return $model::find($this->getParams());
    }

}
