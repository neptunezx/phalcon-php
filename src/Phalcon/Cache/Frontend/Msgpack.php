<?php

namespace Phalcon\Cache\Frontend;

use Phalcon\Cache\FrontendInterface;
use Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Frontend\Msgpack
 *
 * Allows to cache native PHP data in a serialized form using msgpack extension
 * This adapter uses a Msgpack frontend to store the cached content and requires msgpack extension.
 *
 * @link https://github.com/msgpack/msgpack-php
 *
 * <code>
 * use Phalcon\Cache\Backend\File;
 * use Phalcon\Cache\Frontend\Msgpack;
 *
 * // Cache the files for 2 days using Msgpack frontend
 * $frontCache = new Msgpack(
 *     [
 *         "lifetime" => 172800,
 *     ]
 * );
 *
 * // Create the component that will cache "Msgpack" to a "File" backend
 * // Set the cache file directory - important to keep the "/" at the end of
 * // of the value for the folder
 * $cache = new File(
 *     $frontCache,
 *     [
 *         "cacheDir" => "../app/cache/",
 *     ]
 * );
 *
 * $cacheKey = "robots_order_id.cache";
 *
 * // Try to get cached records
 * $robots = $cache->get($cacheKey);
 *
 * if ($robots === null) {
 *     // $robots is null due to cache expiration or data do not exist
 *     // Make the database call and populate the variable
 *     $robots = Robots::find(
 *         [
 *             "order" => "id",
 *         ]
 *     );
 *
 *     // Store it in the cache
 *     $cache->save($cacheKey, $robots);
 * }
 *
 * // Use $robots
 * foreach ($robots as $robot) {
 *     echo $robot->name, "\n";
 * }
 * </code>
 */
class Msgpack extends Data implements FrontendInterface
{

    /**
     * Frontend Options
     *
     * @var array|null
     * @access protected
     */
    protected $_frontendOptions;

    /**
     * \Phalcon\Cache\Frontend\Base64 constructor
     *
     * @param array|null $frontendOptions
     * @throws Exception
     */
    public function __construct($frontendOptions = null)
    {
        if (is_array($frontendOptions) === false &&
            is_null($frontendOptions) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($frontendOptions["lifetime"])) {
            if (!is_int($frontendOptions["lifetime"])) {
                throw new Exception("Option 'lifetime' must be an integer");
            }
        }
        $this->_frontendOptions = $frontendOptions;
    }

    /**
     * Returns the cache lifetime
     *
     * @return integer
     */
    public function getLifetime()
    {
        if (is_array($this->_frontendOptions) === true &&
            isset($this->_frontendOptions['lifetime']) === true) {
            return $this->_frontendOptions['lifetime'];
        }

        return 1;
    }

    /**
     * Check whether if frontend is buffering output
     *
     * @return boolean
     */
    public function isBuffering()
    {
        return false;
    }

    /**
     * Starts output frontend. Actually, does nothing
     */
    public function start()
    {
        
    }

    /**
     * Returns output cached content
     *
     * @return string|null
     */
    public function getContent()
    {
        return null;
    }

    /**
     * Stops output frontend
     */
    public function stop()
    {
        
    }

    /**
     * Serializes data before storing it
     *
     * @param mixed $data
     * @return string
     */
    public function beforeStore($data)
    {
        return msgpack_pack($data);
    }

    /**
     * Unserializes data after retrieving it
     *
     * @param mixed $data
     * @return mixed
     */
    public function afterRetrieve($data)
    {
        return msgpack_unpack($data);
    }

}
