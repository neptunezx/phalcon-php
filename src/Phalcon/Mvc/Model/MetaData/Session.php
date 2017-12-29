<?php

namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\Exception;

/**
 * Phalcon\Mvc\Model\MetaData\Session
 *
 * Stores model meta-data in session. Data will erased when the session finishes.
 * Meta-data are permanent while the session is active.
 *
 * You can query the meta-data by printing $_SESSION['$PMM$']
 *
 * <code>
 * $metaData = new \Phalcon\Mvc\Model\Metadata\Session(
 *     [
 *        "prefix" => "my-app-id",
 *     ]
 * );
 * </code>
 */
class Session extends MetaData
{

    /**
     * Prefix
     *
     * @var string
     * @access protected
     */
    protected $_prefix = '';

    /**
     * \Phalcon\Mvc\Model\MetaData\Session constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true &&
            isset($options['prefix']) === true) {
            $this->_prefix = $options['prefix'];
        }

        $this->_metaData = array();
    }

    /**
     * Reads meta-data from $_SESSION
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

        $prefixKey = '$PMM$' . $this->_prefix;
        if (isset($_SESSION[$prefixKey]) === true &&
            isset($_SESSION[$prefixKey][$key])) {
            return $_SESSION[$prefixKey][$key];
        }
    }

    /**
     * Writes the meta-data to $_SESSION
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

        $prefixKey = '$PMM$' . $this->_prefix;

        if (!isset($_SESSION[$prefixKey])) {
            $_SESSION[$prefixKey] = [];
        }

        $_SESSION[$prefixKey][$key] = $data;
    }

}
