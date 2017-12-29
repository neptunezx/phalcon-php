<?php

namespace Phalcon\Logger\Formatter;

use \Phalcon\Logger\Formatter;
use \Phalcon\Logger\FormatterInterface;
use \Phalcon\Logger\Exception;

/**
 * Phalcon\Logger\Formatter\Syslog
 *
 * Prepares a message to be used in a Syslog backend
 *
 */
class Syslog extends Formatter
{

    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param string $message
     * @param int $type
     * @param int $timestamp
     * @param array $context
     * @return array
     * @throws Exception
     */
    public function format($message, $type, $timestamp, array $context = null)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($timestamp) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($context)) {
            $message = $this->interpolate($message, $context);
        }

        return array($type, $message);
    }

}
