<?php

namespace Phalcon\Cache\Frontend;

use Phalcon\Factory\Exception;
use Phalcon\Factory as BaseFactory;
use Phalcon\Config;
use Phalcon\Text;

/**
 * Loads Frontend Cache Adapter class using 'adapter' option
 *
 * <code>
 * use Phalcon\Cache\Frontend\Factory;
 *
 * $options = [
 *     "lifetime" => 172800,
 *     "adapter"  => "data",
 * ];
 * $frontendCache = Factory::load($options);
 * </code>
 */
class Factory extends BaseFactory
{

    /**
     * @param \Phalcon\Config|array config
     * @return \Phalcon\Cache\FrontendInterface
     */
    public static function load($config)
    {
        return self::loadClass("Phalcon\\Cache\\Frontend", $config);
    }

    protected static function loadClass($namespace, $config)
    {
        if (!is_string($namespace)) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_object($config) && $config instanceof Config) {
            $config = $config->toArray();
        }

        if (!is_array($config)) {
            throw new Exception("Config must be array or Phalcon\\Config object");
        }

        if (isset($config["adapter"])) {
            $className = $namespace . "\\" . Text::camelize($config["adapter"]);
            unset($config["adapter"]);
            if ($className == "Phalcon\\Cache\\Frontend\\None") {
                return new $className();
            } else {
                return new $className($config);
            }
        }

        throw new Exception("You must provide 'adapter' option in factory config parameter.");
    }

}
