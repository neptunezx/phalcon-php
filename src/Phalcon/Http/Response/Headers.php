<?php

/**
 * Headers
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Http\Response;

use \Phalcon\Http\Response\HeadersInterface;
use \Phalcon\Http\Response\Exception;
use \Phalcon\Text;

/**
 * Phalcon\Http\Response\Headers
 *
 * This class is a bag to manage the response headers
 */
class Headers implements HeadersInterface
{

    /**
     * Headers
     *
     * @var null|array
     * @access protected
     */
    protected $_headers = [];

    /**
     * Sets a header to be sent at the end of the request
     *
     * @param string $name
     * @param string $value
     * @throws Exception
     */
    public function set($name, $value)
    {
        $this->_headers[$name] = (string) $value;
    }

    /**
     * Gets a header value from the internal bag
     *
     * @param string $name
     * @return string|boolean
     * @throws Exception
     */
    public function get($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_headers[$name]) === true) {
            return $this->_headers[$name];
        }

        return false;
    }

    /**
     * Sets a raw header to be sent at the end of the request
     *
     * @param string $header
     * @throws Exception
     */
    public function setRaw($header)
    {
        $header = (string) $header;

        if (is_array($this->_headers) === false) {
            $this->_headers = array();
        }

        $this->_headers[$header] = null;
    }

    /**
     * Removes a header to be sent at the end of the request
     */
    public function remove($header)
    {
        unset($this->_headers[$header]);
    }

    /**
     * Sends the headers to the client
     *
     * @return boolean
     */
    public function send()
    {
        if (headers_sent() === false) {
            foreach ($this->_headers as $header => $value) {
                if (empty($value) === false) {
                    //Default header
                    header($header . ': ' . $value, true);
                } else {
                    if (Text::memstr($header, ":") || substr($header, 0, 5) == "HTTP/") {
                        header($header, true);
                    } else {
                        header($header . ": ", true);
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Reset set headers
     */
    public function reset()
    {
        $this->_headers = [];
    }

    /**
     * Returns the current headers as an array
     */
    public function toArray()
    {
        return $this->_headers;
    }

    /**
     * Restore a \Phalcon\Http\Response\Headers object
     *
     * @param array $data
     * @return \Phalcon\Http\Response\Headers
     * @throws Exception
     */
    public static function __set_state($data)
    {
        if (is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = new self();
        if (isset($data['_headers']) === true &&
            is_array($data['_headers']) === true) {
            foreach ($data['_headers'] as $key => $value) {
                //@note this doesn't work for raw headers!
                $headers->set($key, $value);
            }
        }

        return $headers;
    }

}
