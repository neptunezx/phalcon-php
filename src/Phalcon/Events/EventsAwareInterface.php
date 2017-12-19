<?php

namespace Phalcon\Events;

use Phalcon\Events\ManagerInterface;

/**
 * Phalcon\Events\EventsAwareInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/events/eventsawareinterface.c
 */
interface EventsAwareInterface
{

    /**
     * Sets the events manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager);

    /**
     * Returns the internal event manager
     *
     * @return \Phalcon\Events\ManagerInterface
     */
    public function getEventsManager();
}
