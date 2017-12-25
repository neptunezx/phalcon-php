<?php
/*
 +------------------------------------------------------------------------+
 | Phalcon Framework                                                      |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2017 Phalcon Team (http://www.phalconphp.com)       |
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

namespace Phalcon;

use Phalcon\Application\Exception;
use Phalcon\Di\Injectable;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;

/**
 * Phalcon\Application
 *
 * Base class for Phalcon\Cli\Console and Phalcon\Mvc\Application.
 */
abstract class Application extends Injectable implements EventsAwareInterface
{

	protected $_eventsManager;

	protected $_dependencyInjector;

	/**
	 * @var string
	 */
	protected $_defaultModule;

	/**
	 * @var array
	 */
	protected $_modules = array();

	/**
	 * Phalcon\Application
	 * @param DiInterface $dependencyInjector
	 */
	public function __construct($dependencyInjector = null)
	{
		if (!is_object($dependencyInjector)) {
			$this->_dependencyInjector = $dependencyInjector;
		}
	}

	/**
	 * Sets the events manager
	 * @param ManagerInterface $eventsManager
	 * @return Application
	 */
	public function setEventsManager(ManagerInterface $eventsManager)
	{
		$this->_eventsManager = $eventsManager;
		return $this;
	}

	/**
	 * Returns the internal event manager
	 */
	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	/**
	 * Register an array of modules present in the application
	 *
	 * <code>
	 * $this->registerModules(
	 *     [
	 *         "frontend" => [
	 *             "className" => "Multiple\\Frontend\\Module",
	 *             "path"      => "../apps/frontend/Module.php",
	 *         ],
	 *         "backend" => [
	 *             "className" => "Multiple\\Backend\\Module",
	 *             "path"      => "../apps/backend/Module.php",
	 *         ],
	 *     ]
	 * );
	 * </code>
	 * @param array $modules
	 * @param boolean $merge
	 * @return Application
	 */
	public function registerModules(array $modules, $merge = false)
	{
		if ($merge) {
			$this->_modules = array_merge($this->_modules, $modules);
		} else {
			$this->_modules = $modules;
		}

		return $this;
	}

	/**
	 * Return the modules registered in the application
	 * @return array
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Gets the module definition registered in the application via module name
	 * @param string $name
	 * @return array|object
	 * @throws \Phalcon\Exception
	 */
	public function getModule($name)
	{
		$module = $this->_modules["name"];
		if (!$module) {
			throw new Exception("Module '" . $name . "' isn't registered in the application container");
		}
		return $module;
	}

	/**
	 * Sets the module name to be used if the router doesn't return a valid module
	 * @param string $defaultModule
	 * @return Application
	 */
	public function setDefaultModule($defaultModule)
	{
		$this->_defaultModule = $defaultModule;
		return $this;
	}

	/**
	 * Returns the default module name
	 * @return string
	 */
	public function getDefaultModule()
	{
		return $this->_defaultModule;
	}

	/**
	 * Handles a request
	 */
	abstract public function handle();
}
