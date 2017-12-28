<?php

namespace Phalcon\Logger\Formatter;

use \Phalcon\Logger\Formatter;
use \Phalcon\Logger\FormatterInterface;
use \Phalcon\Logger\Exception;
use Phalcon\Text;

/**
 * Phalcon\Logger\Formatter\Line
 *
 * Formats messages using an one-line string
 *
 */
class Line extends Formatter
{

    /**
     * Date Format
     *
     * @var string
     * @access protected
     */
    protected $_dateFormat = 'D, d M y H:i:s O';

    /**
     * Format
     *
     * @var string
     * @access protected
     */
    protected $_format = '[%date%][%type%] %message%';

    /**
     * \Phalcon\Logger\Formatter\Line construct
     *
     * @param string|null $format
     * @param string|null $dateFormat
     * @throws Exception
     */
    public function __construct($format = null, $dateFormat = null)
    {
        if (!is_null($format)) {
            if (is_string($format) === false) {
                throw new Exception('Invalid parameter type.');
            } else {
                $this->_format = $format;
            }
        }
        if (!is_null($dateFormat)) {
            if (is_string($dateFormat) === false) {
                throw new Exception('Invalid parameter type.');
            } else {
                $this->_dateFormat = $dateFormat;
            }
        }

    }

    /**
     * Set the log format
     *
     * @param string $format
     * @throws Exception
     */
    public function setFormat($format)
    {
        if (is_string($format) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_format = $format;
    }

    /**
     * Returns the log format
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * Sets the internal date format
     *
     * @param string $date
     * @throws Exception
     */
    public function setDateFormat($date)
    {
        if (is_string($date) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dateFormat = $date;
    }

    /**
     * Returns the internal date format
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->_dateFormat;
    }

    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param string $message
     * @param int $type
     * @param int $timestamp
     * @param array $context
     * @return string
     * @throws Exception
     */
    public function format($message, $type, $timestamp,array $context = null)
    {
        /* Type check */
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($timestamp) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /* Format */
        $format = $this->_format;

        if (Text::memstr($format, '%date%') !== false) {
            $format = str_replace(
                '%date%', $this->getTypeString($type), $timestamp, $format
            );
        }
        $format = str_replace("%message%", $message, $format) . PHP_EOL;

        if (is_array($context) === true) {
            return $this->interpolate($format, $context);
        }

        return $format;
    }

}
