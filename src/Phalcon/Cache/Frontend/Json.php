<?php

namespace Phalcon\Cache\Frontend;

use \Phalcon\Cache\FrontendInterface;
use \Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Frontend\Json
 *
 * Allows to cache data converting/deconverting them to JSON.
 *
 * This adapter uses the json_encode/json_decode PHP's functions
 *
 * As the data is encoded in JSON other systems accessing the same backend could
 * process them
 *
 * <code>
 * <?php
 *
 * // Cache the data for 2 days
 * $frontCache = new \Phalcon\Cache\Frontend\Json(
 *     [
 *         "lifetime" => 172800,
 *     ]
 * );
 *
 * // Create the Cache setting memcached connection options
 * $cache = new \Phalcon\Cache\Backend\Memcache(
 *     $frontCache,
 *     [
 *         "host"       => "localhost",
 *         "port"       => 11211,
 *         "persistent" => false,
 *     ]
 * );
 *
 * // Cache arbitrary data
 * $cache->save("my-data", [1, 2, 3, 4, 5]);
 *
 * // Get data
 * $data = $cache->get("my-data");
 * </code>
 */
class Json implements FrontendInterface
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
        return json_encode($data);
    }

    /**
     * Unserializes data after retrieving it
     *
     * @param mixed $data
     * @return mixed
     */
    public function afterRetrieve($data)
    {
        return json_decode($data);
    }

}
