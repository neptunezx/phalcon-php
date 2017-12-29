<?php

namespace Phalcon\Logger;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Config;

/**
 * Loads Logger Adapter class using 'adapter' option
 *
 * <code>
 * use Phalcon\Logger\Factory;
 *
 * $options = [
 *     "name"    => "log.txt",
 *     "adapter" => "file",
 * ];
 * $logger = Factory::load($options);
 * </code>
 */
class Factory extends BaseFactory
{

    /**
     * @param $namespace
     * @param $config
     * @return mixed
     * @throws Exception
     * @throws \Phalcon\Exception
     */
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
            $className = $namespace . "\\" . \Phalcon\Text::camelize($adapter);
            if ($className != "Phalcon\\Logger\\Adapter\\Firephp") {
                unset($config["adapter"]);
                if (!isset($config["name"])) {
                    throw new Exception("You must provide 'name' option in factory config parameter.");
                }
                $name = $config["name"];
                unset($config["name"]);

                return new $className($name, $config);
            }

            return new $className();
        }
        throw new Exception("You must provide 'adapter' option in factory config parameter.");
    }

    /**
     * @param \Phalcon\Config|array config
     * 
     * @return object
     */
    public static function load($config)
    {
        return self::loadClass("Phalcon\\Logger\\Adapter", $config);
    }

}
