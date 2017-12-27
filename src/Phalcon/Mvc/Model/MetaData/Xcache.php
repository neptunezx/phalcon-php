<?php

namespace Phalcon\Mvc\Model\MetaData;

use \Phalcon\Mvc\Model\MetaData;

/**
 * Phalcon\Mvc\Model\MetaData\Xcache
 *
 * Stores model meta-data in the XCache cache. Data will erased if the web server is restarted
 *
 * By default meta-data is stored for 48 hours (172800 seconds)
 *
 * You can query the meta-data by printing xcache_get('$PMM$') or xcache_get('$PMM$my-app-id')
 *
 * <code>
 * $metaData = new Phalcon\Mvc\Model\Metadata\Xcache(
 *     [
 *         "prefix"   => "my-app-id",
 *         "lifetime" => 86400,
 *     ]
 * );
 * </code>
 */
class Xcache extends MetaData
{

    /**
     * Prefix
     *
     * @var string
     * @access protected
     */
    protected $_prefix = '';

    /**
     * Lifetime
     *
     * @var int
     * @access protected
     */
    protected $_ttl;
    protected $_metaData = [];

    /**
     * \Phalcon\Mvc\Model\MetaData\Xcache constructor
     *
     * @param array|null $options
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
    }

    /**
     * Reads metadata from XCache
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

        $data = xcache_get('$PMM$' . $this->_prefix . $key);
        if (is_array($data) === true) {
            return $data;
        }

        return null;
    }

    /**
     *  Writes the metadata to XCache
     *
     * @param string $key
     * @param array $data
     * @throws Exception
     */
    public function write($key, $data)
    {
        if (is_string($key) === false ||
            is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        xcache_set('$PMM$' . $this->_prefix . $key, $data, $this->_ttl);
    }

}
