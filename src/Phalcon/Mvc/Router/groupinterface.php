<?php
/*
 +------------------------------------------------------------------------+
 | Phalcon Framework                                                      |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2017 Phalcon Team (https://phalconphp.com)          |
 +------------------------------------------------------------------------+
 | This source file is subject to the New BSD License that is bundled     |
 | with this package in the file LICENSE.txt.                             |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@phalconphp.com so we can send you a copy immediately.       |
 +------------------------------------------------------------------------+
 | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
 |          Eduar Carvajal <eduar@phalconphp.com>                         |
 |          Stanislav Kiryukhin <korsar.zn@gmail.com>                     |
 +------------------------------------------------------------------------+
 */

namespace Phalcon\Mvc\Router;

use Phalcon\Mvc\Router\RouteInterface;

/**
 * Phalcon\Mvc\Router\GroupInterface
 *
 *
 *<code>
 * $router = new \Phalcon\Mvc\Router();
 *
 * // Create a group with a common module and controller
 * $blog = new Group(
 *     [
 *         "module"     => "blog",
 *         "controller" => "index",
 *     ]
 * );
 *
 * // All the routes start with /blog
 * $blog->setPrefix("/blog");
 *
 * // Add a route to the group
 * $blog->add(
 *     "/save",
 *     [
 *         "action" => "save",
 *     ]
 * );
 *
 * // Add another route to the group
 * $blog->add(
 *     "/edit/{id}",
 *     [
 *         "action" => "edit",
 *     ]
 * );
 *
 * // This route maps to a controller different than the default
 * $blog->add(
 *     "/blog",
 *     [
 *         "controller" => "about",
 *         "action"     => "index",
 *     ]
 * );
 *
 * // Add the group to the router
 * $router->mount($blog);
 *</code>
 */
interface GroupInterface
{

	/**
	 * Set a hostname restriction for all the routes in the group
     *
     * @param  string  $hostname
     * @return GroupInterface
	 */
	public function setHostname($hostname);

	/**
	 * Returns the hostname restriction
     *
     * @return string
	 */
	public function getHostname();

	/**
	 * Set a common uri prefix for all the routes in this group
     *
     * @param string $prefix
     * @return GroupInterface
	 */
	public function setPrefix($prefix);

	/**
	 * Returns the common prefix for all the routes
     *
     * @return string
	 */
	public function getPrefix() ;

	/**
	 * Sets a callback that is called if the route is matched.
	 * The developer can implement any arbitrary conditions here
	 * If the callback returns false the route is treated as not matched
     *
     * @param callable $beforeMatch
     * @return GroupInterface
	 */
	 public function beforeMatch($beforeMatch);

	/**
	 * Returns the 'before match' callback if any
     *
     * @return callable
	 */
	public function getBeforeMatch();

	/**
	 * Set common paths for all the routes in the group
	 *
	 * @param array $paths
	 * @return \Phalcon\Mvc\Router\Group
	 */
	public function setPaths($paths);

	/**
	 * Returns the common paths defined for this group
     *
     * @return  array | string
	 */
	public function getPaths() ;

	/**
	 * Returns the routes added to the group
     *
     * @return RouteInterface[]
	 */
	public function getRoutes() ;

	/**
	 * Adds a route to the router on any HTTP method
	 *
	 *<code>
	 * router->add("/about", "About::index");
	 *</code>
     *
     * @param string $pattern
     * @param $paths
     * @param string $httpMethods
     * @return  RouteInterface
	 */
	public function add($pattern, $paths = null, $httpMethods = null);

	/**
	 * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string $pattern
     * @param $paths
     * @return  RouteInterface
     */
	public function addGet($pattern, $paths = null);

	/**
	 * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string $pattern
     * @param $paths
     * @return RouteInterface
	 */
	public function addPost($pattern, $paths = null);

	/**
	 * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string $pattern
     * @param $paths
     * @return RouteInterface
	 */
	public function addPut($pattern, $paths = null);

	/**
	 * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string $pattern
     * @param $paths
     * @return RouteInterface
	 */
	public function addPatch($pattern, $paths = null);

	/**
	 * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string $pattern
     * @param $paths
     * @return RouteInterface
	 */
	public function addDelete($pattern, $paths = null);

	/**
	 * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string $pattern
     * @param $paths
     * @return RouteInterface
	 */
	public function addOptions($pattern, $paths = null);

	/**
	 * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string $pattern
     * @param $paths
     * @return RouteInterface
	 */
	public function addHead($pattern, $paths = null);

	/**
	 * Removes all the pre-defined routes
	 */
	public function clear();
}
