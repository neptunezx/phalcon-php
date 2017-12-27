<?php

namespace Phalcon\Mvc\Model;

use Phalcon\Db;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\ResultsetInterface;

/**
 * Phalcon\Mvc\Model\Resultset
 *
 * This component allows to Phalcon\Mvc\Model returns large resultsets with the minimum memory consumption
 * Resultsets can be traversed using a standard foreach or a while statement. If a resultset is serialized
 * it will dump all the rows into a big array. Then unserialize will retrieve the rows as they were before
 * serializing.
 *
 * <code>
 *
 * // Using a standard foreach
 * $robots = Robots::find(
 *     [
 *         "type = 'virtual'",
 *         "order" => "name",
 *     ]
 * );
 *
 * foreach ($robots as robot) {
 *     echo robot->name, "\n";
 * }
 *
 * // Using a while
 * $robots = Robots::find(
 *     [
 *         "type = 'virtual'",
 *         "order" => "name",
 *     ]
 * );
 *
 * $robots->rewind();
 *
 * while ($robots->valid()) {
 *     $robot = $robots->current();
 *
 *     echo $robot->name, "\n";
 *
 *     $robots->next();
 * }
 * </code>
 */
abstract class Resultset implements ResultsetInterface, \Iterator, \SeekableIterator, \Countable, \ArrayAccess, \Serializable, \JsonSerializable
{

    /**
     * Phalcon\Db\ResultInterface or false for empty resultset
     */
    protected $_result      = false;
    protected $_cache;
    protected $_isFresh     = true;
    protected $_pointer     = 0;
    protected $_count;
    protected $_activeRow   = null;
    protected $_rows        = null;
    protected $_row         = null;
    protected $_errorMessages;
    protected $_hydrateMode = 0;

    const TYPE_RESULT_FULL    = 0;
    const TYPE_RESULT_PARTIAL = 1;
    const HYDRATE_RECORDS     = 0;
    const HYDRATE_OBJECTS     = 2;
    const HYDRATE_ARRAYS      = 1;

    /**
     * Phalcon\Mvc\Model\Resultset constructor
     *
     * @param \Phalcon\Db\ResultInterface|false result
     * @param \Phalcon\Cache\BackendInterface cache
     */
    public function __construct($result, BackendInterface $cache = null)
    {
        /**
         * 'false' is given as result for empty result-sets
         */
        if (!$result instanceof ResultInterface) {
            $this->_count = 0;
            $this->_rows  = [];
            return;
        }

        /**
         * Valid resultsets are Phalcon\Db\ResultInterface instances
         */
        $this->_result = $result;

        /**
         * Update the related cache if any
         */
        if ($cache !== null) {
            $this->_cache = $cache;
        }

        /**
         * Do the fetch using only associative indexes
         */
        $result->setFetchMode(Db::FETCH_ASSOC);

        /**
         * Update the row-count
         */
        $rowCount     = $result->numRows();
        $this->_count = $rowCount;

        /**
         * Empty result-set
         */
        if ($rowCount == 0) {
            $this->_rows = [];
            return;
        }

        /**
         * Small result-sets with less equals 32 rows are fetched at once
         */
        if ($rowCount <= 32) {
            /**
             * Fetch ALL rows from database
             */
            $rows = $result->fetchAll();
            if (is_array($rows)) {
                $this->_rows = $rows;
            } else {
                $this->_rows = [];
            }
        }
    }

    /**
     * Moves cursor to next row in the resultset
     * 
     * return void
     */
    public function next()
    {
        // Seek to the next position
        $this->seek($this->_pointer + 1);
    }

    /**
     * Gets pointer number of active row in the resultset
     *
     * @return int
     */
    public function key()
    {
        return $this->_pointer;
    }

    /**
     * Check whether internal resource has rows to fetch
     * 
     * @return boolean
     */
    public function valid()
    {
        return $this->_pointer < $this->_count;
    }

