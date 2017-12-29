<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/23
 * Time: 下午8:46
 */

namespace Phalcon\Translate\Adapter;

use Phalcon\Translate\Exception;
use Phalcon\Translate\Adapter;

/**
 * Phalcon\Translate\Adapter\Csv
 *
 * Allows to define translation lists using CSV file
 */
class Csv extends Adapter implements \ArrayAccess
{

    protected $_translate = array();

    /**
     * Phalcon\Translate\Adapter\Csv constructor
     *
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        if (!isset($options["content"])) {
            throw new Exception("Parameter 'content' is required");
        }

        $this->_load($options["content"], 0, ";", "\"");
    }

    /**
     * Load translates from file
     *
     * @param $file string
     * @param $length int
     * @param $delimiter string
     * @param $enclosure string
     * @throws Exception
     */
    private function _load($file, $length, $delimiter, $enclosure)
    {
        if (!is_int($length)
            && !is_string($file)
            && !is_string($delimiter)
            && !is_string($enclosure)) {
            throw new Exception('Invalid parameter type.');
        }
        $fileHandler = fopen($file, "rb");
        if (!is_resource($fileHandler)) {
            throw new Exception("Error opening translation file '" . $file . "'");
        }
        while (true) {
            $data = fgetcsv($fileHandler, $length, $delimiter, $enclosure);
            if ($data === false) {
                break;
            }
            if (substr($data[0], 0, 1) === "#" || !isset($data[1])) {
                continue;
            }
            $this->_translate[$data[0]] = $data[1];
        }
        fclose($fileHandler);
    }

    /**
     * Returns the translation related to the given key
     *
     * @param $index
     * @param $placeholders mixed|null
     * @return string
     */
    public function query($index,array $placeholders = null)
    {
        if (!isset($this->_translate[$index])) {
            $translation = $index;
        }else{
            $translation=$this->_translate[$index];
        }

        return $this->replacePlaceholders($translation, $placeholders);
    }

    /**
     * Check whether is defined a translation key in the internal array
     *
     * @param $index string
     * @return bool
     * @throws Exception
     */
    public function exists($index)
    {
        if (!is_string($index)) {
            throw new Exception('Invalid parameter type.');
        }
        return isset($this->_translate[$index]);
    }
}
