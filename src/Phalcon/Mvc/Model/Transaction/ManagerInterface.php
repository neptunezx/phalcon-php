<?php

/**
 * Manager Interface
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon\Mvc\Model\Transaction;

/**
 * Phalcon\Mvc\Model\Transaction\ManagerInterface initializer
 *
 */
interface ManagerInterface
{

    /**
     * Checks whether manager has an active transaction
     *
     * @return boolean
     */
    public function has();

    /**
     * Returns a new \Phalcon\Mvc\Model\Transaction or an already created once
     *
     * @param boolean|null $autoBegin
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public function get($autoBegin = true);

    /**
     * Rollbacks active transactions within the manager
     */
    public function rollbackPendent();

    /**
     * Commmits active transactions within the manager
     */
    public function commit();

    /**
     * Rollbacks active transactions within the manager
     * Collect will remove transaction from the manager
     *
     * @param boolean $collect
     */
    public function rollback($collect = false);

    /**
     * Notifies the manager about a rollbacked transaction
     *
     * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
     */
    public function notifyRollback(\Phalcon\Mvc\Model\TransactionInterface $transaction);

    /**
     * Notifies the manager about a commited transaction
     *
     * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
     */
    public function notifyCommit(\Phalcon\Mvc\Model\TransactionInterface $transaction);

    /**
     * Remove all the transactions from the manager
     */
    public function collectTransactions();
}
