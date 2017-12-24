<?php

namespace Phalcon\Mvc\Model\Transaction;

use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\Transaction\ManagerInterface;
use Phalcon\Mvc\Model\Transaction\Exception;
use Phalcon\Mvc\Model\Transaction;
use Phalcon\Mvc\Model\TransactionInterface;

/**
 * Phalcon\Mvc\Model\Transaction\Manager
 */
class Manager implements ManagerInterface, InjectionAwareInterface
{

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
     */
    protected $_dependencyInjector;

    /**
     * Initialized?
     *
     * @var boolean
     * @access protected
     */
    protected $_initialized = false;

    /**
     * Rollback Pendent
     *
     * @var boolean
     * @access protected
     */
    protected $_rollbackPendent = true;

    /**
     * Number
     *
     * @var int
     * @access protected
     */
    protected $_number = 0;

    /**
     * Service
     *
     * @var string
     * @access protected
     */
    protected $_service = 'db';

    /**
     * Transactions
     *
     * @var null|array
     * @access protected
     */
    protected $_transactions;

    /**
     * \Phalcon\Mvc\Model\Transaction\Manager constructor
     *
     * @param \Phalcon\DiInterface|null $dependencyInjector
     * @throws Exception
     */
    public function __construct(DiInterface $dependencyInjector = null)
    {
        if ($dependencyInjector) {
            $this->_dependencyInjector = $dependencyInjector;
        } else {
            $this->_dependencyInjector = DI::getDefault();
            if (is_object($this->_dependencyInjector) === false) {
                throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
            }
        }
    }

    /**
     * Sets the dependency injection container
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI($dependencyInjector)
    {
        if (is_object($dependencyInjector) === false ||
            $dependencyInjector instanceof DiInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the dependency injection container
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets the database service used to run the isolated transactions
     *
     * @param string $service
     * @return \Phalcon\Mvc\Model\Transaction\Manager
     * @throws Exception
     */
    public function setDbService($service)
    {
        if (is_string($service) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_service = $service;

        return $this;
    }

    /**
     * Returns the database service used to isolate the transaction
     *
     * @return string
     */
    public function getDbService()
    {
        return $this->_service;
    }

    /**
     * Set if the transaction manager must register a shutdown function to clean up pendent transactions
     *
     * @param boolean $rollbackPendent
     * @return \Phalcon\Mvc\Model\Transaction\Manager
     * @throws Exception
     */
    public function setRollbackPendent($rollbackPendent)
    {
        $this->_rollbackPendent = (boolean) $rollbackPendent;
        return $this;
    }

    /**
     * Check if the transaction manager is registering a shutdown function to clean up pendent transactions
     *
     * @return boolean
     */
    public function getRollbackPendent()
    {
        return $this->_rollbackPendent;
    }

    /**
     * Checks whether the manager has an active transaction
     *
     * @return boolean
     */
    public function has()
    {
        return $this->_number > 0;
    }

    /**
     * Returns a new \Phalcon\Mvc\Model\Transaction or an already created once
     * This method registers a shutdown function to rollback active connections
     *
     * @param boolean|null $autoBegin
     * @return \Phalcon\Mvc\Model\TransactionInterface
     * @throws Exception
     */
    public function get($autoBegin = true)
    {
        $autoBegin = (boolean) $autoBegin;

        if ($this->_initialized === true) {
            //@note this might be wrong?
            if ($this->_rollbackPendent === true) {
                register_shutdown_function(array($this, 'rollbackPendent'));
            }

            $this->_initialized = true;
        }

        return $this->getOrCreateTransaction();
    }

    /**
     * Create/Returns a new transaction or an existing one
     *
     * @param boolean|null $autoBegin
     * @return \Phalcon\Mvc\Model\TransactionInterface
     * @throws Exception
     */
    public function getOrCreateTransaction($autoBegin = true)
    {
        $autoBegin = (boolean) $autoBegin;

        if (is_object($this->_dependencyInjector) === false) {
            throw new Exception('A dependency injector container is required to obtain the services related to the ORM');
        }

        if ($this->_number != 0 &&
            is_array($this->_transactions) === true) {
            foreach ($this->_transactions as $transaction) {
                if (is_object($transaction) === true) {
                    $transaction->setIsNewTransaction(false);
                    return $transaction;
                }
            }
        }

        $transaction           = new Transaction($this->_dependencyInjector, $autoBegin, $this->_service);
        $this->_transactions[] = $transaction;
        $this->_number++;
        return $transaction;
    }

    /**
     * Rollbacks active transactions within the manager
     */
    public function rollbackPendent()
    {
        $this->rollback();
    }

    /**
     * Commmits active transactions within the manager
     */
    public function commit()
    {
        if (is_array($this->_transactions) === true) {
            foreach ($this->_transactions as $transaction) {
                $connection = $transaction->getConnection();
                if ($connection->isUnderTransaction() === true) {
                    $connection->commit();
                }
            }
        }
    }

    /**
     * Rollbacks active transactions within the manager
     * Collect will remove transaction from the manager
     *
     * @param boolean|null $collect
     * @throws Exception
     */
    public function rollback($collect = null)
    {
        $collect = (boolean) $collect;

        if (is_array($this->_transactions) === true) {
            foreach ($this->_transactions as $transaction) {
                $connection = $transaction->getConnection();

                if ($connection->isUnderTransaction() === true) {
                    $connection->rollback();
                    $connection->close();
                }

                if ($collect === true) {
                    $this->_collectTransaction($transaction);
                }
            }
        }
    }

    /**
     * @param TransactionInterface $transaction
     * @throws \Phalcon\Mvc\Model\Transaction\Exception
     */
    public function notifyRollback(TransactionInterface $transaction)
    {
        $this->_collectTransaction($transaction);
    }

    /**
     * Notifies the manager about a commited transaction
     *
     * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
     * @throws Exception
     */
    public function notifyCommit(TransactionInterface $transaction)
    {
        $this->_collectTransaction($transaction);
    }

    /**
     * Removes transactions from the TransactionManager
     *
     * @param \Phalcon\Mvc\Model\TransactionInterface $transaction
     * @throws Exception
     */
    protected function _collectTransaction(TransactionInterface $transaction)
    {
        $transactions = $this->_transactions;
        if (count($transactions)) {
            $newTransactions = [];
            foreach ($transactions as $managedTransaction) {
                if ($managedTransaction != $transaction) {
                    $newTransactions[] = $transaction;
                } else {
                    $this->_number--;
                }
            }
            $this->_transactions = $newTransactions;
        }
    }

    /**
     * Remove all the transactions from the manager
     */
    public function collectTransactions()
    {
        $transactions = $this->_transactions;
        if (count($transactions)) {
            foreach ($transactions as $_) {
                $this->_number--;
            }
            $this->_transactions = null;
        }
    }

}
