<?php

namespace Phalcon\Mvc\Micro;

/**
 * Phalcon\Mvc\Micro\MiddlewareInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/micro/middlewareinterface.c
 */
interface MiddlewareInterface
{

    /**
     * Calls the middleware
     *
     * @param \Phalcon\Mvc\Micro $application
     */
    public function call(\Phalcon\Mvc\Micro $application);
}
