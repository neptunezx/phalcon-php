<?php

namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\MetaData\Apc
 *
 * Stores model meta-data in the APC cache. Data will erased if the web server is restarted
 *
 * By default meta-data is stored for 48 hours (172800 seconds)
 *
 * You can query the meta-data by printing apc_fetch('$PMM$') or apc_fetch('$PMM$my-app-id')
 *
 * <code>
 * $metaData = new \Phalcon\Mvc\Model\Metadata\Apc(
 *     [
 *         "prefix"   => "my-app-id",
 *         "lifetime" => 86400,
 *     ]
 * );
 * </code>
 */
class Apc extends MetaData
{

    protected $_prefix   = "";
    protected $_ttl      = 172800;
    protected $_metaData = [];

    /**
     * \Phalcon\Mvc\Model\MetaData\Apc constructor
     *
     * @param array|null $options
     * @throws Exception
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true) {
            if (isset($options['prefix']) === true) {
                $this->_prefix = $options['prefix'];
            }

            if (isset($options['lifetime']) === true) {
                $this->_ttl = $options['lifetime'];
            }
        }

        $this->_metaData = array();
    }

    /**
     * Reads meta-data from APC
     *
     * @param string $key
     * @return array|null
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $data = apc_fetch('$PMM$' . $this->_prefix . $key);

        if (is_array($data) === true) {
            return $data;
        }

        return null;
    }

    /**
     * Writes the meta-data to APC
     *
     * @param string $key
     * @param mixed $data
     * @return void
     */
    public function write($key, $data)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }

        apc_store('$PMM$' . $this->_prefix . $key, $data, $this->_ttl);
    }

}
