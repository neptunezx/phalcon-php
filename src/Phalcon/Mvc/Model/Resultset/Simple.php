<?php

namespace Phalcon\Mvc\Model\Resultset;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Kernel;

/**
 * Phalcon\Mvc\Model\Resultset\Simple
 *
 * Simple resultsets only contains a complete objects
 * This class builds every complete object as it is required
 */
class Simple extends Resultset implements ResultsetInterface
{

    /**
     * Model
     *
     * @var null|\Phalcon\Mvc\ModelInterface
     * @access protected
     */
    protected $_model;

    /**
     * Column Map
     *
     * @var null|array
     * @access protected
     */
    protected $_columnMap;

    /**
     * Keep Snapshots
     *
     * @var boolean
     * @access protected
     */
    protected $_keepSnapshots = false;

    /**
	 * Phalcon\Mvc\Model\Resultset\Simple constructor
	 *
	 * @param array columnMap
	 * @param \Phalcon\Mvc\ModelInterface|Phalcon\Mvc\Model\Row model
	 * @param \Phalcon\Db\Result\Pdo|null result
	 * @param \Phalcon\Cache\BackendInterface cache
	 * @param boolean keepSnapshots
	 */
    public function __construct(array $columnMap, $model, $result, BackendInterface $cache = null, $keepSnapshots = null)
    {
        $this->_model     = $model;
        $this->_columnMap = $columnMap;

        /**
         * Set if the returned resultset must keep the record snapshots
         */
        $this->_keepSnapshots = boolval($keepSnapshots);

        parent::__construct($result, $cache);
    }

    /**
     * Returns current row in the resultset
     * 
     * @return Phalcon\Mvc\Model\ModelInterface|boolean
     */
    public final function current()
    {
        $activeRow = $this->_activeRow;
        if ($activeRow !== null) {
            return $activeRow;
        }

        /**
         * Current row is set by seek() operations
         */
        $row = $this->_row;

        /**
         * Valid records are arrays
         */
        if (!is_array($row)) {
            $this->_activeRow = false;
            return false;
        }

        /**
         * Get current hydration mode
         */
        $hydrateMode = $this->_hydrateMode;

        /**
         * Get the resultset column map
         */
        $columnMap = $this->_columnMap;

        /**
         * Hydrate based on the current hydration
         */
        switch ($hydrateMode) {

            case Resultset::HYDRATE_RECORDS:

                /**
                 * Set records as dirty state PERSISTENT by default
                 * Performs the standard hydration based on objects
                 */
                if (Kernel::getGlobals("orm.late_state_binding")) {

                    if ($this->_model instanceof Model) {
                        $modelName = get_class($this->_model);
                    } else {
                        $modelName = "Phalcon\\Mvc\\Model";
                    }

                    $activeRow = $modelName::cloneResultMap($this->_model, $row, $columnMap, Model::DIRTY_STATE_PERSISTENT, $this->_keepSnapshots
                    );
                } else {
                    $activeRow = Model::cloneResultMap(
                            $this->_model, $row, $columnMap, Model::DIRTY_STATE_PERSISTENT, $this->_keepSnapshots
                    );
                }
                break;

            default:
                /**
                 * Other kinds of hydrations
                 */
                $activeRow = Model::cloneResultMapHydrate($row, $columnMap, $hydrateMode);
                break;
        }

        $this->_activeRow = $activeRow;
        return $activeRow;
    }

    /**
     * Returns a complete resultset as an array, if the resultset has a big number of rows
     * it could consume more memory than it currently does. Exporting the resultset to an array
     * couldn't be faster with a large number of records
     *
     * @param boolean $renameColumns
     * @return array
     * @throws Exception
     */
    public function toArray($renameColumns = true)
    {
        $renameColumns = (boolean) $renameColumns;

        /**
         * If _rows is not present, fetchAll from database
         * and keep them in memory for further operations
         */
        $records = $this->_rows;
        if (!is_array($records)) {
            $result = $this->_result;
            if ($this->_row !== null) {
                // re-execute query if required and fetchAll rows
                $result->execute();
            }
            $records     = $result->fetchAll();
            $this->_row  = null;
            $this->_rows = $records; // keep result-set in memory
        }

        /**
         * We need to rename the whole set here, this could be slow
         */
        if ($renameColumns) {
            /**
             * Get the resultset column map
             */
            $columnMap = $this->_columnMap;
            if (!is_array($columnMap)) {
                return $records;
            }

            $renamedRecords = [];
            if (is_array($records)) {
                foreach ($records as $record) {
                    $renamed = [];
                    foreach ($record as $key => $value) {
                        /**
                         * Check if the key is part of the column map
                         */
                        if (!isset($columnMap[$key])) {
                            throw new Exception("Column '" . $key . "' is not part of the column map");
                        }

                        $renamedKey = $columnMap[$key];
                        if (is_array($renamedKey)) {
                            if (!isset($renamedKey[0])) {
                                throw new Exception("Column '" . $key . "' is not part of the column map");
                            }
                            $renamedKey = $renamedKey[0];
                        }

                        $renamed[$renamedKey] = $value;
                    }

                    /**
                     * Append the renamed records to the main array
                     */
                    $renamedRecords[] = $renamed;
                }
            }

            return $renamedRecords;
        }

        return $records;
    }

    /**
     * Serializing a resultset will dump all related rows into a big array
     *
     * @return string
     */
    public function serialize()
    {
        //Serialize the cache using the serialize function
        return serialize([
            'model'         => $this->_model,
            'cache'         => $this->_cache,
            'rows'          => $this->toArray(false),
            'columnMap'     => $this->_columnMap,
            'hydrateMode'   => $this->_hydrateMode,
            "keepSnapshots" => $this->_keepSnapshots
        ]);
    }

    /**
     * Unserializing a resultset only works on the rows present in the saved state
     *
     * @param string $data
     * @throws Exception
     */
    public function unserialize($data)
    {
        if (is_string($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $resultset = unserialize($data);

        if (is_array($resultset) === false) {
            throw new Exception('Invalid serialization data');
        }

        $this->_model         = $resultset['model'];
        $this->_rows          = $resultset['rows'];
        $this->_count         = count($resultset["rows"]);
        $this->_cache         = $resultset['cache'];
        $this->_columnMap     = $resultset['columnMap'];
        $this->_hydrateMode   = $resultset['hydrateMode'];
        $this->_keepSnapshots = isset($resultset["keepSnapshots"]) ? $resultset["keepSnapshots"] : false;
    }

}
