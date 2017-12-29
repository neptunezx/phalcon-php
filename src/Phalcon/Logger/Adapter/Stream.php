<?php

namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Exception;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Text;

/**
 * Phalcon\Logger\Adapter\Stream
 *
 * Sends logs to a valid PHP stream
 *
 * <code>
 * use Phalcon\Logger;
 * use Phalcon\Logger\Adapter\Stream;
 *
 * $logger = new Stream("php://stderr");
 *
 * $logger->log("This is a message");
 * $logger->log(Logger::ERROR, "This is an error");
 * $logger->error("This is another error");
 * </code>
 */
class Stream extends Adapter
{

    /**
     * Stream
     *
     * @var null|resource
     * @access protected
     */
    protected $_stream;

    /**
     * \Phalcon\Logger\Adapter\Stream constructor
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

        if (isset($options["mode"]) === true) {
            $mode = $options["mode"];
            if (Text::memstr($mode, "r")) {
                throw new Exception('Stream must be opened in append or write mode');
            }
        } else {
            $mode = "ab";
        }

        //We use 'fopen' to respect the open-basedir directive
        $stream = fopen($name, $mode);
        if (is_resource($stream) === false) {
            throw new Exception("Can't open stream '" . $name . "'");
        }

        $this->_stream = $stream;
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
     * Writes the log to the stream itself
     *
     * @param string $message
     * @param int $type
     * @param int $time
     * @param array $context
     * @throws Exception
     */
    public function logInternal($message, $type, $time, array $context = null)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($time) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_resource($this->_stream) === false) {
            throw new Exception('Cannot send message to the log because it is invalid');
        }

        //@note no return value handeling
        fwrite($this->_stream, $this->getFormatter()->format($message, $type, $time));
    }

    /**
     * Closes the logger
     *
     * @return boolean
     */
    public function close()
    {
        return fclose($this->_stream);
    }

}
