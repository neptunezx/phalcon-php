<?php

namespace Phalcon\Logger;

use Phalcon\Logger;

/**
 * Phalcon\Logger\Adapter
 *
 * Base class for Phalcon\Logger adapters
 */
abstract class Adapter implements AdapterInterface
{

    /**
     * Tells if there is an active transaction or not
     *
     * @var boolean
     * @access protected
     */
    protected $_transaction = false;

    /**
     * Array with messages queued in the transaction
     *
     * @var array
     * @access protected
     */
    protected $_queue = array();

    /**
     * Formatter
     *
     * @var object
     * @access protected
     */
    protected $_formatter;

    /**
     * Log Level
     *
     * @var int
     * @access protected
     */
    protected $_logLevel = 9;

    /**
     * Filters the logs sent to the handlers that are less or equal than a specific level
     *
     * @param int $level
     * @return \Phalcon\Logger\AdapterInterface
     * @throws Exception
     */
    public function setLogLevel($level)
    {
        if (is_int($level) === false) {
            throw new Exception('The log level is not valid');
        }

        $this->_logLevel = $level;

        return $this;
    }

    /**
     * Returns the current log level
     *
     * @return int
     */
    public function getLogLevel()
    {
        return $this->_logLevel;
    }

    /**
     * Sets the message formatter
     *
     * @param \Phalcon\Logger\FormatterInterface $formatter
     * @return \Phalcon\Logger\AdapterInterface
     * @throws Exception
     */
    public function setFormatter($formatter)
    {

        if (is_object($formatter) === false ||
            $formatter instanceof FormatterInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_formatter = $formatter;

        return $this;
    }

    /**
     * Starts a transaction
     *
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function begin()
    {
        $this->_transaction = true;

        return $this;
    }

    /**
     * Commits the internal transaction
     *
     * @return \Phalcon\Logger\AdapterInterface
     * @throws Exception
     */
    public function commit()
    {
        /* Set transaction state */
        if ($this->_transaction === false) {
            throw new Exception('There is not active transaction');
        }

        $this->_transaction = false;

        /* Log queue data */
        if (is_array($this->_queue) === true) {
            foreach ($this->_queue as $message) {
                //@note no interface validation
                $messageStr = $message->getMessage();
                $type = $message->getType();
                $time = $message->getTime();
                $context = $message->getContext();
                $this->logInternal($messageStr, $type, $time, $context);
            }
        }

        /* Unset queue */
        $this->_queue = array();

        return $this;
    }

    /**
     * Rollbacks the internal transaction
     *
     * @return \Phalcon\Logger\AdapterInterface
     * @throws Exception
     */
    public function rollback()
    {
        if ($this->_transaction === false) {
            throw new Exception('There is no active transaction');
        }

        $this->_transaction = false;
        $this->_queue = array();

        return $this;
    }

    /**
     * Returns the whether the logger is currently in an active transaction or not
     * @return boollean
     */
    public function isTransaction()
    {
        return $this->_transaction;
    }

    /**
     * Sends/Writes a critical message to the log
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function critical($message, $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!isEmpty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        return $this->log(Logger::CRITICAL, $message, $context);
    }

    /**
     * Sends/Writes an emergence message to the log
     * @param $message string
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function emergence($message, $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!isEmpty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->log($message, Logger::EMERGENCE);

        return $this;
    }

    /**
     * Sends/Writes a debug message to the log
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function debug($message, array $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!empty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->log($message, Logger::DEBUG);

        return $this;
    }

    /**
     * Sends/Writes an error message to the log
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function error($message, array $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!empty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->log($message, Logger::ERROR);

        return $this;
    }

    /**
     * Sends/Writes an info message to the log
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function info($message, array $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!empty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->log($message, Logger::INFO);

        return $this;
    }

    /**
     * Sends/Writes a notice message to the log
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function notice($message, array $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!empty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->log($message, Logger::NOTICE);

        return $this;
    }

    /**
     * Sends/Writes a warning message to the log
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function warning($message, array $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!empty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->log($message, Logger::WARNING);

        return $this;
    }

    /**
     * Sends/Writes an alert message to the log
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function alert($message, array $context = null)
    {
        if (!is_string($message)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!empty($context) && !is_array($context)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->log($message, Logger::ALERT);

        return $this;
    }

    /**
     * Logs messages to the internal logger. Appends messages to the log
     * @param $type
     * @param $message string|null
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function log($type, $message = null, array $context = null)
    {
        if (is_string($type) && is_integer($message)) {
            $toggledMessage = $type;
            $toggledType = $message;
        } else if (is_string($type) && empty($message)) {
            $toggledMessage = $type;
            $toggledType = $message;
        } else {
            $toggledMessage = $message;
            $toggledType = $type;
        }
        if (empty($toggledType)) {
            $toggledType = Logger::DEBUG;
        }

        /**
         * Checks if the log is valid respecting the current log level
         */

        if ($this->_logLevel >= $toggledType) {
            $timestamp = time();
            if ($this->_transaction) {
                $this->_queue[] = new Item($toggledMessage, $toggledType, $timestamp, $context);
            } else {
                $this->logInternal($toggledMessage, $toggledType, $timestamp, $context);
            }
        }

        return $this;
    }

}
