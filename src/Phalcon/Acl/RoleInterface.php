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
 * Phalcon\Acl\RoleInterface
 *
 * Interface for Phalcon\Acl\Role
 */
interface RoleInterface
{

    /**
     * Returns the role name
     *
     * @return string
     */
    public function getName();

    /**
     * Returns role description
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
