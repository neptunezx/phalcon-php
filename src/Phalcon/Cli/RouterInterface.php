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
 +------------------------------------------------------------------------+
 */

namespace Phalcon\Cli;

/**
 * Phalcon\Cli\RouterInterface
 *
 * Interface for Phalcon\Cli\Router
 */
interface RouterInterface
{

    /**
     * Sets the name of the default module
     * @param string $moduleName
     */
    public function setDefaultModule($moduleName);

	/**
     * Sets the default task name
     * @param string $taskName
     */
	public function setDefaultTask($taskName);

	/**
     * Sets the default action name
     * @param $actionName
     */
	public function setDefaultAction($actionName);

	/**
     * Sets an array of default paths
     * @param array $defaults
     */
	public function setDefaults($defaults);

	/**
     * Handles routing information received from the rewrite engine
     *
     * @param array|null $arguments
     */
	public function handle($arguments = null);

	/**
     * Adds a route to the router on any HTTP method
     * @param string $pattern
     * @param mixed | null $paths
     * @return <RouteInterface>
     */
	public function add($pattern,$paths = null);

	/**
     * Returns processed module name
     * @return string
     */
	public function getModuleName();

	/**
     * Returns processed task name
     * @return string
     */
	public function getTaskName();

	/**
     * Returns processed action name
     * @return string
     */
	public function getActionName();

	/**
     * Returns processed extra params
     * @return array
     */
	public function getParams();

	/**
     * Returns the route that matches the handled URI
     * @return <RouteInterface>
     */
	public function getMatchedRoute();

	/**
     * Return the sub expressions in the regular expression matched
     * @return array
     */
	public function getMatches();

	/**
     * Check if the router matches any of the defined routes
     * @return boolean
     */
	public function wasMatched();

	/**
     * Return all the routes defined in the router
     * @return <RouteInterface[]>
     */
	public function getRoutes();

	/**
     * Returns a route object by its id
     * @param mixed $id
     * @return <RouteInterface>
     */
	public function getRouteById($id);

	/**
     * Returns a route object by its name
     * @param string $name
     * @return <RouteInterface>
     */
	public function getRouteByName($name);
}
