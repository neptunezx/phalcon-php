<?php

namespace Phalcon\Config\Adapter;

use \Phalcon\Config;
use \Phalcon\Config\Exception;

/**
 * Phalcon\Config\Adapter\Json
 *
 * Reads JSON files and converts them to Phalcon\Config objects.
 *
 * Given the following configuration file:
 *
 * <code>
 * {"phalcon":{"baseuri":"\/phalcon\/"},"models":{"metadata":"memory"}}
 * </code>
 *
 * You can read it as follows:
 *
 * <code>
 *  $config = new Phalcon\Config\Adapter\Json("path/config.json");
 *  echo $config->phalcon->baseuri;
 *  echo $config->models->metadata;
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/config/adapter/json.c
 */
class Json extends Config
{

    /**
     * \Phalcon\Config\Adapter\Json constructor
     *
     * @param string $filePath
     * @throws Exception
     */
    public function __construct($filePath)
    {
        if (is_string($filePath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        @$contents = file_get_contents($filePath);
        if (is_string($contents) === true) {
            $array = json_decode($contents, true);

            if (json_last_error() !== \JSON_ERROR_NONE) {
                throw new Exception('Invalid json file.');
            }

            parent::__construct($array);
        } else {
            throw new Exception('Unable to read json file.');
        }
    }

}
