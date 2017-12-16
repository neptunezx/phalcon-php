<?php

/**
 * Response
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Http;

use \Phalcon\Http\ResponseInterface;
use \Phalcon\Http\Response\Exception;
use \Phalcon\Http\Response\HeadersInterface;
use \Phalcon\Http\Response\Headers;
use \Phalcon\Http\Response\CookiesInterface;
use \Phalcon\Mvc\UrlInterface;
use \Phalcon\Di\InjectionAwareInterface;
use \Phalcon\DiInterface;
use \Phalcon\Di;
use \DateTime;
use \DateTimeZone;

/**
 * Phalcon\Http\Response
 *
 * Part of the HTTP cycle is return responses to the clients.
 * Phalcon\HTTP\Response is the Phalcon component responsible to achieve this task.
 * HTTP responses are usually composed by headers and body.
 *
 * <code>
 * $response = new \Phalcon\Http\Response();
 *
 * $response->setStatusCode(200, "OK");
 * $response->setContent("<html><body>Hello</body></html>");
 *
 * $response->send();
 * </code>
 */
class Response implements ResponseInterface, InjectionAwareInterface
{

    /**
     * Sent
     *
     * @var boolean
     * @access protected
     */
    protected $_sent = false;

    /**
     * Content
     *
     * @var null|string
     * @access protected
     */
    protected $_content;

    /**
     * Headers
     *
     * @var null|\Phalcon\Http\Response\HeadersInterface
     * @access protected
     */
    protected $_headers;

    /**
     * Cookies
     *
     * @var null|\Phalcon\Ḩttp\Response\CookiesInterface
     * @access protected
     */
    protected $_cookies;

    /**
     * File
     *
     * @var null|string
     * @access protected
     */
    protected $_file;

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
     */
    protected $_dependencyInjector;

    /**
     * \Phalcon\Http\Response constructor
     *
     * @param string|null $content
     * @param int|null $code
     * @param string|null $status
     * @throws Exception
     */
    public function __construct($content = null, $code = null, $status = null)
    {
        if (is_string($content) === true) {
            $this->_content = $content;
        } elseif (is_null($content) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_int($code) === true && is_string($status) === true) {
            $this->setStatusCode($code, $status);
        } elseif (is_null($code) === false || is_null($status) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * A Phalcon\Http\Response\Headers bag is temporary used to manage the headers before sent them to the client
         */
        $this->_headers = new Headers();
    }

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        if (is_object($this->_dependencyInjector) === false) {
            $dependencyInjector = DI::getDefault();
            if (is_object($dependencyInjector) === false) {
                //@note potentially misleading exception
                throw new Exception("A dependency injection object is required to access the 'url' service");
            }

            $this->_dependencyInjector = $dependencyInjector;
        }

        return $this->_dependencyInjector;
    }

    /**
     * Sets the HTTP response code
     *
     * <code>
     *  $response->setStatusCode(404, "Not Found");
     * </code>
     *
     * @param int $code
     * @param string $message
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setStatusCode($code, $message = '')
    {
        if ($message == null) {
            $message = '';
        }
        if (is_int($code) === false ||
            is_string($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $headers           = $this->getHeaders();
        $currentHeadersRaw = $headers->toArray();

        /**
         * We use HTTP/1.1 instead of HTTP/1.0
         *
         * Before that we would like to unset any existing HTTP/x.y headers
         */
        if (is_array($currentHeadersRaw)) {
            foreach ($currentHeadersRaw as $key => $_) {
                if (is_string($key) && strstr($key, "HTTP/")) {
                    $headers->remove($key);
                }
            }
        }

