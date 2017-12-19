<?php

namespace Phalcon\Logger;

/**
 * Phalcon\Logger\FormatterInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/logger/formatterinterface.c
 */
interface FormatterInterface
{

    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param string $message
     * @param int $type
     * @param int $timestamp
     */
    public function format($message, $type, $timestamp);
}