    /**
     * Rewinds resultset to its beginning
     * 
     * @return void
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Changes internal pointer to a specific position in the resultset
     *
     * @param int $position
     */
    public function seek($position)
    {
        $position = (int) $position;
        if ($this->_pointer != $position || $this->_row === null) {
            if (is_array($this->_rows)) {
                /**
                 * All rows are in memory
                 */
                if (isset($this->_rows[$position])) {
                    $this->_row = $this->_rows[$position];
                }

                $this->_pointer   = $position;
                $this->_activeRow = null;
                return;
            }

            /**
             * Fetch from PDO one-by-one.
             */
            $result = $this->_result;
            if ($this->_row === null && $this->_pointer === 0) {
                /**
                 * Fresh result-set: Query was already executed in model\query::_executeSelect()
                 * The first row is available with fetch
                 */
                $this->_row = $result->fetch();
            }

            if ($this->_pointer > $position) {
                /**
                 * Current pointer is ahead requested position: e.g. request a previous row
                 * It is not possible to rewind. Re-execute query with dataSeek
                 */
                $result->dataSeek($position);
                $this->_row     = $result->fetch();
                $this->_pointer = $position;
            }

            while ($this->_pointer < $position) {
                /**
                 * Requested position is greater than current pointer,
                 * seek forward until the requested position is reached.
                 * We do not need to re-execute the query!
                 */
                $this->_row = $result->fetch();
                $this->_pointer++;
            }

            $this->_pointer   = $position;
            $this->_activeRow = null;
        }
    }

    /**
     * Counts how many rows are in the resultset
     *
     * @return int
     */
    public function count()
    {
        return $this->_count;
    }

