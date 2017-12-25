<?php

namespace Phalcon\Translate;

use Phalcon\Translate\Exception;
use Phalcon\Translate\InterpolatorInterface;
use Phalcon\Translate\Interpolator;

/**
 * Phalcon\Translate\Adapter
 *
 * Base class for Phalcon\Translate adapters
 *
 */
abstract class Adapter implements AdapterInterface
{

    /**
     * @var
     */
    protected $_interpolator;

    /**
     * Adapter constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (!isset($options["interpolator"])) {
            $interpolator = new AssociativeArray();
        }
        $this->setInterpolator($interpolator);
    }

    /**
     * @param \Phalcon\Translate\InterpolatorInterface $interpolator
     * @return $this
     */
    public function setInterpolator(InterpolatorInterface $interpolator)
    {
        $this->_interpolator = $interpolator;
        return $this;
    }

    /**
     * Returns the translation string of the given key
     *
     * @param string  translateKey
     * @param array|null   placeholders
     * @return string
     */
    public function t($translateKey, array $placeholders = null)
    {
        return $this->query($translateKey, $placeholders);
    }

    /**
     * Returns the translation string of the given key
     *
     * @param string $translateKey
     * @param array|null $placeholders
     * @return string
     */
    public function _($translateKey, $placeholders = null)
    {
        return $this->query($translateKey, $placeholders);
    }

    /**
     * Sets a translation value
     *
     * @param string $offset
     * @param string $value
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        throw new Exception('Translate is an immutable ArrayAccess object');
    }

    /**
     * Check whether a translation key exists
     *
     * @param string $translateKey
     * @return boolean
     */
    public function offsetExists($translateKey)
    {
        return $this->exists($translateKey);
    }

    /**
     * Unsets a translation from the dictionary
     *
     * @param string $offset
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        throw new Exception('Translate is an immutable ArrayAccess object');
    }

    /**
     * Returns the translation related to the given key
     *
     * @param string $translateKey
     * @return string
     */
    public function offsetGet($translateKey)
    {
        return $this->query($translateKey, null);
    }

}
