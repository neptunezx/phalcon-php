<?php

namespace Phalcon\Di;

use \Phalcon\Di;
use \Phalcon\DiInterface;
use \Phalcon\Di\Service;

/**
 * Phalcon\Di\FactoryDefault
 *
 * This is a variant of the standard Phalcon\Di. By default it automatically
 * registers all the services provided by the framework. Thanks to this, the developer does not need
 * to register each service individually providing a full stack framework
 */
class FactoryDefault extends Di implements DiInterface
{

    /**
     * \Phalcon\Di\FactoryDefault constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_services = [
            "router"             => new Service("router", "Phalcon\\Mvc\\Router", true),
            "dispatcher"         => new Service("dispatcher", "Phalcon\\Mvc\\Dispatcher", true),
            "url"                => new Service("url", "Phalcon\\Mvc\\Url", true),
            "modelsManager"      => new Service("modelsManager", "Phalcon\\Mvc\\Model\\Manager", true),
            "modelsMetadata"     => new Service("modelsMetadata", "Phalcon\\Mvc\\Model\\MetaData\\Memory", true),
            "response"           => new Service("response", "Phalcon\\Http\\Response", true),
            "cookies"            => new Service("cookies", "Phalcon\\Http\\Response\\Cookies", true),
            "request"            => new Service("request", "Phalcon\\Http\\Request", true),
            "filter"             => new Service("filter", "Phalcon\\Filter", true),
            "escaper"            => new Service("escaper", "Phalcon\\Escaper", true),
            "security"           => new Service("security", "Phalcon\\Security", true),
            "crypt"              => new Service("crypt", "Phalcon\\Crypt", true),
            "annotations"        => new Service("annotations", "Phalcon\\Annotations\\Adapter\\Memory", true),
            "session"            => new Service("session", "Phalcon\\Session\\Adapter\\Files", true),
            "sessionBag"         => new Service("sessionBag", "Phalcon\\Session\\Bag"),
            "eventsManager"      => new Service("eventsManager", "Phalcon\\Events\\Manager", true),
            "transactionManager" => new Service("transactionManager", "Phalcon\\Mvc\\Model\\Transaction\\Manager", true),
            "strategy"           => new Service("strategy", "Phalcon\\Mvc\Model\\MetaData\\Strategy\\Introspection", true),
        ];
    }

}
