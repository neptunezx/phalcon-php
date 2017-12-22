<?php

/**
 * Paginator Native Array Adapter
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
 * Phalcon\Paginator\Adapter\NativeArray
 *
 * Pagination using a PHP array as source of data
 *
 * <code>
 * use Phalcon\Paginator\Adapter\NativeArray;
 *
 * $paginator = new NativeArray(
 *     [
 *         "data"  => [
 *             ["id" => 1, "name" => "Artichoke"],
 *             ["id" => 2, "name" => "Carrots"],
 *             ["id" => 3, "name" => "Beet"],
 *             ["id" => 4, "name" => "Lettuce"],
 *             ["id" => 5, "name" => ""],
 *         ],
 *         "limit" => 2,
 *         "page"  => $currentPage,
 *     ]
 * );
 *</code>
 */
class NativeArray implements AdapterInterface
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
    protected $_config = array();

    /**
     * Page
     *
     * @var null|int
     * @access protected
     */
    protected $_page;

    /**
     * \Phalcon\Paginator\Adapter\NativeArray constructor
     *
     * @param $config array
     * @throws Exception
     */
    public function __construct($config)
    {
        if (is_array($config) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_config = $config;

        if (isset($config['limit']) === true) {
            $this->_limitRows = $config['limit'];
        }

        if (isset($config['page']) === true) {
            $this->_page = $config['page'];
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
        if (is_array($items) === false) {
            throw new Exception('Invalid data for paginator');
        }
        $config = $this->_config;
		$items  = config["data"];

        //@note no is_null check for $this->_limitRows
        $show       = (int) $this->_limitRows;
        $pageNumber = (int) $this->_page;

        if (is_null($pageNumber) === true || pageNumber <= 0) {
            $pageNumber = 0;
        }

        $number = count($items);
        $roundedTotal = $number / floatval($show);
        $totalPages   = (int) $roundedTotal;

        //Increase total pages if it wasn't iteger
        if ($totalPages !== $roundedTotal) {
            $totalPages++;
        }
        $items = array_slice($items, $show * ($pageNumber - 1), $show);

        /* Generate stdClass object */
        $page              = new stdClass();
        $page->items       = array_slice($items, ($show * ($pageNumber - 1)), $show);
        $page->first       = 1;
        $page->last        = $totalPages;
        $page->next        = ($pageNumber < $totalPages ? $pageNumber + 1 : $totalPages);
        $page->before      = ($pageNumber > 1 ? $pageNumber - 1 : 1);
        $page->current     = $pageNumber;
        $page->total_pages = $totalPages;
        $page->total_items = $number;

        return $page;
    }

}
