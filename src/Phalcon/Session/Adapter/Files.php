<?php

/**
 * Files Adapter
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Session\Adapter;

use Phalcon\Session\Adapter;

/**
 * Phalcon\Session\Adapter\Files
 *
 * This adapter store sessions in plain files
 *
 * <code>
 * use Phalcon\Session\Adapter\Files;
 *
 * $session = new Files(
 *     [
 *         "uniqueId" => "my-private-app",
 *     ]
 * );
 *
 * $session->start();
 *
 * $session->set("var", "some-value");
 *
 * echo $session->get("var");
 * </code>
 */
class Files extends Adapter
{
    
}
