<?php

namespace Phalcon\Logger\Formatter;

use \Phalcon\Logger\Formatter;
use \Phalcon\Logger\FormatterInterface;
use \Phalcon\Logger\Exception;

/**
 * Phalcon\Logger\Formatter\Json
 *
 * Formats messages using JSON encoding
 *
 */
class Json extends Formatter
{

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
    public function format($message, $type, $timestamp, $context = null)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($timestamp) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($context)) {
            $message = $this->interpolate($message, $context);
        }

        //@note no exception handeling
        return json_encode(
            array(
                'type' => $this->getTypeString($type),
                'message' => $message,
                'timestamp' => $timestamp
            )
        );
    }

}
