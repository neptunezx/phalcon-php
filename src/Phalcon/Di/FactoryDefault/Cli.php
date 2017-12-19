<?php

/**
 * CLI Factory Default
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Di\FactoryDefault;

use \Phalcon\Di\FactoryDefault;
use \Phalcon\DiInterface;
use \Phalcon\Di\Service;

/**
 * Phalcon\Di\FactoryDefault\Cli
 *
 * This is a variant of the standard Phalcon\Di. By default it automatically
 * registers all the services provided by the framework.
 * Thanks to this, the developer does not need to register each service individually.
 * This class is specially suitable for CLI applications
 */
class Cli extends FactoryDefault implements DiInterface
{

    /**
     * Phalcon\Di\FactoryDefault\Cli constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_services = [
            "router"             => new Service("router", "Phalcon\\Cli\\Router", true),
            "dispatcher"         => new Service("dispatcher", "Phalcon\\Cli\\Dispatcher", true),
            "modelsManager"      => new Service("modelsManager", "Phalcon\\Mvc\\Model\\Manager", true),
            "modelsMetadata"     => new Service("modelsMetadata", "Phalcon\\Mvc\\Model\\MetaData\\Memory", true),
            "filter"             => new Service("filter", "Phalcon\\Filter", true),
            "escaper"            => new Service("escaper", "Phalcon\\Escaper", true),
            "annotations"        => new Service("annotations", "Phalcon\\Annotations\\Adapter\\Memory", true),
            "security"           => new Service("security", "Phalcon\\Security", true),
            "eventsManager"      => new Service("eventsManager", "Phalcon\\Events\\Manager", true),
            "transactionManager" => new Service("transactionManager", "Phalcon\\Mvc\\Model\\Transaction\\Manager", true)
        ];
    }

}