        // if an empty message is given we try and grab the default for this
        // status code. If a default doesn't exist, stop here.
        if ($message == '') {
            // See: http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
            $statusCodes = [
                // INFORMATIONAL CODES
                100 => "Continue", // RFC 7231, 6.2.1
                101 => "Switching Protocols", // RFC 7231, 6.2.2
                102 => "Processing", // RFC 2518, 10.1
                // SUCCESS CODES
                200 => "OK", // RFC 7231, 6.3.1
                201 => "Created", // RFC 7231, 6.3.2
                202 => "Accepted", // RFC 7231, 6.3.3
                203 => "Non-Authoritative Information", // RFC 7231, 6.3.4
                204 => "No Content", // RFC 7231, 6.3.5
                205 => "Reset Content", // RFC 7231, 6.3.6
                206 => "Partial Content", // RFC 7233, 4.1
                207 => "Multi-status", // RFC 4918, 11.1
                208 => "Already Reported", // RFC 5842, 7.1
                226 => "IM Used", // RFC 3229, 10.4.1
                // REDIRECTION CODES
                300 => "Multiple Choices", // RFC 7231, 6.4.1
                301 => "Moved Permanently", // RFC 7231, 6.4.2
                302 => "Found", // RFC 7231, 6.4.3
                303 => "See Other", // RFC 7231, 6.4.4
                304 => "Not Modified", // RFC 7232, 4.1
                305 => "Use Proxy", // RFC 7231, 6.4.5
                306 => "Switch Proxy", // RFC 7231, 6.4.6 (Deprecated)
                307 => "Temporary Redirect", // RFC 7231, 6.4.7
                308 => "Permanent Redirect", // RFC 7538, 3
                // CLIENT ERROR
                400 => "Bad Request", // RFC 7231, 6.5.1
                401 => "Unauthorized", // RFC 7235, 3.1
                402 => "Payment Required", // RFC 7231, 6.5.2
                403 => "Forbidden", // RFC 7231, 6.5.3
                404 => "Not Found", // RFC 7231, 6.5.4
                405 => "Method Not Allowed", // RFC 7231, 6.5.5
                406 => "Not Acceptable", // RFC 7231, 6.5.6
                407 => "Proxy Authentication Required", // RFC 7235, 3.2
                408 => "Request Time-out", // RFC 7231, 6.5.7
                409 => "Conflict", // RFC 7231, 6.5.8
                410 => "Gone", // RFC 7231, 6.5.9
                411 => "Length Required", // RFC 7231, 6.5.10
                412 => "Precondition Failed", // RFC 7232, 4.2
                413 => "Request Entity Too Large", // RFC 7231, 6.5.11
                414 => "Request-URI Too Large", // RFC 7231, 6.5.12
                415 => "Unsupported Media Type", // RFC 7231, 6.5.13
                416 => "Requested range not satisfiable", // RFC 7233, 4.4
                417 => "Expectation Failed", // RFC 7231, 6.5.14
                418 => "I'm a teapot", // RFC 7168, 2.3.3
                421 => "Misdirected Request",
                422 => "Unprocessable Entity", // RFC 4918, 11.2
                423 => "Locked", // RFC 4918, 11.3
                424 => "Failed Dependency", // RFC 4918, 11.4
                425 => "Unordered Collection",
                426 => "Upgrade Required", // RFC 7231, 6.5.15
                428 => "Precondition Required", // RFC 6585, 3
                429 => "Too Many Requests", // RFC 6585, 4
                431 => "Request Header Fields Too Large", // RFC 6585, 5
                451 => "Unavailable For Legal Reasons", // RFC 7725, 3
                499 => "Client Closed Request",
                // SERVER ERROR
                500 => "Internal Server Error", // RFC 7231, 6.6.1
                501 => "Not Implemented", // RFC 7231, 6.6.2
                502 => "Bad Gateway", // RFC 7231, 6.6.3
                503 => "Service Unavailable", // RFC 7231, 6.6.4
                504 => "Gateway Time-out", // RFC 7231, 6.6.5
                505 => "HTTP Version not supported", // RFC 7231, 6.6.6
                506 => "Variant Also Negotiates", // RFC 2295, 8.1
                507 => "Insufficient Storage", // RFC 4918, 11.5
                508 => "Loop Detected", // RFC 5842, 7.2
                510 => "Not Extended", // RFC 2774, 7
                511 => "Network Authentication Required"  // RFC 6585, 6
            ];

            if (!isset($statusCodes[$code])) {
                throw new Exception("Non-standard statuscode given without a message");
            }

            $defaultMessage = $statusCodes[$code];
            $message        = $defaultMessage;
        }

