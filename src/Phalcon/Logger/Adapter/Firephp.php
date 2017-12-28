<?php

namespace Phalcon\Logger\Adapter;

use \Phalcon\Logger\Adapter;
use \Phalcon\Logger\AdapterInterface;
use \Phalcon\Logger\Exception;
use \Phalcon\Logger\Formatter\Firephp as FirephpFormatter;

/**
 * Phalcon\Logger\Adapter\Firephp
 *
 * Sends logs to FirePHP
 *
 */
class Firephp extends Adapter
{

    /**
     * Initialized
     *
     * @var boolean
     * @access private
     */
    private  $_initialized = false;

    /**
     * Index
     *
     * @var int
     * @access private
     */
    private $_index = 1;

    /**
     * Returns the internal formatter
     *
     * @return \Phalcon\Logger\FormatterInterface
     */
    public function getFormatter()
    {
        $formatter = $this->_formatter;
        if (is_object($formatter) === false) {
            $formatter = new FirephpFormatter();
        }

        return $formatter;
    }

    /**
     * Writes the log to the stream itself
     *
     * @param string $message
     * @param int $type
     * @param int $time
     * @throws Exception
     */
    public function logInternal($message, $type, $time ,array $context)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($time) === false ||
            is_array($context) == false ) {
            throw new Exception('Invalid parameter type.');
        }


        if ($this->_initialized === false) {

            //Send the required initialization headers.
            header("X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2");
            header("X-Wf-1-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3");
            header("X-Wf-1-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1");

            $this->_initialized = true;
        }

        $appliedFormat = $this->getFormatter()->format($message, $type, $time ,$context);
        if (is_string($appliedFormat) === false) {
            throw new Exception('The formatted message is not valid');
        }

        $index  = $this->_index;
        $size   = strlen($appliedFormat);
        $offset = 0;

        //We need to send the data in chunks not exceeding 5,000 bytes.
        while ($size > 0) {
            $str      = 'X-Wf-1-1-1-' . $index . ': ';
            $numBytes = ($size > 4500 ? 4500 : $size);

            if ($offset !== 0) {
                $str .= '|';
            }

            $str .= substr($appliedFormat, $offset, $offset + 4500);

            $size   -= $numBytes;
            $offset += $numBytes;

            if ($size > 0) {
                $str .= "|\\";
            }

            header($str);
            $index++;
        }

        $this->_index = $index;
    }

    /**
     * Closes the logger
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

}
