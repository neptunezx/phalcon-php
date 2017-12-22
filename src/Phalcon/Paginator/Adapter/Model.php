<?php

/**
 * Paginator Model Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Paginator\Adapter;

use \Phalcon\Paginator\AdapterInterface;
use \Phalcon\Paginator\Exception;
use \stdClass;

/**
 * Phalcon\Paginator\Adapter\Model
 *
 * This adapter allows to paginate data using a Phalcon\Mvc\Model resultset as a base.
 *
 * <code>
 * use Phalcon\Paginator\Adapter\Model;
 *
 * $paginator = new Model(
 *     [
 *         "data"  => Robots::find(),
 *         "limit" => 25,
 *         "page"  => $currentPage,
 *     ]
 * );
 *
 * $paginate = $paginator->getPaginate();
 *</code>
 */
class Model extends Adapter
{

    /**
     * Limit Rows
     *
     * @var null|int
     * @access protected
     */
    protected $_limitRows;

    /**
     * Configuration
     *
     * @var null|array
     * @access protected
     */
    protected $_config = null;

    /**
     * Page
     *
     * @var null|int
     * @access protected
     */
    protected $_page;

    /**
     * \Phalcon\Paginator\Adapter\Model constructor
     *
     * @param array $config
     * @throws Exception
     */
    public function __construct($config)
    {
        if (is_array($config) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_config = $config;

        if (isset($this->_config['limit']) === true) {
            $this->_limitRows = $this->_config['limit'];
        }

        if (isset($this->_config['page']) === true) {
            $this->_page = $this->_config['page'];
        }
    }

    /**
     * Set the current page number
     *
     * @param int $page
     * @throws Exception
     */
    public function setCurrentPage($page)
    {
        if (is_int($page) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_page = $page;
    }

    /**
     * Returns a slice of the resultset to show in the pagination
     *
     * @return stdClass
     * @throws Exception
     */
    public function getPaginate()
    {

        $show = (int)$this->_limitRows;
        $config = $this->_config;
        $items = $config["data"];
        $pageNumber = (int)$this->_page;

        if (is_object($items)) {
            throw new Exception("Invalid data for paginator");
        }

        if (is_int($pageNumber) === false || $pageNumber <= 0) {
            $pageNumber = 1;
        }

        if ($show < 0) {
            throw new Exception('The start page number is zero or less');
        }

        $n = count($items);
        $lastShowPage = $pageNumber - 1;
        $start = $show * $lastShowPage;
        $pageItems = array();

        if ($n % $show != 0) {
            $totalPages = (int)($n / $show + 1);
        } else {
            $totalPages = (int)($n / $show);
        }

        if ($n > 0) {
            //Seek to the desired position
            if ($start < $n) {
                $items->seek($start);
            } else {
                $items->seek(0);
                $pageNumber = 1;
            }

            //The record must be iterable
            $i = 1;
            while ($items->valid()) {
                $pageItems[] = $items->current();
                if ($i > $show) {
                    break;
                }

                ++$i;
                $items->next();
            }
        }

        //Fix next
        $next = $pageNumber + 1;
        if ($next > $totalPages) {
            $next = $totalPages;
        }

        if ($pageNumber > 1) {
            $before = $pageNumber - 1;
        } else {
            $before = 1;
        }

        $page = new \stdClass();
        $page->items = $pageItems;
        $page->first = 1;
        $page->before = $before;
        $page->current = $pageNumber;
        $page->last = $totalPages;
        $page->next = $next;
        $page->total_pages = $totalPages;
        $page->total_items = $n;
        $page->limit = $this->_limitRows;

        return $page;
    }

}
