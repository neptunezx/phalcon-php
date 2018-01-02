<?php

namespace Phalcon\Mvc\Collection;

use Phalcon\Mvc\CollectionInterface;

/**
 * Phalcon\Mvc\Collection\BehaviorInterface
 *
 * Interface for Phalcon\Mvc\Collection\Behavior
 */
interface BehaviorInterface
{

    /**
     * This method receives the notifications from the EventsManager
     */
    public function notify($type, CollectionInterface $collection);

    /**
     * Calls a method when it's missing in the collection
     */
    public function missingMethod(CollectionInterface $collection, $method, $arguments = null);
}
