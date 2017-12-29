<?php

namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Cache\Backend\Redis as RedisCache;
use Phalcon\Cache\Frontend\Data as FrontendData;

/**
 * Phalcon\Mvc\Model\MetaData\Redis
 *
 * Stores model meta-data in the Redis.
 *
 * By default meta-data is stored for 48 hours (172800 seconds)
 *
 * <code>
 * use Phalcon\Mvc\Model\Metadata\Redis;
 *
 * $metaData = new Redis(
 *     [
 *         "host"       => "127.0.0.1",
 *         "port"       => 6379,
 *         "persistent" => 0,
 *         "statsKey"   => "_PHCM_MM",
 *         "lifetime"   => 172800,
 *         "index"      => 2,
 *     ]
 * );
 * </code>
 */
class Redis extends MetaData
{

    protected $_ttl      = 172800;
    protected $_redis    = null;
    protected $_metaData = [];

    /**
     * Phalcon\Mvc\Model\MetaData\Redis constructor
     *
     * @param array options
     */
    public function __construct($options = null)
    {
        if (!is_array($options)) {
            $options = [];
        }

        if (!isset($options['host'])) {
            $options["host"] = "127.0.0.1";
        }

        if (!isset($options['port'])) {
            $options["port"] = 6379;
        }

        if (!isset($options['persistent'])) {
            $options["persistent"] = 0;
        }

        if (!isset($options['statsKey'])) {
            $options["statsKey"] = "_PHCM_MM";
        }

        if (isset($options["lifetime"])) {
            $this->_ttl = $options["lifetime"];
        }

        $this->_redis = new RedisCache(
            new FrontendData(["lifetime" => $this->_ttl]), $options
        );
    }

    /**
     * Reads metadata from Redis
     * 
     * @param strin $key
     * @return array|null
     */
    public function read($key)
    {
        $data = $this->_redis->get($key);
        if (is_array($data)) {
            return $data;
        }
        return null;
    }

    /**
     * Writes the metadata to Redis
     * 
     * @param string $key
     * @param mixed $data
     * @return void
     */
    public function write($key, $data)
    {
        $this->_redis->save($key, $data);
    }

    /**
     * Flush Redis data and resets internal meta-data in order to regenerate it
     * 
     * @return void
     */
    public function reset()
    {
        $meta = $this->_metaData;
        if (is_array($meta)) {
            foreach ($meta as $key => $_) {
                $realKey = "meta-" . $key;

                $this->_redis->delete($realKey);
            }
        }

        parent::reset();
    }

}
