<?php

namespace Phalcon\Mvc;

/**
 * Phalcon\Mvc\ModuleDefinitionInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/moduledefinitioninterface.c
 */
interface ModuleDefinitionInterface
{

    /**
     * Registers an autoloader related to the module
     */
    public function registerAutoloaders();

    /**
     * Registers services related to the module
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function registerServices($dependencyInjector);
}