        $headers->setRaw("HTTP/1.1 " . $code . " " . $message);

        /**
         * We also define a 'Status' header with the HTTP status
         */
        $headers->set("Status", $code . " " . $message);

        return $this;
    }

    /**
     * Sets a headers bag for the response externally
     *
     * @param \Phalcon\Http\Response\HeadersInterface $headers
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setHeaders($headers)
    {
        if (is_object($headers) === false ||
            $headers instanceof HeadersInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_headers = $headers;

        return $this;
    }

    /**
     * Returns headers set by the user
     *
     * @return \Phalcon\Http\Response\HeadersInterface
     */
    public function getHeaders()
    {
        if (is_null($this->_headers) === true) {
            /*
             * A Phalcon\Http\Response\Headers bag is temporary used to manage the headers
             * before sent them to the client
             */
            $headers        = new Headers();
            $this->_headers = $headers;
        }

        return $this->_headers;
    }

    /**
     * Sets a cookies bag for the response externally
     *
     * @param \Phalcon\Http\Response\CookiesInterface $cookies
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setCookies($cookies)
    {
        if (is_object($cookies) === false ||
            $cookies instanceof CookiesInterface === false) {
            throw new Exception('The cookies bag is not valid');
        }

        $this->_cookies = $cookies;

        return $this;
    }

    /**
     * Returns coookies set by the user
     *
     * @return \Phalcon\Http\Response\CookiesInterface|null
     */
    public function getCookies()
    {
        return $this->_cookies;
    }

    /**
     * Overwrites a header in the response
     *
     * <code>
     *  $response->setHeader("Content-Type", "text/plain");
     * </code>
     *
     * @param string $name
     * @param string $value
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setHeader($name, $value)
    {
        if (is_string($name) === false ||
            is_string($value) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->getHeaders()->set($name, $value);

        return $this;
    }

    /**
     * Send a raw header to the response
     *
     * <code>
     *  $response->setRawHeader("HTTP/1.1 404 Not Found");
     * </code>
     *
     * @param string $header
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setRawHeader($header)
    {
        if (is_string($header) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->getHeaders()->setRaw($header);

        return $this;
    }

    /**
     * Resets all the stablished headers
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function resetHeaders()
    {
        $this->getHeaders()->reset();

        return $this;
    }

    /**
     * Sets a Expires header to use HTTP cache
     *
     * <code>
     *  $this->response->setExpires(new DateTime());
     * </code>
     *
     * @param DateTime $datetime
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setExpires($datetime)
    {
        if (is_object($datetime) === false ||
            $datetime instanceof DateTime === false) {
            throw new Exception('datetime parameter must be an instance of DateTime');
        }

        $headers = $this->getHeaders();
        try {
            $date = clone $datetime;
        } catch (\Exception $e) {
            return;
        }

        //All the expiration times are sent in UTC
        $timezone = new DateTimeZone('UTC');

        //Change the timezone to UTC
        $date->setTimezone($timezone);
        $utcDate = $date->format('D, d M Y H:i:s') . ' GMT';

        //The 'Expires' header set this info
        $this->setHeader('Expires', $utcDate);

        return $this;
    }

    /**
     * Sets Last-Modified header
     *
     * <code>
     * $this->response->setLastModified(
     *     new DateTime()
     * );
     * </code>
     * 
     * @param DateTime $datetime 
     * @return Response
     * @note php7.* features
     */
    public function setLastModified($datetime)
    {

        $date = clone $datetime;

        /**
         * All the Last-Modified times are sent in UTC
         * Change the timezone to utc
         */
        $date->setTimezone(new \DateTimeZone("UTC"));

        /**
         * The 'Last-Modified' header sets this info
         */
        $this->setHeader("Last-Modified", $date->format("D, d M Y H:i:s") . " GMT");
        return $this;
    }

    /**
     * Sets Cache headers to use HTTP cache
     *
     * <code>
     * $this->response->setCache(60);
     * </code>
     * 
     * @param int $minutes 
     * @return Response
     * @note php7.* features
     */
    public function setCache($minutes)
    {
        $date = new \DateTime();
        $date->modify("+" . $minutes . " minutes");

        $this->setExpires($date);
        $this->setHeader("Cache-Control", "max-age=" . ($minutes * 60));

        return $this;
    }

    /**
     * Sends a Not-Modified response
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function setNotModified()
    {
        $this->setStatusCode(304, 'Not modified');

        return $this;
    }

    /**
     * Sets the response content-type mime, optionally the charset
     *
     * <code>
     *  $response->setContentType('application/pdf');
     *  $response->setContentType('text/plain', 'UTF-8');
     * </code>
     *
     * @param string $contentType
     * @param string|null $charset
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setContentType($contentType, $charset = null)
    {
        if (is_string($contentType) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = $this->getHeaders();

        if (is_null($charset) === true) {
            $headers->set('Content-Type', $contentType);
        } elseif (is_string($charset) === true) {
            $headers->set('Content-Type', $contentType . '; charset=' . $charset);
        } else {
            throw new Exception('Invalid parameter type.');
        }

        return $this;
    }

    /**
     * Sets the response content-length
     *
     * <code>
     * $response->setContentLength(2048);
     * </code>
     * 
     * @param int $contentLength
     * @return \Phalcon\Http\ResponseInterface
     */
    public function setContentLength($contentLength)
    {
        $this->setHeader("Content-Length", $contentLength);

        return $this;
    }

    /**
     * Set a custom ETag
     *
     * <code>
     *  $response->setEtag(md5(time()));
     * </code>
     *
     * @param string $etag
     * @throws Exception
     */
    public function setEtag($etag)
    {
        if (is_string($etag) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->getHeaders()->set('Etag', $etag);

        return $this;
    }

    /**
     * Redirect by HTTP to another action or URL
     *
     * <code>
     *  //Using a string redirect (internal/external)
     *  $response->redirect("posts/index");
     *  $response->redirect("http://en.wikipedia.org", true);
     *  $response->redirect("http://www.example.com/new-location", true, 301);
     *
     *  //Making a redirection based on a named route
     *  $response->redirect(array(
     *      "for" => "index-lang",
     *      "lang" => "jp",
     *      "controller" => "index"
     *  ));
     * </code>
     *
     * @param string|null $location
     * @param boolean|null $externalRedirect
     * @param int|null $statusCode
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function redirect($location = null, $externalRedirect = false, $statusCode = 302)
    {
        /* Type check */
        if (is_string($location) === false &&
            is_null($location) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($externalRedirect) === true) {
            $externalRedirect = false;
        } elseif (is_bool($externalRedirect) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($statusCode) === true) {
            $statusCode = 302;
        } elseif (is_int($statusCode) === false) {
            $statusCode = (int) $statusCode;
        }

        if (!$location) {
            $location = "";
        }

        if ($externalRedirect) {
            $header = $location;
        } else {
            if (is_string($location) && strstr($location, "://")) {
                $matched = preg_match("/^[^:\\/?#]++:/", $location);
                if ($matched) {
                    $header = $location;
                } else {
                    $header = null;
                }
            } else {
                $header = null;
            }
        }

        $dependencyInjector = $this->getDI();

        if (!$header) {
            $url = $dependencyInjector->getShared("url");
            if ($url instanceof UrlInterface) {
                $header = $url->get($location);
            }
        }

        if ($dependencyInjector->has("view")) {
            $view = $dependencyInjector->getShared("view");

            /**
             * 当前系统可能没有使用View组件
             */
            //if ($view instanceof ViewInterface) {
            if (method_exists($view, 'disable')) {
                $view->disable();
            }
        }

        /**
         * The HTTP status is 302 by default, a temporary redirection
         */
        if ($statusCode < 300 || $statusCode > 308) {
            $statusCode = 302;
        }

        $this->setStatusCode($statusCode);

        /**
         * Change the current location using 'Location'
         */
        $this->setHeader("Location", $header);

        return $this;
    }

    /**
     * Sets HTTP response body
     *
     * <code>
     *  $response->setContent("<h1>Hello!</h1>");
     * </code>
     *
     * @param string $content
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function setContent($content)
    {
        if (is_string($content) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_content = $content;

        return $this;
    }

    /**
     * Sets HTTP response body. The parameter is automatically converted to JSON
     *
     * <code>
     *  $response->setJsonContent(array("status" => "OK"));
     * </code>
     *
     * @param mixed $content
     * @param int $jsonOptions
     * @return \Phalcon\Http\ResponseInterface
     */
    public function setJsonContent($content, $jsonOptions = 0, $depth = 512)
    {
        if (is_null($jsonOptions) === false) {
            $options = (int) $jsonOptions;
        } else {
            $options = 0;
        }

        $this->setContentType("application/json", "UTF-8");
        $this->setContent(json_encode($content, $jsonOptions, $depth));
        return $this;
    }

    /**
     * Appends a string to the HTTP response body
     *
     * @param string $content
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function appendContent($content)
    {
        if (is_string($content) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($this->_content) === false) {
            $this->_content .= $content;
        } else {
            $this->_content = $content;
        }

        return $this;
    }

    /**
     * Gets the HTTP response body
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Check if the response is already sent
     *
     * @return boolean
     */
    public function isSent()
    {
        return $this->_sent;
    }

    /**
     * Sends headers to the client
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function sendHeaders()
    {
        if (is_object($this->_headers) === true) {
            $this->_headers->send();
        }

        return $this;
    }

    /**
     * Sends cookies to the client
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function sendCookies()
    {
        if (is_object($this->_cookies) === true) {
            $this->_cookies->send();
        }

        return $this;
    }

    /**
     * Prints out HTTP response to the client
     *
     * @return \Phalcon\Http\ResponseInterface
     * @throws Exception
     */
    public function send()
    {
        if ($this->_sent) {
            throw new Exception("Response was already sent");
        }

        $this->sendHeaders();

        $this->sendCookies();

        /**
         * Output the response body
         */
        $content = $this->_content;
        if ($content != null) {
            echo $content;
        } else {
            $file = $this->_file;

            if (is_string($file) && strlen($file)) {
                readfile($file);
            }
        }

        $this->_sent = true;
        return $this;
    }

    /**
     * Sets an attached file to be sent at the end of the request
     *
     * @param string $filePath
     * @param string|null $attachmentName
     * @param boolean|null $attachment
     * @throws Excepiton
     */
    public function setFileToSend($filePath, $attachmentName = null, $attachment = true)
    {
        /* Type check */
        if (is_string($filePath) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($attachment) === true) {
            $attachment = true;
        } elseif (is_bool($attachment) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($attachmentName) === false) {
            $basePath = basename($filePath);
        } else {
            $basePath = $attachmentName;
        }

        /* Execute */
        if ($attachment === true) {
            $this->setRawHeader("Content-Description: File Transfer");
            $this->setRawHeader("Content-Type: application/octet-stream");
            $this->setRawHeader("Content-Disposition: attachment; filename=" . $basePath);
            $this->setRawHeader("Content-Transfer-Encoding: binary");
        }

        //@note no check if path is valid
        $this->_file = $filePath;

        return $this;
    }

}
