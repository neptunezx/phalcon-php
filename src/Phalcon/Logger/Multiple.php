<?php

namespace Phalcon\Logger;

use Phalcon\Logger;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Exception;

/**
 * Phalcon\Logger\Multiple
 *
 * Handles multiples logger handlers
 *
 */
class Multiple
{

    /**
     * Loggers
     *
     * @var null|array
     * @access protected
     */
    protected $_loggers;

    /**
     * Formatter
     *
     * @var null|\Phalcon\Logger\FormatterInterface
     * @access protected
     */
    protected $_formatter;

    /**
     * Formatter
     *
     * @var null|\Phalcon\Logger\FormatterInterface
     * @access protected
     */
    protected $_logLevel;

    /**
     * Pushes a logger to the logger tail
     *
     * @param $logger \Phalcon\Logger\AdapterInterface
     * @throws Exception
     */
    public function push(AdapterInterface $logger)
    {
        if (is_array($this->_loggers) === false) {
            $this->_loggers = array();
        }

        $this->_loggers[] = $logger;
    }

    /**
     * Returns the registered loggers
     *
     * @return \Phalcon\Logger\AdapterInterface[]|null
     */
    public function getLoggers()
    {
        return $this->_loggers;
    }

    /**
     * Sets a global formatter
     *
     * @param $formatter \Phalcon\Logger\FormatterInterface
     * @throws Exception
     */
    public function setFormatter($formatter)
    {
        if (is_object($formatter) === false ||
            $formatter instanceof FormatterInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_loggers) === true) {
            foreach ($this->_loggers as $logger) {
                $logger->setFormatter($formatter);
            }
        }

        $this->_formatter = $formatter;
    }

    /**
     * Sets a global level
     *
     * @param $level int
     * @throws \Exception
     */
    public function setLogLevel($level)
    {
        if (is_int($level) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $loggers = $this->_loggers;
        if (is_array($loggers)) {
            foreach ($loggers as $logger) {
                $logger->setLogLevel($level);
            }
        }
        $this->_logLevel = $level;
    }

    /**
     * Returns a formatter
     *
     * @return \Phalcon\Logger\FormatterInterface|null
     */
    public function getFormatter()
    {
        return $this->_formatter;
    }

    /**
     * Sends a message to each registered logger
     *
     * @param $type
     * @param $message null|var
     * @param $context array|null
     * @throws Exception
     */
    public function log($type, $message, array $context = null)
    {
        $loggers = $this->_loggers;
        if (is_int($type) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_loggers) === true) {
            foreach ($this->_loggers as $logger) {
                $logger->log($message, $type);
            }
        }
    }

    /**
     * Sends/Writes an critical message to the log
     *
     * @param $message string
     * @param $context array|null
     * @throws Exception
     */
    public function critical($message, array $context = null)
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    /**
     * Sends/Writes an emergence message to the log
     *
     * @param $message string
     * @param $context array
     * @throws Exception
     */
    public function emergence($message, array $context = null)
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    /**
     * Sends/Writes a debug message to the log
     *
     * @param $message string
     * @param $context array
     * @throws Exception
     */
    public function debug($message, array $context = null)
    {
        $this->log(Logger::DEBUG, $message, $context);
    }

    /**
     * Sends/Writes an error message to the log
     *
     * @param $message string
     * @param $context array
     * @throws Exception
     */
    public function error($message, array $context = null)
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /**
     * Sends/Writes an info message to the log
     *
     * @param $message string
     * @param $context array
     * @throws Exception
     */
    public function info($message, array $context = null)
    {
        $this->log(Logger::INFO, $message, $context);
    }

    /**
     * Sends/Writes a notice message to the log
     *
     * @param $message string
     * @param $context array
     * @throws Exception
     */
    public function notice($message, array $context = null)
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    /**
     * Sends/Writes a warning message to the log
     *
     * @param $message string
     * @param $context array
     * @throws Exception
     */
    public function warning($message, array $context = null)
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    /**
     * Sends/Writes an alert message to the log
     *
     * @param $message string
     * @param $context array
     * @throws Exception
     */
    public function alert($message, array $context = null)
    {
        $this->log(Logger::ALERT, $message, $context);
    }

}
