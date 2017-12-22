<?php

namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\ModelInterface;

/**
 * Phalcon\Mvc\Model\BehaviorInterface
 *
 * Interface for Phalcon\Mvc\Model\Behavior
 */
interface BehaviorInterface
{

    /**
     * This method receives the notifications from the EventsManager
     *
     * @param string $type
     * @param \Phalcon\Mvc\ModelInterface $model
     */
    public function notify($type, ModelInterface $model);

    /**
     * Calls a method when it's missing in the model
     *
     * @param \Phalcon\Mvc\ModelInterface $model
     * @param string $method
     * @param array|null $arguments
     */
    public function missingMethod(ModelInterface $model, $method, $arguments = null);
}
