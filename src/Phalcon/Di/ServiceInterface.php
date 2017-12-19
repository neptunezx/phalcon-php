<?php

/**
 * Service Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Di;

use Phalcon\DiInterface;

/**
 * Phalcon\Di\ServiceInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/di/serviceinterface.c
 */
interface ServiceInterface
{

    /**
     * Returns the service's name
     *
     * @param string
     */
    public function getName();

    /**
     * Sets if the service is shared or not
     *
     * @param boolean $shared
     */
    public function setShared($shared);

    /**
     * Check whether the service is shared or not
     *
     * @return boolean
     */
    public function isShared();

    /**
     * Set the service definition
     *
     * @param mixed $definition
     */
    public function setDefinition($definition);

    /**
     * Returns the service definition
     *
     * @return mixed
     */
    public function getDefinition();

    /**
     * Resolves the service
     *
     * @param array $parameters
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @return mixed
     */
    public function resolve($parameters = null, DiInterface $dependencyInjector = null);

    /**
     * Changes a parameter in the definition without resolve the service
     * 
     * @param int $position
     * @param array $parameter
     * @return \Phalcon\Di\ServiceInterface
     */
    public function setParameter($position, array $parameter);

    /**
     * Restore the interal state of a service
     *
     * @param array $attributes
     * @return \Phalcon\Di\ServiceInterface
     */
    public static function __set_state(array $attributes);
}
