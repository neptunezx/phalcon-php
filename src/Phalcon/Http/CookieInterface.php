<?php

namespace Phalcon\Http;

/**
 * Phalcon\Http\CookieInterface
 *
 * Interface for Phalcon\Http\Cookie
 */
interface CookieInterface
{

    /**
     * Sets the cookie's value
     *
     * @param string value
     * @return \Phalcon\Http\CookieInterface
     */
    public function setValue($value);

    /**
     * Returns the cookie's value
     *
     * @param string|array filters
     * @param string defaultValue
     * @return mixed
     */
    public function getValue($filters = null, $defaultValue = null);

    /**
     * Sends the cookie to the HTTP client
     */
    public function send();

    /**
     * Deletes the cookie
     */
    public function delete();

    /**
     * Sets if the cookie must be encrypted/decrypted automatically
     * 
     * @param boolean $useEncryption
     * @return \Phalcon\Http\CookieInterface
     */
    public function useEncryption($useEncryption);

    /**
     * Check if the cookie is using implicit encryption
     * 
     * @return boolean
     */
    public function isUsingEncryption();

    /**
     * Sets the cookie's expiration time
     * 
     * @param boolean $expire
     * @return \Phalcon\Http\CookieInterface
     */
    public function setExpiration($expire);

    /**
     * Returns the current expiration time
     * 
     * @return string
     */
    public function getExpiration();

    /**
     * Sets the cookie's expiration time
     * 
     * @param string $path
     * @return \Phalcon\Http\CookieInterface
     */
    public function setPath($path);

    /**
     * Returns the current cookie's name
     * 
     * @return string
     */
    public function getName();

    /**
     * Returns the current cookie's path
     * 
     * @return string
     */
    public function getPath();

    /**
     * Sets the domain that the cookie is available to
     * 
     * @param string $domain
     * @return \Phalcon\Http\CookieInterface
     */
    public function setDomain($domain);

    /**
     * Returns the domain that the cookie is available to
     * 
     * @return string
     */
    public function getDomain();

    /**
     * Sets if the cookie must only be sent when the connection is secure (HTTPS)
     * 
     * @param boolean $secure
     * @return \Phalcon\Http\CookieInterface
     */
    public function setSecure($secure);

    /**
     * Returns whether the cookie must only be sent when the connection is secure (HTTPS)
     * 
     * @return boolean
     */
    public function getSecure();

    /**
     * Sets if the cookie is accessible only through the HTTP protocol
     * 
     * @param boolean $httpOnly
     * @return \Phalcon\Http\CookieInterface
     */
    public function setHttpOnly($httpOnly);

    /**
     * Returns if the cookie is accessible only through the HTTP protocol
     * 
     * @return boolean
     */
    public function getHttpOnly();
}
