<?php

namespace Phalcon\Cache\Backend;

use \Phalcon\Cache\Backend;
use \Phalcon\Cache\Exception;
use \APCIterator;
use \Iterator;

/**
 * Phalcon\Cache\Backend\Apc
 *
 * Allows to cache output fragments, PHP data and raw data using an APC backend
 *
 * <code>
 * use Phalcon\Cache\Backend\Apc;
 * use Phalcon\Cache\Frontend\Data as FrontData;
 *
 * // Cache data for 2 days
 * $frontCache = new FrontData(
 *     [
 *         "lifetime" => 172800,
 *     ]
 * );
 *
 * $cache = new Apc(
 *     $frontCache,
 *     [
 *         "prefix" => "app-data",
 *     ]
 * );
 *
 * // Cache arbitrary data
 * $cache->save("my-data", [1, 2, 3, 4, 5]);
 *
 * // Get data
 * $data = $cache->get("my-data");
 * </code>
 *
 * @see \Phalcon\Cache\Backend\Apcu
 * @deprecated
 */
class Apc extends Backend
{

    /**
     * Returns a cached content
     *
     * @param string $keyName
     * @param int|null $lifetime
     * @return mixed
     * @throws Exception
     */
    public function get($keyName, $lifetime = null)
    {
        /* Type check */
        if (is_string($keyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($lifetime) === false && is_int($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Fetch data */
        $this->_lastKey = '_PHCA' . $this->_prefix . $keyName;

        $cachedContent = apc_fetch($this->_lastKey);
        if ($cachedContent === false) {
            return null;
        }

        /* Processing */
        return $this->_frontend->afterRetrieve($cachedContent);
    }

    /**
     * Stores cached content into the APC backend and stops the frontend
     *
     * @param string|null $keyName
     * @param string|null $content
     * @param int|null $lifetime
     * @param boolean $stopBuffer
     * @throws Exception
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
    {
        /* Prepare input data */
        if (is_null($keyName) === true) {
            $lastKey = $this->_lastKey;

            if (isset($lastKey) === false) {
                throw new Exception('The cache must be started first');
            }
        } else if (is_string($keyName) === true) {
            $lastKey = '_PHCA' . $this->_prefix . $keyName;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($content) === true) {
            $content = $this->_frontend->getContent();
        } elseif (is_string($content) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!is_numeric($content)) {
            $preparedContent = $frontend->beforeStore($content);
        } else {
            $preparedContent = $content;
        }

        if (is_null($lifetime) === true) {
            $lifetime = $this->_lastLifetime;
            if (is_null($lifetime) === true) {
                $lifetime = $this->_frontend->getLifetime();
            } else {
                $this->_lastKey = $lastKey;
            }
        } elseif (is_int($lifetime) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Store data */
        $success = apc_store($lastKey, $preparedContent, $lifetime);
        if (!$success) {
            throw new Exception("Failed storing data in apc");
        }

        /* Buffer */
        $isBuffering = $this->_frontend->isBuffering();
        if (boolval($stopBuffer) === true) {
            $this->_frontend->stop();
        }
        if ($isBuffering === true) {
            echo $content;
        }
        $this->_started = false;

        return success;
    }

    /**
     * Increment of a given key, by number $value
     *
     * @param string keyName
     * @param int $value
     * @return int|boolean
     */
    public function increment($keyName = null, $value = 1)
    {
        $prefixedKey    = "_PHCA" . $this->_prefix . $keyName;
        $this->_lastKey = $prefixedKey;

        if (function_exists("apc_inc")) {
            $result = apc_inc($prefixedKey, $value);
            return $result;
        } else {
            $cachedContent = apc_fetch($prefixedKey);

            if (is_numeric($cachedContent)) {
                $result = $cachedContent + $value;
                $this->save($keyName, $result);
                return $result;
            }
        }

        return false;
    }

    /**
     * Decrement of a given key, by number $value
     *
     * @param string keyName
     * @param int $value
     * @return int|boolean
     */
    public function decrement($keyName = null, $value = 1)
    {
        $lastKey        = "_PHCA" . $this->_prefix . $keyName;
        $this->_lastKey = $lastKey;

        if (function_exists("apc_dec")) {
            return apc_dec($lastKey, $value);
        } else {
            $cachedContent = apc_fetch($lastKey);

            if (is_numeric($cachedContent)) {
                $result = $cachedContent - $value;
                $this->save($keyName, $result);
                return $result;
            }
        }

        return false;
    }

    /**
     * Deletes a value from the cache by its key
     *
     * @param string $keyName
     * @return boolean
     * @throws Exception
     */
    public function delete($keyName)
    {
        if (is_string($keyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return apc_delete('_PHCA' . $this->_prefix . $keyName);
    }

    /**
     * Query the existing cached keys
     *
     * @param string|null $prefix
     * @return array
     * @throws Exception
     */
    public function queryKeys($prefix = null)
    {
        if (is_null($prefix) === false) {
            $prefix = '/^_PHCA/';
        } elseif (is_string($prefix) === true) {
            $prefix = '/^_PHCA' . $prefix . '/';
        } else {
            throw new Exception('Invalid parameter types.');
        }

        $prefixlength = strlen($prefix);

        $keys = array();

        $iterator = new APCIterator('user', $prefix);

        //APCIterator implements Iterator
        if ($iterator instanceof Iterator === false) {
            throw new Exception('Invalid APC iteration class.');
        }

        foreach ($iterator as $key => $value) {
            if (is_string($key) === true) {
                $keys[] = substr($key, $prefixlength);
            }
        }

        return $keys;
    }

    /**
     * Checks if cache exists and it hasn't expired
     *
     * @param string|null $keyName
     * @param int|null $lifetime
     * @return boolean
     * @throws Exception
     */
    public function exists($keyName = null, $lifetime = null)
    {
        if (is_null($keyName) === true) {
            $lastKey = $this->_lastKey;
        } elseif (is_string($keyName) === true) {
            $lastKey = '_PHCA' . $this->_prefix . $keyName;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($lastKey) === true &&
            apc_exists($lastKey) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Immediately invalidates all existing items.
     *
     * <code>
     * use Phalcon\Cache\Backend\Apc;
     *
     * $cache = new Apc($frontCache, ["prefix" => "app-data"]);
     *
     * $cache->save("my-data", [1, 2, 3, 4, 5]);
     *
     * // 'my-data' and all other used keys are deleted
     * $cache->flush();
     * </code>
     * 
     * @return  boolean
     */
    public function flush()
    {
        $prefixPattern = "/^_PHCA" . $this->_prefix . "/";

        foreach (iterator(new \APCIterator("user", $prefixPattern)) as $item) {
            apc_delete($item["key"]);
        }

        return true;
    }

}