    /**
     * Checks whether offset exists in the resultset
     *
     * @param int $index
     * @return boolean
     * @throws Exception
     */
    public function offsetExists($index)
    {
        if (is_int($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return $index < $this->_count;
    }

    /**
     * Gets row in a specific position of the resultset
     *
     * @param int $index
     * @return \Phalcon\Mvc\ModelInterface
     * @throws Exception
     */
    public function offsetGet($index)
    {
        if (is_int($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($index < $this->_count) {
            /**
             * Move the cursor to the specific position
             */
            $this->seek($index);

            return $this->{"current"}();
        }
        throw new Exception("The index does not exist in the cursor");
    }

    /**
     * Resultsets cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
     *
     * @param int $index
     * @param \Phalcon\Mvc\ModelInterface $value
     * @throws Exception
     */
    public function offsetSet($index, $value)
    {
        throw new Exception('Cursor is an immutable ArrayAccess object');
    }

    /**
     * Resultsets cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
     *
     * @param int $offset
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        throw new Exception('Cursor is an immutable ArrayAccess object');
    }

    /**
     * Returns the internal type of data retrieval that the resultset is using
     *
     * @return int
     */
    public function getType()
    {
        return is_array($this->_rows) ? self::TYPE_RESULT_FULL : self::TYPE_RESULT_PARTIAL;
    }

    /**
     * Get first row in the resultset
     *
     * @return \Phalcon\Mvc\ModelInterface|boolean
     */
    public function getFirst()
    {
        if ($this->_count == 0) {
            return false;
        }

        $this->seek(0);
        return $this->{"current"}();
    }

    /**
     * Get last row in the resultset
     *
     * @return \Phalcon\Mvc\ModelInterface|boolean
     */
    public function getLast()
    {
        $count = $this->_count;
        if ($count == 0) {
            return false;
        }

        $this->seek($count - 1);
        return $this->{"current"}();
    }

    /**
     * Set if the resultset is fresh or an old one cached
     *
     * @param boolean $isFresh
     * @return \Phalcon\Mvc\Model\Resultset
     * @throws Exception
     */
    public function setIsFresh($isFresh)
    {
        if (is_bool($isFresh) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_isFresh = $isFresh;

        return $this;
    }

    /**
     * Tell if the resultset if fresh or an old one cached
     *
     * @return boolean
     */
    public function isFresh()
    {
        return $this->_isFresh;
    }

    /**
     * Sets the hydration mode in the resultset
     *
     * @param int $hydrateMode
     * @return \Phalcon\Mvc\Model\Resultset
     * @throws Exception
     */
    public function setHydrateMode($hydrateMode)
    {
        $this->_hydrateMode = (int) $hydrateMode;

        return $this;
    }

    /**
     * Returns the current hydration mode
     *
     * @return int|null
     */
    public function getHydrateMode()
    {
        return $this->_hydrateMode;
    }

    /**
     * Returns the associated cache for the resultset
     *
     * @return \Phalcon\Cache\BackendInterface|null
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Returns current row in the resultset
     *
     * @return \Phalcon\Mvc\ModelInterface
     */
    public function current()
    {
        return $this->_activeRow;
    }

    /**
     * Returns the error messages produced by a batch operation
     *
     * @return \Phalcon\Mvc\Model\MessageInterface[]|null
     */
    public function getMessages()
    {
        return $this->_errorMessages;
    }

    /**
     * Updates every record in the resultset
     *
     * @param array data
     * @param \Closure conditionCallback
     * @return boolean
     */
    public function update($data, \Closure $conditionCallback = null)
    {
        $connection = null;

        $transaction = false;

        $this->rewind();

        while ($this->valid()) {

            $record = $this->current();

            if ($transaction === false) {

                /**
                 * We only can update resultsets if every element is a complete object
                 */
                if (!method_exists($record, "getWriteConnection")) {
                    throw new Exception("The returned record is not valid");
                }

                $connection  = $record->getWriteConnection();
                $transaction = true;

                $connection->begin();
            }

            /**
             * Perform additional validations
             */
            if (is_object($conditionCallback)) {
                if (call_user_func_array($conditionCallback, [$record]) === false) {
                    $this->next();
                    continue;
                }
            }

            /**
             * Try to update the record
             */
            if (!$record->save($data)) {
                /**
                 * Get the messages from the record that produce the error
                 */
                $this->_errorMessages = $record->getMessages();

                /**
                 * Rollback the transaction
                 */
                $connection->rollback();
                $transaction = false;
                break;
            }

            $this->next();
        }

        /**
         * Commit the transaction
         */
        if ($transaction === true) {
            $connection->commit();
        }

        return true;
    }

    /**
     * Deletes every record in the resultset
     *
     * @param Closure|null $conditionCallback
     * @return boolean
     * @throws Exception
     */
    public function delete(\Closure $conditionCallback = null)
    {
        $transaction = false;
        $result      = true;
        $transaction = false;
        $this->rewind();

        while ($this->valid()) {
            $record = $this->current();
            //Start transaction
            if ($transaction === false) {
                //We can only delete resultsets whose every element is a complete object
                if (method_exists($record, 'getWriteConnection') === false) {
                    throw new Exception('The returned record is not valid');
                }

                $connection  = $record->getWriteConnection();
                $transaction = true;
                $connection->begin();
            }

            //Perform additional validations
            if (is_object($conditionCallback) === true) {
                if (call_user_func($conditionCallback, $record) === false) {
                    continue;
                }
            }

            //Try to delete the record
            if ($record->delete() !== true) {
                //Get the messages from the record that produces the error
                $this->_errorMessages = $record->getMessages();

                //Rollback the transaction
                $connection->rollback();
                $result      = false;
                $transaction = false;
                break;
            }

            //Next element
            $this->next();
        }

        //Commit the transaction
        if ($transaction === true) {
            $connection->commit();
        }

        return $result;
    }

    /**
     * Filters a resultset returning only those the developer requires
     *
     * <code>
     * $filtered = $robots->filter(
     *     function ($robot) {
     *         if ($robot->id < 3) {
     *             return $robot;
     *         }
     *     }
     * );
     * </code>
     *
     * @param callback filter
     * @return \Phalcon\Mvc\Model[]
     */
    public function filter($filter)
    {
        $records    = array();
        $parameters = array();
        $this->rewind();

        while ($this->valid()) {
            $record          = $this->current();
            $parameters[0]   = $record;
            $processedRecord = call_user_func_array($filter, $parameters);

            //Only add processed records to 'records' if the returned value is an array/object
            if (is_object($processedRecord) === false && is_array($processedRecord) === false) {
                $this->next();
                continue;
            }

            $records[] = $processedRecord;
            $this->next();
        }

        return $records;
    }

    /**
     * Returns serialised model objects as array for json_encode.
     * Calls jsonSerialize on each object if present
     *
     * <code>
     * $robots = Robots::find();
     * echo json_encode($robots);
     * </code>
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $records = [];
        $this->rewind();

        while ($this->valid()) {
            $current = $this->current();

            if (is_object($current) && method_exists($current, "jsonSerialize")) {
                $records[] = $current->{"jsonSerialize"}();
            } else {
                $records[] = $current;
            }

            $this->next();
        }

        return $records;
    }

}
