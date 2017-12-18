<?php

namespace Phalcon\Session\Adapter;

use Phalcon\Session\Adapter;
use Phalcon\Cache\Backend\Redis as RedisCache;
use Phalcon\Cache\Frontend\None as FrontendNone;

/**
 * Phalcon\Session\Redis
 *
 * This adapter stores session in redis, surpport redis master-slaves cluster
 *
 * @author ZhangXiang
 * 
 * <code>
 *
 * $session = new Phalcon\Session\Redis([
 *     'uniqueId'   => 'my-private-app',
 *     'host'        => '127.0.0.1',
 *     'port'        => 6379,
 *     'auth'        => '74c448dab46b',
 *     'persistent'  => false,
 *     'enableSlave' => true,
 *     'lifetime'    => 172800,
 *     'database'    => 0,
 *     'slaveConfig' => [
 *          'port'     => 6379,
 *          'database' => 0,
 *      ],
 *      'slaves'      => [
 *              [
 *              'host' => '10.173.30.43',
 *              'auth' => '74c448dab46b',
 *          ],
 *      ],
 * ]);
 *
 * $session->start();
 *
 * $session->set('var', 'some-value');
 *
 * echo $session->get('var');
 * </code>
 */
class Redis extends Adapter
{

    protected $_redis    = null;
    protected $_lifetime = 8600;

    /**
     * Phalsky\Web\RedisSession constructor
     */
    public function __construct($options)
    {
        if (!isset($options["host"])) {
            $options["host"] = "127.0.0.1";
        }
        if (!isset($options["port"])) {
            $options["port"] = 6379;
        }

        if (!isset($options["persistent"])) {
            $options["persistent"] = false;
        }

        if (isset($options["lifetime"]) && (int) $options["lifetime"] > 0) {
            $this->_lifetime = (int) $options["lifetime"];
        }

        //TODO:这里传入的options需要处理下
        $this->_redis = new RedisCache(new FrontendNone(["lifetime" => $this->_lifetime]), $options);

        //使得全局SESSION数组被设置时能够调用到这里的回调函数，从而达到用户自定义Session设置
        session_set_save_handler([$this, "open"], [$this, "close"], [$this, "read"], [$this, "write"], [$this, "dispose"], [$this, "gc"]);
        //register_shutdown_function('session_write_close'); 父类析构函数会调用session_write_close，这里就不需要了

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return $this->_redis->get($this->sessionKey($sessionId), $this->_lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        return $this->_redis->save($this->sessionKey($sessionId), $data, $this->_lifetime);
    }

    /**
     *  销毁redis中session变量
     *  当调用$session->destroy()时，父类的destroy方法会调用session_destroy()方法，进一步调用到本销毁函数
     */
    public function dispose($sessionId = null)
    {
        if ($sessionId === null) {
            $sessionId = $this->getId();
        }
        return $this->_redis->delete($this->sessionKey($sessionId));
    }

    /**
     * {@inheritdoc}
     */
    public function gc()
    {
        return true;
    }

    protected function sessionKey($sessionId)
    {
        return "session:$sessionId";
    }

}
