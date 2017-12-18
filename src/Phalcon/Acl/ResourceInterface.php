<?php

/**
 * ACL Resource Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Acl;

/**
 *
 * Phalcon\Acl\ResourceInterface
 *
 * Interface for Phalcon\Acl\Resource
 */
interface ResourceInterface
{

    /**
     * Returns the resource name
     *
     * @return string
     */
    public function getName();

    /**
     * Returns resource description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString();
}
