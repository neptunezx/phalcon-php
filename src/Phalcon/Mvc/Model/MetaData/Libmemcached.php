<?php

namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Cache\Backend\Libmemcached as LibmemcachedCache;
use Phalcon\Cache\Frontend\Data as FrontendData;
use Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\MetaData\Libmemcached
 *
 * Stores model meta-data in the Memcache.
 *
 * By default meta-data is stored for 48 hours (172800 seconds)
 *
 * <code>
 * $metaData = new Phalcon\Mvc\Model\Metadata\Libmemcached(
 *     [
 *         "servers" => [
 *             [
 *                 "host"   => "localhost",
 *                 "port"   => 11211,
 *                 "weight" => 1,
 *             ],
 *         ],
 *         "client" => [
 *             Memcached::OPT_HASH       => Memcached::HASH_MD5,
 *             Memcached::OPT_PREFIX_KEY => "prefix.",
 *         ],
 *         "lifetime" => 3600,
 *         "prefix"   => "my_",
 *     ]
 * );
 * </code>
 */
class Libmemcached extends MetaData
{

    protected $_ttl      = 172800;
    protected $_memcache = null;
    protected $_metaData = [];

    /**
     * Phalcon\Mvc\Model\MetaData\Libmemcached constructor
     *
     * @param array options
     */
    public function __construct($options = null)
    {
        if (!is_array($options)) {
            $options = [];
        }

        if (!isset($options["servers"])) {
            throw new Exception("No servers given in options");
        }

        if (isset($options["lifetime"])) {
            $this->_ttl = $options["lifetime"];
        }

        if (!isset($options["statsKey"])) {
            $options["statsKey"] = "_PHCM_MM";
        }

        $this->_memcache = new LibmemcachedCache(
            new FrontendData(["lifetime" => $this->_ttl]), $options
        );
    }

    /**
     * Reads metadata from Memcache
     */
    public function read($key)
    {
        $data = $this->_memcache->get($key);
        if (!is_array($data)) {
            return $data;
        }
        return null;
    }

    /**
     * Writes the metadata to Memcache
     */
    public function write($key, $data)
    {
        $this->_memcache->save($key, $data);
    }

    /**
     * Flush Memcache data and resets internal meta-data in order to regenerate it
     */
    public function reset()
    {
        $meta = $this->_metaData;

        if (!is_array($meta)) {
            foreach ($meta as $key => $_) {
                $realKey = "meta-" . $key;
                $this->_memcache->delete($realKey);
            }
        }

        parent::reset();
    }

}
