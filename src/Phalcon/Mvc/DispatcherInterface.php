<?php

namespace Phalcon\Mvc;

use Phalcon\Mvc\ControllerInterface;
use Phalcon\DispatcherInterface as DispatcherInterfaceBase;

/**
 * Phalcon\Mvc\DispatcherInterface
 *
 * Interface for Phalcon\Mvc\Dispatcher
 */
interface DispatcherInterface extends DispatcherInterfaceBase
{

    /**
     * Sets the default controller suffix
     *
     * @param string $controllerSuffix
     */
    public function setControllerSuffix($controllerSuffix);

    /**
     * Sets the default controller name
     *
     * @param string $controllerName
     */
    public function setDefaultController($controllerName);

    /**
     * Sets the controller name to be dispatched
     *
     * @param string $controllerName
     * @param bool|null $isExact If true, the name should not be mangled in any way
     */
    public function setControllerName($controllerName, $isExact = null);

    /**
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getControllerName();

    /**
     * Returns the lastest dispatched controller
     *
     * @return \Phalcon\Mvc\ControllerInterface
     */
    public function getLastController();

    /**
     * Returns the active controller in the dispatcher
     *
     * @return \Phalcon\Mvc\ControllerInterface
     */
    public function getActiveController();
}
