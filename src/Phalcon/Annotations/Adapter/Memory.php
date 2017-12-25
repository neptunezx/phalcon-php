<?php


namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Exception;
use Phalcon\Annotations\Reflection;

/**
 * Phalcon\Annotations\Adapter\Memory
 *
 * Stores the parsed annotations in memory. This adapter is the suitable development/testing
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/annotations/adapter/memory.c
 */
class Memory extends Adapter
{

    /**
     * Data
     * @var mixed
     */
    protected $_data;

    /**
     * Reads parsed annotations from memory
     * @param string $key
     * @return Reflection | boolean
     * @throws Exception
     */

    public function read($key)
    {
        if (is_string($key)===false) {
            throw new Exception('Invalid parameter type.');
        }
        if (isset($this->_data[strtolower($key)])) {
            return $this->_data[strtolower($key)];
        } else {
            return false;
        }
    }

    /**
     * Writes parsed annotations to memory
     * @param string $key
     * @param Reflection $data
     * @throws Exception
     */
    public function write($key, $data)
    {
        if (is_string($key) === false ||
            is_object($data) === false ||
            $data instanceof Reflection === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_data[strtolower($key)] = $data;
    }

}
