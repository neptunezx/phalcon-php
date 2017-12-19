<?php

namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\Exception;
use Phalcon\Cache\BackendInterface;
use \Redis as PhpRedis;

/**
 * Phalsky\Cache\Redis
 *
 * Allows to cache output fragments, PHP data or raw data to a redis master-slaves cluster backend
 *
 * This adapter uses the special redis key '_PHCR' to store all the keys internally used by the adapter
 * class Phalcon\Cache\Backend source address：https://github.com/phalcon/cphalcon/blob/master/phalcon/cache/backend.zep
 * 
 * @author ZhangXiang <zhangxiang-iri@360.cn>
 * 
 * <code>
 * use Phalcon\Cache\Frontend\Data as FrontData;
 *
 * // Cache data for 2 days
 * $frontCache = new FrontData([
 *     'lifetime' => 172800
 * ]);
 *
 * // Create the Cache setting redis connection $options
 * $cache = new Phalsky\Cache\Redis($frontCache, [
 *     'host' => 'localhost',
 *     'port' => 6379,
 *     'auth' => 'foobared',
 *     'persistent' => false
 *     'index' => 0,
 * ]);
 *
 * // Cache arbitrary data
 * $cache->save('my-data', [1, 2, 3, 4, 5]);
 *
 * // Get data
 * $data = $cache->get('my-data');
 * </code>
 * 
 * @todo: 某一slave不可读时，记录其状态，改为读其他slave或者master，使得本次操作可用
 */
class Redis extends Backend implements BackendInterface
{

    protected $_masterRedis     = null;
    protected $_slaveRedisArray = [];

    /**
     * Phalsky\Cache\Redis constructor
     *
     * @param	Phalcon\Cache\FrontendInterface frontend
     * @param	array $options
     */
    public function __construct($frontend, $options = null)
    {
        if (!is_array($options)) {
            $options = [];
        }

        if (!isset($options["host"])) {
            $options["host"] = "127.0.0.1";
        }

        if (!isset($options["port"])) {
            $options["port"] = 6379;
        }

        if (!isset($options['index'])) {
            $options['index'] = 0;
        }

        if (!isset($options["auth"])) {
            $options["auth"] = '';
        }

        if (!isset($options["persistent"])) {
            $options["persistent"] = false;
        }

        if (!isset($options["timeout"])) {
            $options["timeout"] = 60;
        }

        if (!isset($options["statsKey"])) {
            // Disable tracking of cached keys per default
            $options["statsKey"] = "";
        }

        if (!isset($options["enableSlave"])) {
            $options["enableSlave"] = false;
        }

        if (!isset($options["slaves"]) || !$options["slaves"]) {
            $options["slaves"] = [];
        }

        if (count($options["slaves"]) == 0) {
            $options["enableSlave"] = false;
        }

        parent::__construct($frontend, $options);
    }

    /**
     * Create new internal connection to redis
     */
    protected function getConnection($redisOptions)
    {
        $redis   = new PhpRedis();
        $options = $redisOptions;
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : 0;
        if ($options['persistent']) {
            if ($timeout > 0) {
                $success = $redis->pconnect($options['host'], $options['port'], $timeout);
            } else {
                $success = $redis->pconnect($options['host'], $options['port']);
            }
        } else {
            if ($timeout > 0) {
                $success = $redis->connect($options['host'], $options['port'], $timeout);
            } else {
                $success = $redis->connect($options['host'], $options['port']);
            }
        }

        if (!$success) {
            throw new Exception("Could not connect to the Redisd server " . $options['host'] . ":" . $options['port']);
        }

        if (isset($options['auth']) && $options['auth'] != '') {
            $success = $redis->auth($options['auth']);

            if (!$success) {
                throw new Exception("Failed to authenticate with the Redisd server");
            }
        }

        if (isset($options['index'])) {
            $success = $redis->select($options['index']);

            if (!$success) {
                throw new Exception("Redisd server selected database failed");
            }
        }

        return $redis;
    }

