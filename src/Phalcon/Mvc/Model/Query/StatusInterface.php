<?php

namespace Phalcon\Mvc\Model\Query;

/**
 * Phalcon\Mvc\Model\Query\StatusInterface initializer
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/mvc/model/query/statusinterface.c
 */
interface StatusInterface
{

    /**
     * Returns the model which executed the action
     *
     * @return \Phalcon\Mvc\ModelInterface
     */
    public function getModel();

    /**
     * Returns the messages produced by a operation failed
     *
     * @return \Phalcon\Mvc\Model\MessageInterface[]
     */
    public function getMessages();

    /**
     * Allows to check if the executed operation was successful
     *
     * @return boolean
     */
    public function success();
}
