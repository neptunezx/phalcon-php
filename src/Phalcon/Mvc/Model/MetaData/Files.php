<?php

namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Kernel;

/**
 * Phalcon\Mvc\Model\MetaData\Files
 *
 * Stores model meta-data in PHP files.
 *
 * <code>
 * $metaData = new \Phalcon\Mvc\Model\Metadata\Files(
 *     [
 *         "metaDataDir" => "app/cache/metadata/",
 *     ]
 * );
 * </code>
 */
class Files extends MetaData
{

    protected $_metaDataDir = "./";
    protected $_metaData    = [];

    /**
     * \Phalcon\Mvc\Model\MetaData\Files constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if ($options && isset($options['metaDataDir'])) {
            $this->_metaDataDir = $options['metaDataDir'];
        }
    }

    /**
     * Reads meta-data from files
     *
     * @param string $key
     * @return array
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $path = $this->_metaDataDir . Kernel::prepareVirtualPath($key, "_") . ".php";
        if (file_exists($path)) {
            return require $path;
        }
        return null;
    }

    /**
     * Writes the meta-data to files
     *
     * @param string $key
     * @param array $data
     * @throws Exception
     */
    public function write($key, $data)
    {
        if (is_string($key) === false ||
            is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $path = $this->_metaDataDir . Kernel::prepareVirtualPath($key, '_') . '.php';
        if (file_put_contents($path, '<?php return ' . var_export($data, true) . '; ') === false) {
            throw new Exception('Meta-Data directory cannot be written');
        }
    }

}
