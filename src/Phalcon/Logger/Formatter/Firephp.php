<?php

namespace Phalcon\Logger\Formatter;

use Phalcon\Logger;
use Phalcon\Logger\Formatter;

/**
 * Phalcon\Logger\Formatter\Firephp
 *
 * Formats messages so that they can be sent to FirePHP
 *
 */
class Firephp extends Formatter
{

    /**
     * Show Backtrace
     *
     * @var boolean
     * @access protected
     */
    protected $_showBacktrace = true;

    /**
     * @var bool
     */
    protected $_enableLabels = true;

    /**
     * Returns the string meaning of a logger constant
     *
     * @param int $type
     * @return string
     */
    public function getTypeString($type)
    {
        switch ($type) {
            case Logger::EMERGENCY:
            case Logger::CRITICAL:
            case Logger::ERROR:
                return "ERROR";

            case Logger::ALERT:
            case Logger::WARNING:
                return "WARN";

            case Logger::INFO:
            case Logger::NOTICE:
            case Logger::CUSTOM:
                return "INFO";

            case Logger::DEBUG:
            case Logger::SPECIAL:
                return "LOG";
        }

        return "CUSTOM";
    }

    /**
     * Get _showBacktrace member variable
     *
     * @return boolean
     */
    public function getShowBacktrace()
    {
        return $this->_showBacktrace;
    }

    /**
     * Set _showBacktrace member variable
     *
     * @param boolean|null $show
     * @throws Exception
     */
    public function setShowBacktrace($show = null)
    {
        $this->_showBacktrace = (boolean) $show;
    }

    /**
     * Returns the string meaning of a logger constant
     *
     * @param null|bool $isEnable
     * @return Firephp
     */
    public function enableLabels($isEnable = null)
    {
        $isEnable            = (boolean) $isEnable;
        $this->_enableLabels = $isEnable;
        return $this;
    }

    /**
     * Returns the labels enabled
     *
     * @return bool
     */
    public function labelsEnabled()
    {
        return $this->_enableLabels;
    }

    /**
     * Applies a format to a message before sending it to the log
     *
     * @param string $message
     * @param int $type
     * @param int $timestamp
     * @param var $context
     * @return string
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

        $meta = ["Type" => $this->getTypeString($type)];

        if ($this->_showBacktrace) {
            $param     = DEBUG_BACKTRACE_IGNORE_ARGS;
            $backtrace = debug_backtrace($param);
            $lastTrace = end($backtrace);

            if (isset($lastTrace["file"])) {
                $meta["File"] = $lastTrace["file"];
            }

            if (isset($lastTrace["line"])) {
                $meta["Line"] = $lastTrace["line"];
            }

            foreach ($backtrace as $key => $backtraceItem) {
                unset($backtraceItem["object"]);
                unset($backtraceItem["args"]);

                $backtrace[$key] = $backtraceItem;
            }
        }

        if ($this->_enableLabels) {
            $meta["Label"] = $message;
        }

        if (!$this->_enableLabels && !$this->_showBacktrace) {
            $body = $message;
        } elseif ($this->_enableLabels && !$this->_showBacktrace) {
            $body = "";
        } else {
            $body = [];

            if ($this->_showBacktrace) {
                $body["backtrace"] = $backtrace;
            }

            if (!$this->_enableLabels) {
                $body["message"] = $message;
            }
        }

        $encoded = json_encode([$meta, $body]);
        $len     = strlen($encoded);
        codecept_debug('$this->_showBacktrace'.$this->_showBacktrace);
        return $len . "|" . $encoded . "|";
    }

}
