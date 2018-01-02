<?php

namespace Phalcon\Mvc\Micro;

use \Phalcon\Mvc\Micro\CollectionInterface;
use \Phalcon\Mvc\Micro\Exception;

/**
 * Phalcon\Mvc\Micro\Collection
 *
 * Groups Micro-Mvc handlers as controllers
 *
 * <code>
 * $app = new \Phalcon\Mvc\Micro();
 *
 * $collection = new Collection();
 *
 * $collection->setHandler(
 *     new PostsController()
 * );
 *
 * $collection->get("/posts/edit/{id}", "edit");
 *
 * $app->mount($collection);
 * </code>
 */
class Collection implements CollectionInterface
{

    /**
     * Prefix
     *
     * @var null|string
     * @access protected
     */
    protected $_prefix;

    /**
     * Lazy
     *
     * @var null|boolean
     * @access protected
     */
    protected $_lazy;

    /**
     * Handler
     *
     * @var null|mixed
     * @access protected
     */
    protected $_handler;

    /**
     * Handlers
     *
     * @var null|array
     * @access protected
     */
    protected $_handlers;

    /**
     * Internal function to add a handler to the group
     *
     * @param string|array method
     * @param string $routePattern
     * @param mixed $handler
     * @param string $name
     */
    private function _addMap($method, $routePattern, $handler, $name)
    {
        if (!is_string($routePattern)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_handlers[] = array($method, $routePattern, $handler, $name);
    }

    /**
     * Sets a prefix for all routes added to the collection
     *
     * @param string $prefix
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function setPrefix($prefix)
    {
        if (!is_string($prefix)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * Returns the collection prefix if any
     *
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Returns the registered handlers
     *
     * @return array|null
     */
    public function getHandlers()
    {
        return $this->_handlers;
    }

    /**
     * Sets the main handler
     *
     * @param mixed $handler
     * @param boolean $lazy
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function setHandler($handler, $lazy = false)
    {
        $this->_handler = $handler;
        $this->_lazy    = (bool) $lazy;

        return $this;
    }

    /**
     * Sets if the main handler must be lazy loaded
     *
     * @param boolean $lazy
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function setLazy($lazy)
    {
        $this->_lazy = (bool) $lazy;
        return $this;
    }

    /**
     * Returns if the main handler must be lazy loaded
     *
     * @return boolean|null
     */
    public function isLazy()
    {
        return $this->_lazy;
    }

    /**
     * Returns the main handler
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * Maps a route to a handler
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function map($routePattern, $handler, $name = null)
    {
        $this->_addMap(null, $routePattern, $handler, $name);
        return $this;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is GET
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function get($routePattern, $handler, $name = null)
    {
        $this->_addMap('GET', $routePattern, $handler, $name);
        return $this;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is POST
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function post($routePattern, $handler, $name = null)
    {
        $this->_addMap('POST', $routePattern, $handler, $name);
        return $this;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is PUT
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function put($routePattern, $handler, $name = null)
    {
        $this->_addMap('PUT', $routePattern, $handler, $name);
        return $this;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is PATCH
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function patch($routePattern, $handler, $name = null)
    {
        $this->_addMap('PATCH', $routePattern, $handler, $name);
        return $this;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is HEAD
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function head($routePattern, $handler, $name = null)
    {
        $this->_addMap('HEAD', $routePattern, $handler, $name);
        return $this;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is DELETE
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function delete($routePattern, $handler, $name = null)
    {
        $this->_addMap('DELETE', $routePattern, $handler, $name);
        return $this;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is OPTIONS
     *
     * @param string $routePattern
     * @param callable $handler
     * @param string $name
     * @return \Phalcon\Mvc\Micro\CollectionInterface
     * @throws Exception
     */
    public function options($routePattern, $handler, $name = null)
    {
        $this->_addMap('OPTIONS', $routePattern, $handler, $name);
        return $this;
    }

}
