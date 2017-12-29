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

use Phalcon\DispatcherInterface as DispatcherInterfaceBase;

/**
 * Phalcon\Cli\DispatcherInterface
 *
 * Interface for Phalcon\Cli\Dispatcher
 */
interface DispatcherInterface extends DispatcherInterfaceBase
{

    /**
     * Sets the default task suffix
     * @param string $taskSuffix
     */
    public function setTaskSuffix($taskSuffix);

    /**
     * Sets the default task name
     * @param string $taskName
     */
    public function setDefaultTask($taskName);

    /**
     * Sets the task name to be dispatched
     * @param $taskName
     */
    public function setTaskName($taskName);

    /**
     * Gets last dispatched task name
     * @return string
     */
    public function getTaskName();

	/**
     * Returns the latest dispatched controller
     * @return <TaskInterface>
     */
	public function getLastTask();

	/**
     * Returns the active task in the dispatcher
     * @param <TaskInterface>
     */
	public function getActiveTask();
}