    /**
     * 随机hash出一个0～$slaveCount-1之间的值
     *
     * @param int $m
     * @return int
     * @todo slave的选择算法需要优化
     */
    private function _hashId($m)
    {
        if ($m <= 1) {
            return 0;
        }
        //把字符串K转换为 0～$m-1之间的一个值作为对应记录的散列地址
        $k = md5(mt_rand());
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for ($i = 0; $i < $l; $i++) {
            $h += substr($b, $i * 2, 2);
        }
        $hash = ($h * 1) % $m;
        return $hash;
    }

    protected function getSlave()
    {
        if ($this->_options['enableSlave']) {
            $slaveCount = count($this->_options['slaves']);
            if ($slaveCount == 0) {
                $this->_options['enableSlave'] = false;
                return $this->getMaster();
            } else {
                $id = ($slaveCount == 1) ? 0 : $this->_hashId($slaveCount);
                if (!isset($this->_slaveRedisArray[$id]) || $this->_slaveRedisArray[$id] == null) {
                    try {
                        $this->_slaveRedisArray[$id] = $this->getConnection($this->_options['slaves'][$id]);
                    } catch (Exception $ex) {
                        //TODO: slave不可读时的异常处理
                        throw $ex;
                    }
                }
                return $this->_slaveRedisArray[$id];
            }
        } else {
            return $this->getMaster();
        }
    }

    protected function getMaster()
    {
        if ($this->_masterRedis == null) {
            $this->_masterRedis = $this->getConnection($this->_options);
        }
        return $this->_masterRedis;
    }

    /**
     * Returns a cached content
     */
    public function get($keyName, $lifetime = null)
    {
        $lastKey        = '_PHCR' . $this->_prefix . $keyName;
        $this->_lastKey = $lastKey;
        $cachedContent  = $this->getSlave()->get($lastKey);

        if (is_numeric($cachedContent)) {
            return $cachedContent;
        }

        if (!$cachedContent) {
            return null;
        }

        return $this->_frontend->afterRetrieve($cachedContent);
    }

