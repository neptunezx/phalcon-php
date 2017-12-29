<?php

namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Exception;
use Phalcon\Annotations\Reflection;

/**
 * Phalcon\Annotations\Adapter\Apc
 *
 * Stores the parsed annotations in APC. This adapter is suitable for production
 *
 * <code>
 * use Phalcon\Annotations\Adapter\Apc;
 *
 * $annotations = new Apc();
 * </code>
 *
 * @see \Phalcon\Annotations\Adapter\Apcu
 * @deprecated
 */
class Apc extends Adapter
{
    protected $_prefix = "";

    protected $_ttl = 172800;

    /**
     * Phalcon\Annotations\Adapter\Apc constructor
     *
     * @param  $options |null
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (isset($options['prefix'])) {
                $this->_prefix = $options['prefix'];
            }
            if (isset($options['lifetime'])) {
                $this->_ttl = $options['lifetime'];
            }
        }
    }

    /**
     * Reads parsed annotations from APC
     *
     * @param string $key
     * @return Reflection| boolean
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return apc_fetch(strtolower('_PHAN' . $this->_prefix . $key));

    }

    /**
     * Writes parsed annotations to APC
     *
     * @param string $key
     * @param Reflection $data
     * @throws Exception
     * @return  bool|array Returns TRUE on success or FALSE on failure | array with error keys.
     */

    public function write($key, $data)
    {
        if (is_string($key) === false ||
            is_object($data) === false ||
            $data instanceof Reflection === false) {
            throw new Exception('Invalid parameter type.');
        }

        return apc_store(strtolower('_PHAN' . $this->_prefix . $key), $data, $this->_ttl);
    }

}
