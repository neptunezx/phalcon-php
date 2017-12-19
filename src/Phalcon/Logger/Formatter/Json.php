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
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/formatter/json.c
 */
class Json extends Formatter implements FormatterInterface
{

    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param string $message
     * @param int $type
     * @param int $timestamp
     * @return string
     * @throws Exception
     */
    public function format($message, $type, $timestamp)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($timestamp) === false) {
            throw new Exception('Invalid parameter type.');
        }


        //@note no exception handeling
        return json_encode(
            array(
                'type'      => $this->getTypeString($type),
                'message'   => $message,
                'timestamp' => $timestamp
            )
        );
    }

}
