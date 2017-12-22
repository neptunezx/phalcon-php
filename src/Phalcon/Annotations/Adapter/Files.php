<?php

namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Reflection;
use Phalcon\Annotations\Exception;
/**
 * Phalcon\Annotations\Adapter\Files
 *
 * Stores the parsed annotations in files. This adapter is suitable for production
 *
 *<code>
 * use Phalcon\Annotations\Adapter\Files;
 *
 * $annotations = new Files(
 *     [
 *         "annotationsDir" => "app/cache/annotations/",
 *     ]
 * );
 *</code>
 */
class Files extends Adapter
{

    protected $_annotationsDir = './';

    /**
     * Phalcon\Annotations\Adapter\Files constructor
     *
     * @param  $options | null
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (isset($options['annotationsDir'])) {
                $this->_annotationsDir = $options['annotationsDir'];
            }
        }

    }

    /**
     * Normalize Path
     *
     * @param string $key
     * @param string $virtualSeperator
     * @return string
     */
    private function prepareVirtualPath($key, $virtualSeperator)
    {
        $keylen = strlen($key);
        for ($i = 0; $i < $keylen; ++$i) {
            $c = $key[$i];
            if ($c === '/' || $c === '\\' || $c === ':' || ctype_print($c) === false) {
                $key[$i] = $virtualSeperator;
            }
        }

        return strtolower($key);
    }

    /**
     * Reads parsed annotations from files
     *
     * @param string $key
     * @return Reflection | boolean |int
     * @throws Exception
     */
    public function read($key)
    {
        if (is_string($key) === false) {
            throw new Exception('Invalid parameter type.');
        }
        /**
         * Paths must be normalized before be used as keys
         */
        $path = $this->_annotationsDir . $this->prepareVirtualPath($key, "_") . ".php";
        if (file_exists($path)){
            return require($path);
        }

        return false;
    }

    /**
     * Writes parsed annotations to files
     *
     * @param string $key
     * @param  Reflection \data
     * @throws Exception
     */
    public
    function write($key, $data)
    {
        if (is_string($key) === false ||
            is_object($data) === false ||
            $data instanceof Reflection === false) {
            throw new Exception('Invalid parameter type.');
        }

        $path = $this->_annotationsDir . $this->prepareVirtualPath($key, "_") . ".php";

        if (file_put_contents($path, "<?php return " . var_export($data, true) . "; ") === false) {
            throw new Exception('Annotations directory cannot be written');
        }
    }

}
