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
 *  $paginator = new \Phalcon\Paginator\Adapter\Model(
 *      array(
 *          "data"  => array(
 *              array('id' => 1, 'name' => 'Artichoke'),
 *              array('id' => 2, 'name' => 'Carrots'),
 *              array('id' => 3, 'name' => 'Beet'),
 *              array('id' => 4, 'name' => 'Lettuce'),
 *              array('id' => 5, 'name' => '')
 *          ),
 *          "limit" => 2,
 *          "page"  => $currentPage
 *      )
 *  );
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/paginator/adapter/nativearray.c
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
    protected $_config;

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
     * @param array $config
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
        $items = $this->_config['data'];

        if (is_array($items) === false) {
            throw new Exception('Invalid data for paginator');
        }

        //@note no is_null check for $this->_limitRows
        $show       = $this->_limitRows;
        $pageNumber = $this->_page;

        if (is_null($pageNumber) === true) {
            $pageNumber = 0;
        }

        $number = count($items);

        $roundedTotal = $number / $show;
        $totalPages   = (int) $roundedTotal;

        //Increase total pages if it wasn't iteger
        if ($totalPages !== $roundedTotal) {
            $totalPages++;
        }

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
