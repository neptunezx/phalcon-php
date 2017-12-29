<?php

namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Exception;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Text;

/**
 * Phalcon\Logger\Adapter\File
 *
 * Adapter to store logs in plain text files
 *
 * <code>
 * $logger = new \Phalcon\Logger\Adapter\File("app/logs/test.log");
 *
 * $logger->log("This is a message");
 * $logger->log(\Phalcon\Logger::ERROR, "This is an error");
 * $logger->error("This is another error");
 *
 * $logger->close();
 * </code>
 */
class File extends Adapter
{

    /**
     * File Handler
     *
     * @var null|resource
     * @access protected
     */
    protected $_fileHandler;

    /**
     * Path
     *
     * @var string
     * @access protected
     */
    protected $_path;

    /**
     * Options
     *
     * @var null|array
     * @access protected
     */
    protected $_options;

    /**
     * \Phalcon\Logger\Adapter\File constructor
     *
     * @param string $name
     * @param array|null $options
     * @throws Exception
     */
    public function __construct($name, $options = null)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($options) === true) {
            $options = array();
        } elseif (is_array($options) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($options['mode']) === true) {
            $mode = $options['mode'];
            if (strpos($options['mode'], 'r') === true) {
                throw new Exception('Logger must be opened in append or write mode');
            }
        } else {
            $mode = 'ab';
        }

        //We use 'fopen' to respect the open-basedir directive
        $handler = fopen($name, $mode);
        if ($handler === false) {
            throw new Exception("Can't open lo gfile at '" . $name . "'");
        }

        $this->_path        = $name;
        $this->_options     = $options;
        $this->_fileHandler = $handler;
    }

    /**
     * Returns the internal formatter
     *
     * @return \Phalcon\Logger\Formatter\Line
     */
    public function getFormatter()
    {
        if (is_object($this->_formatter) === false) {
            $this->_formatter = new LineFormatter();
        }

        return $this->_formatter;
    }

    /**
     * Writes the log to the file itself
     *
     * @param string $message
     * @param int $type
     * @param int $time
     * @throws Exception
     */
    public function logInternal($message, $type, $time, array $context = null)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($time) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_fileHandler) === false) {
            throw new Exception('Cannot send message to the log because it is invalid');
        }

        $formatter = $this->getFormatter();
        fwrite($this->_fileHandler, $formatter->format($message, $type, $time, $context));
    }

    /**
     * Closes the logger
     *
     * @return boolean
     */
    public function close()
    {
        return fclose($this->_fileHandler);
    }

    /**
     * Opens the internal file handler after unserialization
     *
     * @throws Exception
     */
    public function __wakeup()
    {
        if (is_string($this->_path) === false) {
            throw new Exception("Invalid data passed to Phalcon\\Logger\\Adapter\\File::__wakeup()");
        }

        if (isset($$this->_options['mode']) === true) {
            $mode = $$this->_options['mode'];
            if (is_string($mode) === false) {
                throw new Exception("Invalid data passed to Phalcon\\Logger\\Adapter\\File::__wakeup()");
            }
        } else {
            $mode = 'ab';
        }

        if (Text::memstr($mode, "r")) {
            throw new Exception("Logger must be opened in append or write mode");
        }

        //Re-open the file handler if the logger was serialized
        $this->_fileHandler = fopen($this->_path, $mode);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

}
