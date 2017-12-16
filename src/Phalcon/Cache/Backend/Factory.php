<?php

namespace Phalcon\Cache\Backend;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\Frontend\Factory as FrontendFactory;
use Phalcon\Config;
use Phalcon\Text;

/**
 * Loads Backend Cache Adapter class using 'adapter' option, if frontend will be provided as array it will call Frontend Cache Factory
 *
 * <code>
 * use Phalcon\Cache\Backend\Factory;
 * use Phalcon\Cache\Frontend\Data;
 *
 * $options = [
 *     "prefix"   => "app-data",
 *     "frontend" => new Data(),
 *     "adapter"  => "apc",
 * ];
 * $backendCache = Factory::load($options);
 * </code>
 */
class Factory extends BaseFactory
{

    /**
     * @param \Phalcon\Config|array config
     * @return BackendInterface
     */
    public static function load($config)
    {
        return self::loadClass("Phalcon\\Cache\\Backend", $config);
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

        if (!isset($config["frontend"])) {
            throw new Exception("You must provide 'frontend' option in factory config parameter.");
        }

        if (isset($config["adapter"])) {
            $adapter  = $config["adapter"];
            $frontend = $config["frontend"];
            unset($config["adapter"]);
            unset($config["frontend"]);
            if (is_array($frontend) || $frontend instanceof Config) {
                $frontend = FrontendFactory::load($frontend);
            }
            $className = $namespace . "\\" . Text::camelize($adapter);

            return new $className($frontend, $config);
        }

        throw new Exception("You must provide 'adapter' option in factory config parameter.");
    }

}
