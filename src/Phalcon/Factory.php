<?php

namespace Phalcon;

use Phalcon\Factory\Exception;
use Phalcon\Config;
use Phalcon\FactoryInterface;

abstract class Factory implements FactoryInterface
{

    protected static function loadClass($namespace, $config)
    {
        if (is_object($config) && $config instanceof Config) {
            $config = $config->toArray();
        }

        if (!is_array($config)) {
            throw new Exception("Config must be array or Phalcon\\Config object");
        }

        if (isset($config["adapter"])) {
            $adapter   = $config["adapter"];
            unset($config["adapter"]);
            $className = $namespace . "\\" . $adapter;

            return new $className($config);
        }

        throw new Exception("You must provide 'adapter' option in factory config parameter.");
    }

}