    /**
     * Stores cached content into the file backend and stops the frontend
     *
     * @param int|$keyName
     * @param $content
     * @param long lifetime
     * @param boolean stopBuffer
     */
    public function set($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
    {
        return $this->save($keyName, $content, $lifetime, $stopBuffer);
    }

    /**
     * 数据入队列
     * 
     * @param string $keyName KEY名称
     * @param string|array $content 获取得到的数据
     * @param long lifetime
     * @param bool $right 是否从右边开始入
     */
    public function push($keyName = null, $content = null, $lifetime = null, $right = true)
    {
        if ($keyName === null) {
            $lastKey     = $this->_lastKey;
            $prefixedKey = substr(lastKey, 5);
        } else {
            $prefixedKey    = $this->_prefix . $keyName;
            $lastKey        = '_PHCR' . $prefixedKey;
            $this->_lastKey = $lastKey;
        }

        if (!$lastKey) {
            throw new Exception("The cache must be started first");
        }
        $frontend = $this->_frontend;
        if ($lifetime === null) {
            $tmp = $this->_lastLifetime;

            if (!$tmp) {
                $tt1 = $frontend->getLifetime();
            } else {
                $tt1 = $tmp;
            }
        } else {
            $tt1 = $lifetime;
        }

        $redis   = $this->getMaster();
        $success = $right ? $redis->rPush($lastKey, $content) : $redis->lPush($lastKey, $content);

        if (!$success) {
            throw new Exception("Failed storing the data in redis");
        }
        return $success;
//        $content = json_encode($content);
//        return $right ? $this->redis->rPush($keyName, $content) : $this->redis->lPush($keyName, $content);
    }

    /**
     * Stores cached content into the file backend and stops the frontend
     *
     * @param int|$keyName
     * @param $content
     * @param long lifetime
     * @param boolean stopBuffer
     */
    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
    {

        if ($keyName === null) {
            $lastKey     = $this->_lastKey;
            $prefixedKey = substr($lastKey, 5);
        } else {
            $prefixedKey    = $this->_prefix . $keyName;
            $lastKey        = '_PHCR' . $prefixedKey;
            $this->_lastKey = $lastKey;
        }

        if (!$lastKey) {
            throw new Exception("The cache must be started first");
        }

        $frontend = $this->_frontend;
        if ($content === null) {
            $cachedContent = $frontend->getContent();
        } else {
            $cachedContent = $content;
        }

        /**
         * Prepare the content in the frontend
         */
        if (!is_numeric($cachedContent)) {
            $preparedContent = $frontend->beforeStore($cachedContent);
        } else {
            $preparedContent = $cachedContent;
        }

        if ($lifetime === null) {
            $tmp = $this->_lastLifetime;

            if (!$tmp) {
                $tt1 = $frontend->getLifetime();
            } else {
                $tt1 = $tmp;
            }
        } else {
            $tt1 = $lifetime;
        }

        $redis   = $this->getMaster();
        $success = $redis->set($lastKey, $preparedContent);

        if (!$success) {
            throw new Exception("Failed storing the data in redis");
        }

        $redis->settimeout($lastKey, $tt1);

        $options = $this->_options;

        if (!isset($options["statsKey"])) {
            throw new Exception("Unexpected inconsistency in options");
        }
        $specialKey = $options["statsKey"];

        if ($specialKey != "") {
            $redis->sAdd($specialKey, $prefixedKey);
        }

        $isBuffering = $frontend->isBuffering();

        if ($stopBuffer === true) {
            $frontend->stop();
        }

        if ($isBuffering === true) {
            echo $cachedContent;
        }

        $this->_started = false;

        return $success;
    }

    /**
     * Deletes a value from the cache by its key
     *
     * @param int|$keyName
     */
    public function delete($keyName)
    {
        $redis       = $this->getMaster();
        $prefix      = $this->_prefix;
        $prefixedKey = $prefix . $keyName;
        $lastKey     = '_PHCR' . $prefixedKey;
        $options     = $this->_options;

        if (!isset($options["statsKey"])) {
            throw new Exception("Unexpected inconsistency in options");
        }
        $specialKey = $options["statsKey"];

        if ($specialKey != "") {
            $redis->sRem($specialKey, $prefixedKey);
        }

        /**
         * Delete the key from redis
         */
        return (bool) $redis->delete($lastKey);
    }

    /**
     * Query the existing cached keys
     *
     * @param $prefix
     */
    public function queryKeys($prefix = null)
    {
        $redis = $this->getSlave();

        $options = $this->_options;

        if (!isset($options["statsKey"])) {
            throw new Exception("Unexpected inconsistency in options");
        }
        $specialKey = $options["statsKey"];

        if ($specialKey == "") {
            throw new Exception('Cached keys need to be enabled to use this function (options[\'statsKey\'] == \'_PHCM\')!\')!');
        }

        /**
         * Get the key from redis
         */
        $keys = $redis->sMembers($specialKey);
        if (is_array($keys)) {
            foreach ($keys as $key => $value) {
                if ($prefix && strpos($value, $prefix) !== 0) {
                    unset($keys[$key]);
                }
            }
            return $keys;
        }

        return [];
    }

    /**
     * Checks if cache exists and it isn'xpired
     *
     * @param $keyName
     * @param   long lifetime
     * @return boolean
     */
    public function exists($keyName = null, $lifetime = null)
    {
        if (!$keyName) {
            $lastKey = $this->_lastKey;
        } else {
            $prefix  = $this->_prefix;
            $lastKey = '_PHCR' . $prefix . $keyName;
        }

        if ($lastKey) {
            if (!$this->getSlave()->get($lastKey)) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Increment of given $keyName by $value
     *
     * @param $keyName
     * @param long value
     */
    public function increment($keyName = null, $value = null)
    {
        $redis = $this->getMaster();

        if (!$keyName) {
            $lastKey = $this->_lastKey;
        } else {
            $prefix         = $this->_prefix;
            $lastKey        = '_PHCR' . $prefix . $keyName;
            $this->_lastKey = $lastKey;
        }

        if (!$value) {
            $value = 1;
        }

        return $redis->incrBy($lastKey, $value);
    }

    /**
     * Decrement of $keyName by given $value
     *
     * @param $keyName
     * @param long value
     */
    public function decrement($keyName = null, $value = null)
    {

        $redis = $this->getMaster();

        if (!$keyName) {
            $lastKey = $this->_lastKey;
        } else {
            $prefix         = $this->_prefix;
            $lastKey        = '_PHCR' . $prefix . $keyName;
            $this->_lastKey = $lastKey;
        }

        if (!$value) {
            $value = 1;
        }

        return $redis->decrBy($lastKey, $value);
    }

    /**
     * Immediately invalidates all existing items.
     */
    public function flush()
    {
        $redis   = $this->getMaster();
        $options = $this->_options;

        if (!isset($options["statsKey"])) {
            throw new Exception("Unexpected inconsistency in options");
        }
        $specialKey = $options["statsKey"];
        if ($specialKey == "") {
            throw new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCM')!");
        }

        $keys = $redis->sMembers($specialKey);
        if (is_array($keys)) {
            foreach ($keys as $key => $value) {
                $lastKey = '_PHCR' . $key;
                $redis->sRem($specialKey, $key);
                $redis->delete($lastKey);
            }
        }

        return true;
    }

    /**
     * 添加Hash类型的数据
     *
     * @param string $keyName   Key值
     * @param string $field     字段
     * @param $content          内容
     * @param long lifetime     过期时间
     * @param boolean stopBuffer
     */
    public function hashSet($keyName = null, $field = null, $content = null, $lifetime = null)
    {

        if ($keyName === null) {
            $lastKey     = $this->_lastKey;
            $prefixedKey = substr(lastKey, 5);
        } else {
            $prefixedKey    = $this->_prefix . $keyName;
            $lastKey        = '_PHCR' . $prefixedKey;
            $this->_lastKey = $lastKey;
        }

        if (!$lastKey) {
            throw new Exception("The cache must be started first");
        }

        $frontend = $this->_frontend;
        if ($content === null) {
            $cachedContent = $frontend->getContent();
        } else {
            $cachedContent = $content;
        }

        /**
         * Prepare the content in the frontend
         */
        if (!is_numeric($cachedContent)) {
            $preparedContent = $frontend->beforeStore($cachedContent);
        } else {
            $preparedContent = $cachedContent;
        }

        if ($lifetime === null) {
            $tmp = $this->_lastLifetime;

            if (!$tmp) {
                $tt1 = $frontend->getLifetime();
            } else {
                $tt1 = $tmp;
            }
        } else {
            $tt1 = $lifetime;
        }

        $redis   = $this->getMaster();
        $success = $redis->hset($lastKey, $field, $content);
        $redis->expire($lastKey, $lifetime);

        return $success;
    }

    /**
     * Returns a cached content
     */
    public function hashGet($keyName, $field = null, $lifetime = null)
    {
        $lastKey        = '_PHCR' . $this->_prefix . $keyName;
        $this->_lastKey = $lastKey;
        $cachedContent  = $this->getSlave()->hget($lastKey, $field);
        return $cachedContent;
    }

    /**
     * Returns a cached content
     */
    public function hashGetAll($keyName)
    {
        $lastKey        = '_PHCR' . $this->_prefix . $keyName;
        $this->_lastKey = $lastKey;
        $cachedContent  = $this->getSlave()->hgetall($lastKey);
        return $cachedContent;
    }

}
