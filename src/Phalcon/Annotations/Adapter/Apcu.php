<?php

namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Exception;
use Phalcon\Annotations\Reflection;

/**
 * Phalcon\Annotations\Adapter\Apcu
 *
 * Stores the parsed annotations in APCu. This adapter is suitable for production
 *
 *<code>
 * use Phalcon\Annotations\Adapter\Apcu;
 *
 * $annotations = new Apcu();
 *</code>
 */
class Apcu extends Adapter
{

    protected $_prefix = "";

    protected $_ttl = 172800;
    /**
     * Phalcon\Annotations\Adapter\Apc constructor
     *
     * @param array $options
     */
    public function __construct($options = null)
    {

        if (is_array($options)) {
            if (isset($options['prefix'])) {
                $prefix = $options['prefix'];
                $this->_prefix = $prefix;
            }
            if (isset($options['lifetime'])) {
                $ttl = $options['lifetime'];
                $this->_ttl = $ttl;
            }
        }
    }

    /**
     * Reads parsed annotations from APC
     * @param String $key
     * @return Reflection| boolean
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }
        return apcu_fetch(strtolower("_PHAN" . $this->_prefix . $key));
    }

    /**
     * Writes parsed annotations to APC
     * @param string $key
     * @param Reflection $data
     * @return bool|array Returns TRUE on success or FALSE on failure | array with error keys
     * @throws Exception
     */
    public function write($key, $data)//(string!key, <Reflection > data)
    {
        if (is_string($key) === false ||
            is_object($data) === false ||
            $data instanceof Reflection === false) {
            throw new Exception('Invalid parameter type.');
        }
        return apcu_store(strtolower("_PHAN" . $this->_prefix . $key), $data, $this->_ttl);
    }
}
