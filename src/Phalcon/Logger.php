<?php

/**
 * Logger
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon;

/**
 * Phalcon\Logger
 *
 * Phalcon\Logger is a component whose purpose is create logs using
 * different backends via adapters, generating options, formats and filters
 * also implementing transactions.
 *
 * <code>
 * 	$logger = new \Phalcon\Logger\Adapter\File("app/logs/test.log");
 * 	$logger->log("This is a message");
 * 	$logger->log(\Phalcon\Logger::ERROR, "This is an error");
 * 	$logger->error("This is another error");
 * </code>
 */
abstract class Logger
{

    const SPECIAL   = 9;
    const CUSTOM    = 8;
    const DEBUG     = 7;
    const INFO      = 6;
    const NOTICE    = 5;
    const WARNING   = 4;
    const ERROR     = 3;
    const ALERT     = 2;
    const CRITICAL  = 1;
    const EMERGENCE = 0;
    const EMERGENCY = 0;

}
