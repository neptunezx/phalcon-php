<?php

namespace Phalcon\Http;

/**
 * Phalcon\Http\RequestInterface
 *
 * Interface for Phalcon\Http\Request
 */
interface RequestInterface
{
    /**
     * Gets a variable from the $_REQUEST superglobal applying filters if needed
     *
     * @param string name
     * @param string|array filters
     * @param mixed defaultValue
     * @return mixed
     */
    public function get($name = null, $filters = null, $defaultValue = null);

    /**
     * Gets a variable from the $_POST superglobal applying filters if needed
     *
     * @param string name
     * @param string|array filters
     * @param mixed defaultValue
     * @return mixed
     */
    public function getPost($name = null, $filters = null, $defaultValue = null);

    /**
     * Gets variable from $_GET superglobal applying filters if needed
     *
     * @param string name
     * @param string|array filters
     * @param mixed defaultValue
     * @return mixed
     */
    public function getQuery($name = null, $filters = null, $defaultValue = null);

    /**
     * Gets variable from $_SERVER superglobal
     *
     * @param string name
     * @return mixed
     */
    public function getServer($name);

    /**
     * Checks whether $_REQUEST superglobal has certain index
     * 
     * @param string $name
     * @return bool
     */
    public function has($name);

    /**
     * Checks whether $_POST superglobal has certain index
     * 
     * @param string $name
     * @return bool
     */
    public function hasPost($name);

    /**
     * Checks whether the PUT data has certain index
     * 
     * @param string $name
     * @return bool
     */
    public function hasPut($name);

    /**
     * Checks whether $_GET superglobal has certain index
     * 
     * @param string $name
     * @return boolean
     */
    public function hasQuery($name);

    /**
     * Checks whether $_SERVER superglobal has certain index
     * 
     * @param string $name
     * @return boolean
     */
    public function hasServer($name);

    /**
     * Gets HTTP header from request data
     * 
     * @param string $header
     * @return string
     */
    public function getHeader($header);

    /**
     * Gets HTTP schema (http/https)
     * 
     * @return string
     */
    public function getScheme();

    /**
     * Checks whether request has been made using ajax. Checks if $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest"
     * 
     * @return boolean
     */
    public function isAjax();

    /**
     * Checks whether request has been made using SOAP
     * 
     * @return boolean
     */
    public function isSoap();

    /**
     * Checks whether request has been made using any secure layer
     * 
     * @return boolean
     */
    public function isSecure();

    /**
     * Gets HTTP raw request body
     * 
     * @return string
     */
    public function getRawBody();

    /**
     * Gets active server address IP
     * 
     * @return string
     */
    public function getServerAddress();

    /**
     * Gets active server name
     * 
     * @return string
     */
    public function getServerName();

    /**
     * Gets host name used by the request
     * 
     * @return string
     */
    public function getHttpHost();

    /**
     * Gets information about the port on which the request is made
     * 
     * @return int
     */
    public function getPort();

    /**
     * Gets most possibly client IPv4 Address. This methods searches in
     * $_SERVER["REMOTE_ADDR"] and optionally in $_SERVER["HTTP_X_FORWARDED_FOR"]
     * 
     * @param boolean $trustForwardedHeader if trust forwarded header
     * @return string
     */
    public function getClientAddress($trustForwardedHeader = false);

    /**
     * Gets HTTP method which request has been made
     * 
     * @return string
     */
    public function getMethod();

    /**
     * Gets HTTP user agent used to made the request
     * 
     * @return string
     */
    public function getUserAgent();

    /**
     * Check if HTTP method match any of the passed methods
     *
     * @param string|array methods
     * @return boolean
     */
    public function isMethod($methods, $strict = false);

    /**
     * Checks whether HTTP method is POST. if $_SERVER["REQUEST_METHOD"] === "POST"
     * 
     * @return boolean
     */
    public function isPost();

    /**
     * Checks whether HTTP method is GET. if $_SERVER["REQUEST_METHOD"] === "GET"
     * 
     * @return boolean
     */
    public function isGet();

    /**
     * Checks whether HTTP method is PUT. if $_SERVER["REQUEST_METHOD"] === "PUT"
     * 
     * @return boolean
     */
    public function isPut();

    /**
     * Checks whether HTTP method is HEAD. if $_SERVER["REQUEST_METHOD"] === "HEAD"
     * 
     * @return boolean
     */
    public function isHead();

    /**
     * Checks whether HTTP method is DELETE. if $_SERVER["REQUEST_METHOD"] === "DELETE"
     * 
     * @return boolean
     */
    public function isDelete();

    /**
     * Checks whether HTTP method is OPTIONS. if $_SERVER["REQUEST_METHOD"] === "OPTIONS"
     * 
     * @return boolean
     */
    public function isOptions();

    /**
     * Checks whether HTTP method is PURGE (Squid and Varnish support). if $_SERVER["REQUEST_METHOD"] === "PURGE"
     * 
     * @return boolean
     */
    public function isPurge();

    /**
     * Checks whether HTTP method is TRACE. if $_SERVER["REQUEST_METHOD"] === "TRACE"
     * 
     * @return boolean
     */
    public function isTrace();

    /**
     * Checks whether HTTP method is CONNECT. if $_SERVER["REQUEST_METHOD"] === "CONNECT"
     * 
     * @return boolean
     */
    public function isConnect();

    /**
     * Checks whether request include attached files
     *
     * @param boolean onlySuccessful
     * @return boolean
     */
    public function hasFiles($onlySuccessful = false);

    /**
     * Gets attached files as Phalcon\Http\Request\FileInterface compatible instances
     * 
     * @param boolean onlySuccessful
     * @return \Phalcon\Http\Request\FileInterface[]
     */
    public function getUploadedFiles($onlySuccessful = false);

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     * 
     * @return string
     */
    public function getHTTPReferer();

    /**
     * Gets array with mime/types and their quality accepted by the browser/client from $_SERVER["HTTP_ACCEPT"]
     * 
     * @return array
     */
    public function getAcceptableContent();

    /**
     * Gets best mime/type accepted by the browser/client from $_SERVER["HTTP_ACCEPT"]
     * 
     * @return string
     */
    public function getBestAccept();

    /**
     * Gets charsets array and their quality accepted by the browser/client from $_SERVER["HTTP_ACCEPT_CHARSET"]
     * 
     * @return array
     */
    public function getClientCharsets();

    /**
     * Gets best charset accepted by the browser/client from $_SERVER["HTTP_ACCEPT_CHARSET"]
     * 
     * @return string
     */
    public function getBestCharset();

    /**
     * Gets languages array and their quality accepted by the browser/client from _SERVER["HTTP_ACCEPT_LANGUAGE"]
     * 
     * @return array
     */
    public function getLanguages();

    /**
     * Gets best language accepted by the browser/client from $_SERVER["HTTP_ACCEPT_LANGUAGE"]
     * 
     * @return string
     */
    public function getBestLanguage();

    /**
     * Gets auth info accepted by the browser/client from $_SERVER["PHP_AUTH_USER"]
     *
     * @return array
     */
    public function getBasicAuth();

    /**
     * Gets auth info accepted by the browser/client from $_SERVER["PHP_AUTH_DIGEST"]
     * 
     * @return array
     */
    public function getDigestAuth();
}
