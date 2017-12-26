<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/22
 * Time: 上午11:50
 */

namespace Phalcon\Paginator;

/**
 * Phalcon\Paginator\Adapter
 */
abstract class Adapter implements AdapterInterface
{

    /**
     * Number of rows to show in the paginator. By default is null
     */
    protected $_limitRows = null;

    /**
     * Current page in paginate
     */
    protected $_page = null;

    /**
     * Set the current page number
     *
     * @param $page int
     * @return Adapter
     */
    public function setCurrentPage($page)
    {
        $this->_page = $page;
        return $this;
    }

    /**
     * Set current rows limit
     *
     * @param $limitRows int
     * @return Adapter
     */
    public function setLimit($limitRows)
    {
        $this->_limitRows = $limitRows;
        return $this;
    }

    /**
     * Get current rows limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->_limitRows;
    }
}

