<?php

namespace Phalcon\Translate\Adapter;

use \Phalcon\Translate\Adapter;
use \ArrayAccess;
use \Phalcon\Translate\AdapterInterface;
use \Phalcon\Translate\Exception;

/**
 * Phalcon\Translate\Adapter\NativeArray
 *
 * Allows to define translation lists using PHP arrays
 */
class NativeArray extends Adapter implements \ArrayAccess
{

    /**
     * Translate
     *
     * @var null|array
     * @access protected
     */
    protected $_translate;

    /**
     * \Phalcon\Translate\Adapter\NativeArray constructor
     *
     * @param array $options
     * @throws Exception
     */
    public function __construct($options)
    {
        parent::__construct($options);
        if (is_array($options) === false) {
            throw new Exception('Invalid options');
        }

        if (isset($options['content']) === false) {
            throw new Exception('Translation content was not provided');
        }

        if (is_array($options['content']) === false) {
            throw new Exception('Translation data must be an array');
        }

        $this->_translate = $options['content'];
    }

    /**
     * Returns the translation related to the given key
     *
     * @param string $index
     * @param $placeholders array|null
     * @return string
     * @throws Exception
     */
    public function query($index,array $placeholders = null)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($placeholders) === false &&
            is_null($placeholders) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_translate[$index]) === true) {
            $translation = $index;
        }
        return $this->replacePlaceholders($translation, $placeholders);
    }

    /**
     * Check whether is defined a translation key in the internal array
     *
     * @param string $index
     * @return boolean
     * @throws Exception
     */
    public function exists($index)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->_translate[$index]);
    }

}
