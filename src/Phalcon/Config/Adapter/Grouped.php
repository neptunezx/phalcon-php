<?php

namespace Phalcon\Config\Adapter;

use Phalcon\Config;
use Phalcon\Factory\Exception;
use Phalcon\Config\Factory;

/**
 * Phalcon\Config\Adapter\Grouped
 *
 * Reads multiple files (or arrays) and merges them all together.
 *
 * @see Phalcon\Config\Factory::load To load Config Adapter class using 'adapter' option.
 *
 * <code>
 * use Phalcon\Config\Adapter\Grouped;
 *
 * $config = new Grouped(
 *     [
 *         "path/to/config.php",
 *         "path/to/config.dist.php",
 *     ]
 * );
 * </code>
 *
 * <code>
 * use Phalcon\Config\Adapter\Grouped;
 *
 * $config = new Grouped(
 *     [
 *         "path/to/config.json",
 *         "path/to/config.dist.json",
 *     ],
 *     "json"
 * );
 * </code>
 *
 * <code>
 * use Phalcon\Config\Adapter\Grouped;
 *
 * $config = new Grouped(
 *     [
 *         [
 *             "filePath" => "path/to/config.php",
 *             "adapter"  => "php",
 *         ],
 *         [
 *             "filePath" => "path/to/config.json",
 *             "adapter"  => "json",
 *         ],
 *         [
 *             "adapter"  => "array",
 *             "config"   => [
 *                 "property" => "value",
 *         ],
 *     ],
 * );
 * </code>
 */
class Grouped extends Config
{

    /**
     * Phalcon\Config\Adapter\Grouped constructor
     */
    public function __construct(array $arrayConfig, $defaultAdapter = "php")
    {
        parent::__construct([]);

        foreach ($arrayConfig as $configName) {
            $configInstance = $configName;

            // Set to default adapter if passed as string
            if (is_string($configName)) {
                $configInstance = ["filePath" => $configName, "adapter" => $defaultAdapter];
            } elseif (!isset($configInstance["adapter"])) {
                $configInstance["adapter"] = $defaultAdapter;
            }

            if (is_array($configInstance["adapter"])) {
                if (!isset($configInstance["config"])) {
                    throw new Exception(
                    "To use 'array' adapter you have to specify the 'config' as an array."
                    );
                } else {
                    $configArray    = $configInstance["config"];
                    $configInstance = new Config($configArray);
                }
            } else {
                $configInstance = Factory::load($configInstance);
            }

            $this->_merge($configInstance);
        }
    }

}
