<?php

/**
 * Paginator Query Builder Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Paginator\Adapter;

use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Paginator\Adapter;
use Phalcon\Paginator\Exception;
use Phalcon\Db;
use \stdClass;

/**
 * Phalcon\Paginator\Adapter\QueryBuilder
 *
 * Pagination using a PHQL query builder as source of data
 *
 * <code>
 * use Phalcon\Paginator\Adapter\QueryBuilder;
 *
 * $builder = $this->modelsManager->createBuilder()
 *                 ->columns("id, name")
 *                 ->from("Robots")
 *                 ->orderBy("name");
 *
 * $paginator = new QueryBuilder(
 *     [
 *         "builder" => $builder,
 *         "limit"   => 20,
 *         "page"    => 1,
 *     ]
 * );
 *</code>
 */
class QueryBuilder extends Adapter
{

    /**
     * Configuration
     *
     * @var null|array
     * @access protected
     */
    protected $_config;

    /**
     * Builder
     *
     * @var null|object
     * @access protected
     */
    protected $_builder;

    /**
     * Columns for count query if builder has having
     * @var null|int
     * @access protected
     */
    protected $_columns;

    /**
     * Limit Rows
     *
     * @var null|int
     * @access protected
     */
    protected $_limitRows;

    /**
     * Page
     *
     * @var int
     * @access protected
     */
    protected $_page;

    /**
     * \Phalcon\Paginator\Adapter\QueryBuilder
     *
     * @param $config array
     * @throws Exception
     */
    public function __construct(array $config)
    {
        if (is_array($config) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_config = $config;
        if (isset($config['builder']) === false) {
            throw new Exception("Parameter 'builder' is required");
        } else {
            //@note no further builder validation
            $this->_builder = $config['builder'];
        }

        if (isset($config['limit']) === false) {
            throw new Exception("Parameter 'limit' is required");
        } else {
            $this->_limitRows = $config['limit'];
        }

        if (isset($config['page']) === true) {
            $this->_page = $config['page'];
        }
        if (isset($config['columns']) === true) {
            $this->_columns = $config['columns'];
        }
    }

    /**
     * Get the current page number
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->_page;
    }

    /**
     * Set query builder object
     * @param $builder \Phalcon\Mvc\Model\Query\Builder
     * @return QueryBuilder
     */
    public function setQueryBuilder($builder)
    {
        $this->_builder = $builder;

        return $this;
    }

    /**
     * Get query builder object
     * @return null|object
     */
    public function getQueryBuilder()
    {
        return $this->_builder;
    }

    /**
     * Returns a slice of the resultset to show in the pagination
     *
     * @return stdClass
     */
    public function getPaginate()
    {
        $columns = $this->_columns;
        /* Clone the original builder */
        $builder = clone $this->_builder;
        $totalBuilder = clone $builder;

        $limit = $this->_limitRows;
        $numberPage = ( int )$this->_page;

        if (is_null($numberPage) === true || $numberPage <= 0) {
            $numberPage = 1;
        }

        $prevNumberPage = $numberPage - 1;
        $number = $limit * $prevNumberPage;

        //Set the limit clause avoiding negative offsets
        if ($number < $limit) {
            $builder->limit($limit);
        } else {
            $builder->limit($limit, $number);
        }

        $query = $builder->getQuery();

        if ($numberPage == 1) {
            $before = 1;
        } else {
            $before = $numberPage - 1;
        }

        /**
         * Execute the query an return the requested slice of data
         */
        $items = $query->execute();

        $hasHaving = !isEmpty($totalBuilder->getHaving());

        $groups = $totalBuilder->getGroupBy();

        $hasGroup = !isEmpty($groups);

        /**
         * Change the queried columns by a COUNT(*)
         */

        if ($hasHaving && !$hasGroup) {
            if (isEmpty($columns)) {
                throw new Exception("When having is set there should be columns option provided for which calculate row count");
            }
            $totalBuilder->columns($columns);
        } else {
            $totalBuilder->columns("COUNT(*) [rowcount]");
        }

        /**
         * Change 'COUNT()' parameters, when the query contains 'GROUP BY'
         */
        if ($hasGroup) {
            if (is_array($groups)) {
                $groupColumn = implode(",", $groups);
            } else {
                $groupColumn = $groups;
            }

            if (!$hasHaving) {
                $totalBuilder->groupBy(null)->columns[] = "COUNT(DISTINCT " . groupColumn . ") AS [rowcount]";
            } else {
                $totalBuilder->columns[] = "DISTINCT " . groupColumn;
            }
        }

        /**
         * Remove the 'ORDER BY' clause, PostgreSQL requires this
         */
        $totalBuilder->orderBy(null);

        /**
         * Obtain the PHQL for the total query
         */
        $totalQuery = $totalBuilder->getQuery();

        /**
         * Obtain the result of the total query
         * If we have having perform native count on temp table
         */
        if ($hasHaving) {
            $sql = $totalQuery->getSql();
		    $modelClass = $builder->_models;

			if (is_array($modelClass)) {
                $arr = array_values($modelClass);
                $modelClass = $arr[0];
            }

			$model = new $modelClass();
			$dbService = $model->getReadConnectionService();
			$db = $totalBuilder->getDI()->get(dbService);
			$row = $db->fetchOne("SELECT COUNT(*) as \"rowcount\" FROM (" . sql["sql"] . ") as T1", Db::FETCH_ASSOC, sql["bind"]);
		    $rowcount = $row ? intval($row["rowcount"]) : 0;
		    $totalPages = intval(ceil($rowcount / limit));
		} else {
            $result = $totalQuery->execute();
            $row = $result->getFirst();
            $rowcount = $row ? intval($row->rowcount) : 0;
            $totalPages = intval(ceil($rowcount / $limit));
        }

        if ($numberPage < $totalPages) {
            $next = $numberPage + 1;
        } else {
            $next = $totalPages;
        }

        $page = new \stdClass();
        $page->items = $items;
        $page->first = 1;
        $page->before = $before;
        $page->current = $numberPage;
        $page->last = $totalPages;
        $page->next = $next;
        $page->total_pages = $totalPages;
        $page->total_items = $rowcount;
        $page->limit = $this->_limitRows;

        return $page;

    }

}
