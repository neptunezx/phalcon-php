<?php

namespace Phalcon\Mvc\Model;

/**
 * Phalcon\Mvc\Model\ResultInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/resultinterface.c
 */
interface ResultInterface
{

    /**
     * Sets the object's state
     *
     * @param boolean $dirtyState
     */
    public function setDirtyState($dirtyState);
}
