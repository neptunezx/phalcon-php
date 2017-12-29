<?php

namespace Phalcon\Logger;

use Phalcon\Logger;

/**
 * Phalcon\Logger\Formatter
 *
 * This is a base class for logger formatters
 */
abstract class Formatter implements FormatterInterface
{

    /**
     * Returns the string meaning of a logger constant
     *
     * @param integer $type
     * @return string
     */
    public function getTypeString($type)
    {
        switch ((int) $type) {

            case Logger::DEBUG:
                return "DEBUG";

            case Logger::ERROR:
                return "ERROR";

            case Logger::WARNING:
                return "WARNING";

            case Logger::CRITICAL:
                return "CRITICAL";

            case Logger::CUSTOM:
                return "CUSTOM";

            case Logger::ALERT:
                return "ALERT";

            case Logger::NOTICE:
                return "NOTICE";

            case Logger::INFO:
                return "INFO";

            case Logger::EMERGENCY:
                return "EMERGENCY";

            case Logger::SPECIAL:
                return "SPECIAL";
        }

        return "CUSTOM";
    }

    /**
     * Interpolates context values into the message placeholders
     *
     * @param string $message
     * @param array $context
     * @return string
     * @throws Exception
     */
    public function interpolate($message, array $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_array($context) && count($context) > 0) {
            $replace = array();
            foreach ($context as $key => $value) {
                $replace['{' . $key . '}'] = $value;
            }
            return strtr($message, $replace);
        }

        return $message;
    }

}
