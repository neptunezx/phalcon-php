<?php

namespace Phalcon\Session\Adapter;

use Phalcon\Session\Adapter;
use Phalcon\Cache\Backend\Memcache;
use Phalcon\Cache\Frontend\Data as FrontendData;

/**
 * Phalcon\Session\Adapter\Memcache
 *
 * This adapter store sessions in memcache
 *
 * <code>
 * use Phalcon\Session\Adapter\Memcache;
 *
 * $session = new Memcache(
 *     [
 *         "uniqueId"   => "my-private-app",
 *         "host"       => "127.0.0.1",
 *         "port"       => 11211,
 *         "persistent" => true,
 *         "lifetime"   => 3600,
 *         "prefix"     => "my_",
 *     ]
 * );
 *
 * $session->start();
 *
 * $session->set("var", "some-value");
 *
 * echo $session->get("var");
 * </code>
 */
class Memcache extends Adapter
{

    protected $_memcache = null;
    protected $_lifetime = 8600;

    /**
     * Phalcon\Session\Adapter\Memcache constructor
     */
    public function __construct(array $options = [])
    {
        $lifetime;

        if (!isset($options["host"])) {
            $options["host"] = "127.0.0.1";
        }

        if (!isset($options["port"])) {
            $options["port"] = 11211;
        }

        if (!isset($options["persistent"])) {
            $options["persistent"] = 0;
        }

        if (isset($options["lifetime"])) {
            $this->_lifetime = $options["lifetime"];
        }

        $this->_memcache = new Memcache(
            new FrontendData(["lifetime" => $this->_lifetime]), $options
        );

        session_set_save_handler(
            [$this, "open"], [$this, "close"], [$this, "read"], [$this, "write"], [$this, "destroy"], [$this, "gc"]
        );

        parent::__construct(options);
    }

    public function open()
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return (string) $this->_memcache->get($sessionId, $this->_lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        return$this->_memcache->save($sessionId, $data, $this->_lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId = null)
    {
        if ($sessionId === null) {
            $id = $this->getId();
        } else {
            $id = $sessionId;
        }

        $this->removeSessionData();

        if (!empty(id) && $this->_memcache->exists($id)) {
            return (bool) $this->_memcache->delete($id);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc()
    {
        return true;
    }

}
