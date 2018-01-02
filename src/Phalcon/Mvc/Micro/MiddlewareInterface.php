<?php

namespace Phalcon\Mvc\Micro;

/**
 * Phalcon\Mvc\Micro\MiddlewareInterface
 *
 * Allows to implement Phalcon\Mvc\Micro middleware in classes
 */
interface MiddlewareInterface
{

    /**
     * Calls the middleware
     */
    public function call(\Phalcon\Mvc\Micro $application);
}
