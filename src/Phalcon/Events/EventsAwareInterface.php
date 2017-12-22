<?php

namespace Phalcon\Events;


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
     * @param ManagerInterface $eventsManager
     */
    public function setEventsManager($eventsManager);

    /**
     * Returns the internal event manager
     *
     * @return ManagerInterface
     */
    public function getEventsManager();
}
