<?php

namespace Phalcon\Logger\Adapter;

use \Phalcon\Logger\Adapter;
use \Phalcon\Logger\AdapterInterface;
use \Phalcon\Logger\Exception;
use \Phalcon\Logger\Formatter\Syslog as SyslogFormatter;

/**
 * Phalcon\Logger\Adapter\Syslog
 *
 * Sends logs to the system logger
 */
class Syslog extends Adapter implements AdapterInterface
{

    /**
     * Opened
     *
     * @var boolean
     * @access protected
     */
    protected $_opened = false;

    /**
     * \Phalcon\Logger\Adapter\Syslog constructor
     *
     * @param string $name
     * @param array|null $options
     * @throws Exception
     */
    public function __construct($name,array $options = null)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($options['option']) === true) {
            $option = $options['option'];
        }else{
            $option = LOG_ODELAY;
        }

        if (isset($options['facility']) === true) {
            $facility = $options['facility'];
        }else{
            $facility = LOG_USER;
        }

        //@note no return value check
        openlog($name, $option, $facility);
        $this->_opened = true;
    }

    /**
     * Returns the internal formatter
     *
     * @return SyslogFormatter
     */
    public function getFormatter()
    {
        if (is_object($this->_formatter) === false) {
            $this->_formatter = new SyslogFormatter();
        }

        return $this->_formatter;
    }

    /**
     * Writes the log to the stream itself
     *
     * @param string $message
     * @param int $type
     * @param int $time
     * @param array $context
     * @throws Exception
     */
    public function logInternal($message, $type, $time, array $context)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($time) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $appliedFormat = $this->getFormatter()->format($message, $type, $time ,$context);
        if (is_array($appliedFormat) === false) {
            throw new Exception('The formatted message is not valid');
        }

        //@note no return value check
        syslog($appliedFormat[0], $appliedFormat[1]);
    }

    /**
     * Closes the logger
     *
     * @return null
     */
    public function close()
    {
        //@note we don't set $this->_opened = false!
        if ($this->_opened === false) {
            //@note no return value check
            return true;
        }
        return closelog();
        //@note we don't return a boolean
    }

}
