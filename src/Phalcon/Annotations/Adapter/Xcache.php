<?php

namespace Phalcon\Annotations\Adapter;

use \Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Exception;
use \Phalcon\Annotations\Reflection;

/**
 * Phalcon\Annotations\Adapter\Xcache
 *
 * Stores the parsed annotations to XCache. This adapter is suitable for production
 *
 * <code>
 * $annotations = new \Phalcon\Annotations\Adapter\Xcache();
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/adapter/xcache.c
 */
class Xcache extends Adapter
{

    /**
     * Reads parsed annotations from XCache
     *
     * @param string $key
     * @return \Phalcon\Annotations\Reflection|false
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $serialized = xcache_get(strtolower('_PHAN' . $key));
        if (is_string($serialized)) {
            $data = unserialize($serialized);
            if (is_object($data)){
                return $data;
            }
        }
        return false;
    }

    /**
     * Writes parsed annotations to XCache
     *
     * @param string $key
     * @param \Phalcon\Annotations\Reflection $data
     * @throws Exception
     */
    public function write($key, $data)
    {
        if (is_string($key) === false ||
            is_object($data) === false ||
            $data instanceof Reflection === false) {
            throw new Exception('Invalid parameter type.');
        }

        xcache_set(strtolower("_PHAN" . $key), serialize($data));
    }

}
