<?php

namespace Phalcon\Config;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Config;
use Phalcon\Text;

/**
 * Loads Config Adapter class using 'adapter' option, if no extension is provided it will be added to filePath
 *
 * <code>
 * use Phalcon\Config\Factory;
 *
 * $options = [
 *     "filePath" => "path/config",
 *     "adapter"  => "php",
 * ];
 * $config = Factory::load($options);
 * </code>
 */
class Factory extends BaseFactory
{

    /**
     * @param \Phalcon\Config|array config
     */
    public static function load($config)
    {
        return self::loadClass("Phalcon\\Config\\Adapter", $config);
    }

    protected static function loadClass($namespace, $config)
    {
        if ($config && $config instanceof Config) {
            $config = $config->toArray();
        }

        if (!is_array($config)) {
            throw new Exception("Config must be array or Phalcon\\Config object");
        }

        if (!isset($config["filePath"])) {
            throw new Exception("You must provide 'filePath' option in factory config parameter.");
        }

        $filePath = $config["filePath"];
        if (isset($config["adapter"])) {
            $adapter   = $config["adapter"];
            $className = $namespace . "\\" . Text::camelize($adapter);
            if (!strpos($filePath, ".")) {
                $filePath = $filePath . "." . lcfirst($adapter);
            }

            if ($className == "Phalcon\\Config\\Adapter\\Ini") {
                if (isset($config["mode"])) {
                    return new $className($filePath, $config["mode"]);
                }
            } elseif ($className == "Phalcon\\Config\\Adapter\\Yaml") {
                if (isset($config["callbacks"])) {
                    return new $className($filePath, $config["callbacks"]);
                }
            }

            return new $className($filePath);
        }

        throw new Exception("You must provide 'adapter' option in factory config parameter.");
    }

}
