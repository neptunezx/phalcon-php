<?php

namespace Phalcon\Mvc\Model\Resultset;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Row;
use Phalcon\Db\ResultInterface;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Kernel;

/**
 * Phalcon\Mvc\Model\Resultset\Complex
 *
 * Complex resultsets may include complete objects and scalar values.
 * This class builds every complex row as it is required
 */
class Complex extends Resultset implements ResultsetInterface
{

    protected $_columnTypes;

    /**
     * Unserialised result-set hydrated all rows already. unserialise() sets _disableHydration to true
     */
    protected $_disableHydration = false;

    /**
     * \Phalcon\Mvc\Model\Resultset\Complex constructor
     *
     * @param array $columnsTypes
     * @param \Phalcon\Db\ResultInterface $result
     * @param \Phalcon\Cache\BackendInterface|null $cache
     * @throws Exception
     */
    public function __construct(array $columnsTypes, ResultInterface $result, BackendInterface $cache = null)
    {
        //Column types tell the resultset how to build the result
        $this->_columnTypes = $columnsTypes;

        parent::__construct($result, $cache);
    }

    /**
     * Returns current row in the resultset
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
         * Resultset was unserialized, we do not need to hydrate
         */
        if ($this->_disableHydration) {
            $this->_activeRow = $row;
            return $row;
        }

        /**
         * Valid records are arrays
         */
        if (is_array($row)) {
            $this->_activeRow = false;
            return false;
        }

        /**
         * Get current hydration mode
         */
        $hydrateMode = $this->_hydrateMode;

        /**
         * Each row in a complex result is a Phalcon\Mvc\Model\Row instance
         */
        switch ($hydrateMode) {
            case Resultset::HYDRATE_RECORDS:
                $activeRow = new Row();
                break;

            case Resultset::HYDRATE_ARRAYS:
                $activeRow = [];
                break;

            case Resultset::HYDRATE_OBJECTS:
            default:
                $activeRow = new \stdClass();
                break;
        }

        /**
         * Set records as dirty state PERSISTENT by default
         */
        $dirtyState = 0;

        /**
         * Create every record according to the column types
         */
        foreach ($this->_columnTypes as $alias => $column) {

            if (is_array($column)) {
                throw new Exception("Column type is corrupt");
            }

            $type = $column["type"];
            if ($type === "object") {
                /**
                 * Object columns are assigned column by column
                 */
                $source     = $column["column"];
                $attributes = $column["attributes"];
                $columnMap  = $column["columnMap"];

                /**
                 * Assign the values from the _source_attribute notation to its real column name
                 */
                $rowModel = [];
                foreach ($attributes as $attribute) {
                    /**
                     * Columns are supposed to be in the form _table_field
                     */
                    $columnValue          = $row["_" . $source . "_" . $attribute];
                    $rowModel[$attribute] = $columnValue;
                }

                /**
                 * Generate the column value according to the hydration type
                 */
                switch ($hydrateMode) {
                    case Resultset::HYDRATE_RECORDS:
                        $keepSnapshots = isset($column["keepSnapshots"]) ? $column["keepSnapshots"] : false;
                        if (Kernel::getGlobals("orm.late_state_binding")) {
                            if ($column["instance"] instanceof Model) {
                                $modelName = get_class($column["instance"]);
                            } else {
                                $modelName = "Phalcon\\Mvc\\Model";
                            }

                            $value = $modelName::cloneResultMap(
                                    $column["instance"], $rowModel, $columnMap, $dirtyState, $keepSnapshots
                            );
                        } else {
                            // Get the base instance
                            // Assign the values to the attributes using a column map
                            $value = Model::cloneResultMap(
                                    $column["instance"], $rowModel, $columnMap, $dirtyState, $keepSnapshots
                            );
                        }
                        break;

                    default:
                        // Other kinds of hydration
                        $value = Model::cloneResultMapHydrate($rowModel, $columnMap, $hydrateMode);
                        break;
                }

                /**
                 * The complete object is assigned to an attribute with the name of the alias or the model name
                 */
                $attribute = $column["balias"];
            } else {

                /**
                 * Scalar columns are simply assigned to the result object
                 */
                if (isset($column["sqlAlias"])) {
                    $value = $row[$column["sqlAlias"]];
                } else {
                    $value = isset($row[$alias]) ? $row[$alias] : null;
                }

                /**
                 * If a "balias" is defined is not an unnamed scalar
                 */
                if (isset($column["balias"])) {
                    $attribute = $alias;
                } else {
                    $attribute = str_replace("_", "", $alias);
                }
            }

            if (!isset($column["eager"])) {
                /**
                 * Assign the instance according to the hydration type
                 */
                switch ($hydrateMode) {
                    case Resultset::HYDRATE_ARRAYS:
                        $activeRow[$attribute] = $value;
                        break;

                    default:
                        $activeRow->{attribute} = $value;
                        break;
                }
            }
        }

        /**
         * Store the generated row in this_ptr->activeRow to be retrieved by 'current'
         */
        $this->_activeRow = $activeRow;
        return $activeRow;
    }

    /**
     * Returns a complete resultset as an array, if the resultset has a big number of rows
     * it could consume more memory than currently it does.
     *
     * @return array
     */
    public function toArray()
    {
        $records = array();
        $this->rewind();
        while ($this->valid()) {
            $records[] = $this->current();
            $this->next();
        }

        return $records;
    }

    /**
     * Serializing a resultset will dump all related rows into a big array
     *
     * @return string|null
     */
    public function serialize()
    {
        return serialize([
            'cache'       => $this->_cache,
            'rows'        => $this->toArray(),
            'columnTypes' => $this->_columnTypes,
            'hydrateMode' => $this->_hydrateMode
        ]);
    }

    /**
     * Unserializing a resultset will allow to only works on the rows present in the saved state
     *
     * @param string $data
     * @throws Exception
     */
    public function unserialize($data)
    {
        if (is_string($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * Rows are already hydrated
         */
        $this->_disableHydration = true;

        $resultset = unserialize($data);

        if (is_array($resultset) === false) {
            throw new Exception('Invalid serialization data');
        }

        $this->_rows        = $resultset['rows'];
        $this->_count       = count($resultset["rows"]);
        $this->_cache       = $resultset['cache'];
        $this->_columnTypes = $resultset['columnTypes'];
        $this->_hydrateMode = $resultset['hydrateMode'];
    }

}
