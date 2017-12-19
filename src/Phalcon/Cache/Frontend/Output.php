<?php

namespace Phalcon\Cache\Frontend;

use \Phalcon\Cache\FrontendInterface;
use \Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Frontend\Output
 *
 * Allows to cache output fragments captured with ob_* functions
 *
 * <code>
 * use Phalcon\Tag;
 * use Phalcon\Cache\Backend\File;
 * use Phalcon\Cache\Frontend\Output;
 *
 * // Create an Output frontend. Cache the files for 2 days
 * $frontCache = new Output(
 *     [
 *         "lifetime" => 172800,
 *     ]
 * );
 *
 * // Create the component that will cache from the "Output" to a "File" backend
 * // Set the cache file directory - it's important to keep the "/" at the end of
 * // the value for the folder
 * $cache = new File(
 *     $frontCache,
 *     [
 *         "cacheDir" => "../app/cache/",
 *     ]
 * );
 *
 * // Get/Set the cache file to ../app/cache/my-cache.html
 * $content = $cache->start("my-cache.html");
 *
 * // If $content is null then the content will be generated for the cache
 * if (null === $content) {
 *     // Print date and time
 *     echo date("r");
 *
 *     // Generate a link to the sign-up action
 *     echo Tag::linkTo(
 *         [
 *             "user/signup",
 *             "Sign Up",
 *             "class" => "signup-button",
 *         ]
 *     );
 *
 *     // Store the output into the cache file
 *     $cache->save();
 * } else {
 *     // Echo the cached output
 *     echo $content;
 * }
 * </code>
 */
class Output implements FrontendInterface
{

    /**
     * Buffering
     *
     * @var boolean
     * @access protected
     */
    protected $_buffering = false;

    /**
     * Frontend Options
     *
     * @var array|null
     * @access protected
     */
    protected $_frontendOptions;

    /**
     * \Phalcon\Cache\Frontend\Output constructor
     *
     * @param array|null $frontendOptions
     * @throws Exception
     */
    public function __construct($frontendOptions = null)
    {
        if (is_array($frontendOptions) === false &&
            is_null($frontendOptions) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_frontendOptions = $frontendOptions;
    }

    /**
     * Returns cache lifetime
     *
     * @return integer
     */
    public function getLifetime()
    {
        if (is_array($this->_frontendOptions) === true &&
            isset($this->_frontendOptions['lifetime']) === true) {
            return $this->_frontendOptions['lifetime'];
        }

        return 1;
    }

    /**
     * Check whether if frontend is buffering output
     *
     * @return boolean
     */
    public function isBuffering()
    {
        return $this->_buffering;
    }

    /**
     * Starts output frontend
     */
    public function start()
    {
        $this->_buffering = true;
        ob_start();
    }

    /**
     * Returns output cached content
     *
     * @return string|null
     */
    public function getContent()
    {
        if ($this->_buffering === true) {
            return ob_get_contents();
        }

        return null;
    }

    /**
     * Stops output frontend
     */
    public function stop()
    {
        if ($this->_buffering === true) {
            ob_end_clean();
        }

        $this->_buffering = false;
    }

    /**
     * Prepare data to be stored
     *
     * @param mixed $data
     * @return mixed
     */
    public function beforeStore($data)
    {
        return $data;
    }

    /**
     * Prepares data to be retrieved to user
     *
     * @param mixed $data
     * @return mixed
     */
    public function afterRetrieve($data)
    {
        return $data;
    }

}
