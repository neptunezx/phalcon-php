<?php

namespace Phalcon\Logger;

use Phalcon\Logger;
use Phalcon\Logger\Item;
use Phalcon\Logger\Exception;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\FormatterInterface;

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
    protected $_queue = [];

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
        $this->_logLevel = (int) $level;

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
    public function setFormatter(FormatterInterface $formatter)
    {
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
                $this->{"logInternal"}(
                    $message->getMessage(), $message->getType(), $message->getTime(), $message->getContext()
                );
            }
        }

        // clear logger queue at commit
        $this->_queue = [];

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
        $this->_queue       = array();

        return $this;
    }

    /**
     * Returns the whether the logger is currently in an active transaction or not
     * 
     * @return boollean
     */
    public function isTransaction()
    {
        return $this->_transaction;
    }

    /**
     * Sends/Writes a critical message to the log
     * 
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function critical($message, array $context = null)
    {
        return $this->log(Logger::CRITICAL, $message, $context);
    }

    /**
     * Sends/Writes an emergence message to the log
     * 
     * @param $message string
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function emergency($message, array $context = null)
    {
        return $this->log(Logger::EMERGENCE, $message, $context);
    }

    /**
     * Sends/Writes a debug message to the log
     * 
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function debug($message, array $context = null)
    {
        return $this->log(Logger::DEBUG, $message, $context);
    }

    /**
     * Sends/Writes an error message to the log
     * 
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function error($message, array $context = null)
    {
        return $this->log(Logger::ERROR, $message, $context);
    }

    /**
     * Sends/Writes an info message to the log
     * 
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function info($message, array $context = null)
    {
        return $this->log(Logger::INFO, $message, $context);
    }

    /**
     * Sends/Writes a notice message to the log
     * 
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function notice($message, array $context = null)
    {
        return $this->log(Logger::NOTICE, $message, $context);
    }

    /**
     * Sends/Writes a warning message to the log
     * 
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function warning($message, array $context = null)
    {
        return $this->log(Logger::WARNING, $message, $context);
    }

    /**
     * Sends/Writes an alert message to the log
     * 
     * @param $message string
     * @param $context array|null
     * @throws Exception
     * @return \Phalcon\Logger\AdapterInterface
     */
    public function alert($message, array $context = null)
    {
        return $this->log(Logger::ALERT, $message, $context);
    }

    /**
     * Logs messages to the internal logger. Appends messages to the log
     * 
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
            $toggledType    = $message;
        } else if (is_string($type) && empty($message)) {
            $toggledMessage = $type;
            $toggledType    = $message;
        } else {
            $toggledMessage = $message;
            $toggledType    = $type;
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
