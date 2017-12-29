<?php

/*
 +------------------------------------------------------------------------+
 | Phalcon Framework                                                      |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2017 Phalcon Team (https://phalconphp.com)          |
 +------------------------------------------------------------------------+
 | This source file is subject to the New BSD License that is bundled     |
 | with this package in the file LICENSE.txt.                             |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@phalconphp.com so we can send you a copy immediately.       |
 +------------------------------------------------------------------------+
 | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
 |          Eduar Carvajal <eduar@phalconphp.com>                         |
 |          Vladimir Kolesnikov <vladimir@extrememember.com>              |
 +------------------------------------------------------------------------+
 */

namespace Phalcon;

/**
 * Phalcon\Registry
 *
 * A registry is a container for storing objects and values in the application space.
 * By storing the value in a registry, the same object is always available throughout
 * your application.
 *
 *<code>
 * $registry = new \Phalcon\Registry();
 *
 * // Set value
 * $registry->something = "something";
 * // or
 * $registry["something"] = "something";
 *
 * // Get value
 * $value = $registry->something;
 * // or
 * $value = $registry["something"];
 *
 * // Check if the key exists
 * $exists = isset($registry->something);
 * // or
 * $exists = isset($registry["something"]);
 *
 * // Unset
 * unset($registry->something);
 * // or
 * unset($registry["something"]);
 *</code>
 *
 * In addition to ArrayAccess, Phalcon\Registry also implements Countable
 * (count($registry) will return the number of elements in the registry),
 * Serializable and Iterator (you can iterate over the registry
 * using a foreach loop) interfaces. For PHP 5.4 and higher, JsonSerializable
 * interface is implemented.
 *
 * Phalcon\Registry is very fast (it is typically faster than any userspace
 * implementation of the registry); however, this comes at a price:
 * Phalcon\Registry is a final class and cannot be inherited from.
 *
 * Though Phalcon\Registry exposes methods like __get(), offsetGet(), count() etc,
 * it is not recommended to invoke them manually (these methods exist mainly to
 * match the interfaces the registry implements): $registry->__get("property")
 * is several times slower than $registry->property.
 *
 * Internally all the magic methods (and interfaces except JsonSerializable)
 * are implemented using object handlers or similar techniques: this allows
 * to bypass relatively slow method calls.
 */
final class Registry implements \ArrayAccess, \Countable, \Iterator
{
    protected $_data;

    /**
     * Registry constructor
     */
    public final function __construct()
    {
        $this->_data = [];
    }

    /**
     * @param mixed $offset
     * @return bool
     * @throws Exception
     */
    public final function offsetExists($offset)
    {
        if (is_string($offset)) {
            throw new Exception('Invalid parameter type.');
        }
        return isset($this->_data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws Exception
     */
    public final function offsetGet($offset)
    {
        if (is_string($offset)) {
//            throw new Exception('Invalid parameter type.');
        }
        return $this->_data[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws Exception
     */
    public final function offsetSet($offset, $value)
    {
        if (is_string($offset)) {
        //    throw new Exception('Invalid parameter type.');
        }
        $this->_data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @throws Exception
     */
    public final function offsetUnset($offset)
    {
        if (is_string($offset)) {
        //    throw new Exception('Invalid parameter type.');
        }
        unset ($this->_data[$offset]);
    }

    /**
     * @return int
     */
    public final function count()
	{
        return count($this->_data);
	}

    /**
     *
     */
public final function next()
	{
        next($this->_data);
	}

    /**
     * @return int|null|string
     */
	public final function key()
	{
        return key($this->_data);
	}

    /**
     *
     */
	public final function rewind()
	{
        reset($this->_data);
	}

    /**
     * @return bool
     */
	public function valid()
	{
        return key($this->_data) !== null;
	}

    /**
     * @return mixed
     */
	public function current()
{
    return current($this->_data);
	}

    /**
     * @param $key
     * @param $value
     * @throws Exception
     */
	public final function __set($key,$value)
	{
        if (is_string($key)) {
        //    throw new Exception('Invalid parameter type.');
        }
        $this->offsetSet($key, $value);
	}

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     */
	public final function __get($key)
	{
        if (is_string($key)) {
//            throw new Exception('Invalid parameter type.');
        }
        return $this->offsetGet($key);
	}

    /**
     * @param $key
     * @return bool
     * @throws Exception
     */
	public final function __isset($key)
	{
        if (is_string($key)) {
            throw new Exception('Invalid parameter type.');
        }
        return $this->offsetExists($key);
	}

    /**
     * @param $key
     * @throws Exception
     */
	public final function __unset($key)
	{
        if (is_string($key)) {
            throw new Exception('Invalid parameter type.');
        }
        $this->offsetUnset($key);
	}
}
