<?php

namespace Phalcon;

use \Phalcon\Config\Exception as ConfigException;

/**
 * Phalcon\Config
 *
 * Phalcon\Config is designed to simplify the access to, and the use of, configuration data within applications.
 * It provides a nested object property based user interface for accessing this configuration data within
 * application code.
 *
 * <code>
 * $config = new \Phalcon\Config(
 *     [
 *         "database" => [
 *             "adapter"  => "Mysql",
 *             "host"     => "localhost",
 *             "username" => "scott",
 *             "password" => "cheetah",
 *             "dbname"   => "test_db",
 *         ],
 *         "phalcon" => [
 *             "controllersDir" => "../app/controllers/",
 *             "modelsDir"      => "../app/models/",
 *             "viewsDir"       => "../app/views/",
 *         ],
 *     ]
 * );
 * </code>
 */
class Config implements \ArrayAccess, \Countable
{

    const DEFAULT_PATH_DELIMITER = ".";

    protected static $_pathDelimiter;

    /**
     * \Phalcon\Config constructor
     *
     * @param array $arrayConfig
     * @throws ConfigException
     */
    public function __construct(array $arrayConfig = null)
    {
        if (is_array($arrayConfig) === false) {
            throw new ConfigException('The configuration must be an Array');
        }

        foreach ($arrayConfig as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Allows to check whether an attribute is defined using the array-syntax
     *
     * <code>
     * var_dump(isset($config['database']));
     * </code>
     *
     * @param scalar $index
     * @return boolean
     * @throws ConfigException
     */
    public function offsetExists($index)
    {
        $index = strval($index);

        return isset($this->{$index});
    }

    /**
     * Returns a value from current config using a dot separated path.
     *
     * <code>
     * echo $config->path("unknown.path", "default", ".");
     * </code>
     */
    public function path($path, $defaultValue = null, $delimiter = null)
    {
        if (!$path) {
            return null;
        }
        if (!is_string($path)) {
            throw new Exception('Invalid parameter type.');
        }
        if (isset($this->{$path})) {
            return $this->{$path};
        }

        if (empty($delimiter)) {
            $delimiter = self::getPathDelimiter();
        }

        $config = $this;
        $keys   = explode($delimiter, $path);

        while (!empty($keys)) {
            $key = array_shift($keys);

            if (!isset($config->{$key})) {
                break;
            }

            if (empty($keys)) {
                return $config->{$key};
            }

            $config = $config->{$key};

            if (empty($config)) {
                break;
            }
        }

        return $defaultValue;
    }

    /**
     * Gets an attribute from the configuration, if the attribute isn't defined returns null
     * If the value is exactly null or is not defined the default value will be used instead
     *
     * <code>
     * echo $config->get('controllersDir', '../app/controllers/');
     * </code>
     *
     * @param mixed $index
     * @param mixed $defaultValue
     * @return mixed
     * @throws ConfigException
     */
    public function get($index, $defaultValue = null)
    {
        $index = strval($index);
        return (isset($this->{$index}) === true ? $this->{$index} : $defaultValue);
    }

    /**
     * Gets an attribute using the array-syntax
     *
     * <code>
     * print_r($config['database']);
     * </code>
     *
     * @param scalar $index
     * @return mixed
     * @throws ConfigException
     */
    public function offsetGet($index)
    {
        return $this->get(strval($index));
    }

    /**
     * Sets an attribute using the array-syntax
     *
     * <code>
     * $config['database'] = array('type' => 'Sqlite');
     * </code>
     *
     * @param mixed $index
     * @param mixed $value
     */
    public function offsetSet($index, $value)
    {
        $index = strval($index);

        if (is_array($value)) {
            $this->{$index} = new self($value);
        } else {
            $this->{$index} = $value;
        }
    }

    /**
     * Unsets an attribute using the array-syntax
     *
     * <code>
     * unset($config['database']);
     * </code>
     *
     * @param mixed $index
     */
    public function offsetUnset($index)
    {
        $index = strval($index);

        //unset(this->{index});
        $this->{$index} = null;
    }

    /**
     * Merges a configuration into the current one
     *
     * @brief void \Phalcon\Config::merge(array|object $with)
     *
     * <code>
     *  $appConfig = new \Phalcon\Config(array('database' => array('host' => 'localhost')));
     *  $globalConfig->merge($config2);
     * </code>
     *
     * @param \Phalcon\Config|array $config
     * @throws Exception ConfigException
     */
    public function merge($config)
    {
        return $this->_merge($config);
    }

    /**
     * Converts recursively the object to an array
     *
     * @brief array \Phalcon\Config::toArray();
     *
     * <code>
     *  print_r($config->toArray());
     * </code>
     *
     * @return array
     */
    public function toArray()
    {
        $arrayConfig = [];
        $vars        = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, "toArray")) {
                    $arrayConfig[$key] = $value->toArray();
                } else {
                    $arrayConfig[$key] = $value;
                }
            } else {
                $arrayConfig[$key] = $value;
            }
        }
        return $arrayConfig;
    }

    /**
     * Returns the count of properties set in the config
     *
     * <code>
     * print count($config);
     * </code>
     *
     * or
     *
     * <code>
     * print $config->count();
     * </code>
     * 
     * @return int
     */
    public function count()
    {
        return count(get_object_vars($this));
    }

    /**
     * Restore data after unserialize()
     */
    public function __wakeup()
    {
        
    }

    /**
     * Restores the state of a \Phalcon\Config object
     *
     * @param array $data
     * @return \Phalcon\Config
     */
    public static function __set_state(array $data)
    {
        //@warning this function is not compatible with a direct var_export
        return new self($data);
    }

    /**
     * Sets the default path delimiter
     */
    public static function setPathDelimiter($delimiter = null)
    {
        self::$_pathDelimiter = $delimiter;
    }

    /**
     * Gets the default path delimiter
     */
    public static function getPathDelimiter()
    {
        $delimiter = self::$_pathDelimiter;
        if (!$delimiter) {
            $delimiter = self::DEFAULT_PATH_DELIMITER;
        }

        return $delimiter;
    }

    /**
     * Helper method for merge configs (forwarding nested config instance)
     *
     * @param Config config
     * @param Config instance = null
     *
     * @return Config merged config
     */
    protected final function _merge($config, $instance = null)
    {
        if (!$instance) {
            $instance = $this;
        }

        $number = $instance->count();

        foreach (get_object_vars($config) as $key => $value) {
            $property = strval($key);
            if (isset($instance->{$property})) {
                $localObject = $instance->{$property};
                if ($localObject && $value) {
                    if ($localObject instanceof Config && $value instanceof Config) {
                        $this->_merge($value, $localObject);
                        continue;
                    }
                }
            }

            if (is_numeric($key)) {
                $key = strval($number);
                $number++;
            }
            $instance->{$key} = $value;
        }

        return $instance;
    }

}
