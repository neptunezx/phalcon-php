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

namespace Phalcon\Events;

/**
 * Phalcon\Events\EventInterface
 *
 * Interface for Phalcon\Events\Event class
 */
interface EventInterface
{
    /**
     * Gets event data
     */
    public function getData();

	/**
     * Sets event data
     * @param mixed|null data
     * @return <EventInterface>
     */
	public function setData($data = null);

	/**
     * Gets event type
     */
	public function getType();

	/**
     * Sets event type
     * @param string $type
     * @return <EventInterface>
     */
	public function setType($type);

	/**
     * Stops the event preventing propagation
     * @return <EventInterface>
     */
	public function stop();

	/**
     * Check whether the event is currently stopped
     * @return boolean
     */
	public function isStopped();

	/**
     * Check whether the event is cancelable
     * @return boolean
     */
	public function isCancelable();

    /**
     * Gets event Source
     */
    public function getSource();
}
