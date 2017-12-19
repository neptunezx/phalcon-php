<?php

namespace Phalcon\Annotations\Adapter;

use \Phalcon\Annotations\AdapterInterface;
use \Phalcon\Annotations\Adapter;
use \Phalcon\Annotations\Reflection;
use \Phalcon\Annotations\Exception;

/**
 * Phalcon\Annotations\Adapter\Apc
 *
 * Stores the parsed annotations in APC. This adapter is suitable for production
 *
 * <code>
 * $annotations = new \Phalcon\Annotations\Adapter\Apc();
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/adapter/apc.c
 */
class Apc extends Adapter implements AdapterInterface
{

    /**
     * Reads parsed annotations from APC
     *
     * @param string $key
     * @return \Phalcon\Annotations\Reflection|null
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $value = apc_fetch(strtolower('_PHAN' . $key));
        if (isset($value) === true &&
            is_object($value) === true &&
            $value instanceof Reflection === true) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * Writes parsed annotations to APC
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

        if (apc_store(strtolower('_PHAN' . $key), $data) === false) {
            throw new Exception('Unable to store parsed annotations (APC).');
        }
    }

}
