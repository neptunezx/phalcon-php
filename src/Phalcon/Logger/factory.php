<?php

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Config;

/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/28
 * Time: 下午9:04
 */
class Factory extends BaseFactory
{
    /**
     * @param \Phalcon\Config|array config
     * @return AdapterInterface
     * @throws Exception
     * @throws \Phalcon\Exception
     */
    public static function load($config)
    {
        return self::loadClass("Phalcon\\Logger\\Adapter", $config);
    }

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
            $adapter = $config["adapter"];
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
}