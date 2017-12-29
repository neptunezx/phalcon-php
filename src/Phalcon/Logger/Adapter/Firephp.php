<?php

namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Adapter;
use Phalcon\Logger\Exception;
use Phalcon\Logger\FormatterInterface;
use Phalcon\Logger\Formatter\Firephp as FirePhpFormatter;

/**
 * Phalcon\Logger\Adapter\Firephp
 *
 * Sends logs to FirePHP
 *
 * <code>
 * use Phalcon\Logger\Adapter\Firephp;
 * use Phalcon\Logger;
 *
 * $logger = new Firephp();
 *
 * $logger->log(Logger::ERROR, "This is an error");
 * $logger->error("This is another error");
 * </code>
 */
class Firephp extends Adapter
{

    /**
     * Initialized
     *
     * @var boolean
     * @access private
     */
    private $_initialized = false;

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
    public function logInternal($message, $type, $time, array $context)
    {
        if (is_string($message) === false ||
            is_int($type) === false ||
            is_int($time) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (!$this->_initialized) {
            header("X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2");
            header("X-Wf-1-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3");
            header("X-Wf-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1");

            $this->_initialized = true;
        }

        $format = $this->getFormatter()->format($message, $type, $time, $context);
        $chunk  = str_split($format, 4500);
        $index  = $this->_index;

        foreach ($chunk as $key => $chString) {
            $content = "X-Wf-1-1-1-" . (string) $index . ": " . $chString;

            if (isset($chunk[$key + 1])) {
                $content .= "|\\";
            }

            header($content);

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
