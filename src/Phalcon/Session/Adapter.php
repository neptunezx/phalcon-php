<?php

namespace Phalcon\Session;

use \Phalcon\Session\Exception;
use \Phalcon\Text;

/**
 * Phalcon\Session\Adapter
 *
 * Base class for Phalcon\Session adapters
 */
abstract class Adapter implements AdapterInterface
{

    const SESSION_ACTIVE   = 2;
    const SESSION_NONE     = 1;
    const SESSION_DISABLED = 0;

    /**
     * Unique ID
     *
     * @var null|string
     * @access protected
     */
    protected $_uniqueId;

    /**
     * Started
     *
     * @var boolean
     * @access protected
     */
    protected $_started = false;

    /**
     * Options
     *
     * @var null|array
     * @access protected
     */
    protected $_options;

    /**
     * \Phalcon\Session\Adapter constructor
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (is_array($options) === true) {
            $this->setOptions($options);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->_started === true) {
            session_write_close();
            $this->_started = false;
        }
    }

    /**
     * Starts the session (if headers are already sent the session will not be started)
     *
     * @return boolean
     */
    public function start()
    {
        if (headers_sent() === false) {
            if (!$this->_started && $this->status() !== self::SESSION_ACTIVE) {
                session_start();
                $this->_started = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Sets session's options
     *
     * <code>
     *  $session->setOptions(array(
     *      'uniqueId' => 'my-private-app'
     *  ));
     * </code>
     *
     * @param array $options
     * @throws Exception
     */
    public function setOptions(array $options)
    {
        if (is_array($options) === false) {
            throw new Exception('Options must be an Array');
        }

        if (isset($options['uniqueId']) === true) {
            //@note no type check
            $this->_uniqueId = $options['uniqueId'];
        }

        $this->_options = $options;
    }

    /**
     * Get internal options
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Set session name
     * 
     * @param string $name
     * @return string
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new Exception("invalid parameter type.");
        }
        session_name($name);
    }

    /**
     * Get session name
     * 
     * @return string
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateId($deleteOldSession = true)
    {
        session_regenerate_id($deleteOldSession);
        return $this;
    }

    /**
     * Gets a session variable from an application context
     *
     * @param string $index
     * @param mixed $defaultValue
     * @param boolean $remove
     * @return mixed
     * @throws Exception
     */
    public function get($index, $defaultValue = null, $remove = false)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $uniqueId = $this->_uniqueId;
        if (!empty($uniqueId)) {
            $key = $uniqueId . "#" . $index;
        } else {
            $key = $index;
        }

        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            if ($remove) {
                unset($_SESSION[$key]);
            }
            return $value;
        }

        return $defaultValue;
    }

    /**
     * Sets a session variable in an application context
     *
     * <code>
     *  $session->set('auth', 'yes');
     * </code>
     *
     * @param string $index
     * @param mixed $value
     * @throws Exception
     */
    public function set($index, $value)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $uniqueId = $this->_uniqueId;
        if (!empty($uniqueId)) {
            $_SESSION[$uniqueId . "#" . $index] = $value;
            return;
        }

        $_SESSION[$index] = $value;
    }

    /**
     * Check whether a session variable is set in an application context
     *
     * <code>
     *  var_dump($session->has('auth'));
     * </code>
     *
     * @param string $index
     * @return boolean
     * @throws Exception
     */
    public function has($index)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $uniqueId = $this->_uniqueId;
        if (!empty($uniqueId)) {
            return isset($_SESSION[$uniqueId . "#" . $index]);
        }

        return isset($_SESSION[$index]);
    }

    /**
     * Removes a session variable from an application context
     *
     * <code>
     *  $session->remove('auth');
     * </code>
     *
     * @param string $index
     * @throws Exception
     */
    public function remove($index)
    {
        if (is_string($index) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $uniqueId = $this->_uniqueId;
        if (!empty($uniqueId)) {
            unset($_SESSION[$uniqueId . "#" . $index]);
            return;
        }

        unset($_SESSION[$index]);
    }

    /**
     * Returns active session id
     *
     * <code>
     *  echo $session->getId();
     * </code>
     *
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Set the current session id
     *
     * <code>
     * $session->setId($id);
     * </code>
     * 
     * @param string $id
     */
    public function setId($id)
    {
        session_id($id);
    }

    /**
     * Check whether the session has been started
     *
     * <code>
     *  var_dump($session->isStarted());
     * </code>
     *
     * @return boolean|null
     */
    public function isStarted()
    {
        return $this->_started;
    }

    /**
     * Destroys the active session
     *
     * <code>
     *  var_dump($session->destroy());
     * </code>
     *
     * @param boolean $removeData
     * @return boolean
     */
    public function destroy($removeData = false)
    {
        if ($removeData) {
            $this->removeSessionData();
        }
        $this->_started = false;
        //@note no return value check
        session_destroy();
        return true;
    }

    /**
     * Returns the status of the current session.
     *
     * <code>
     * var_dump(
     *     $session->status()
     * );
     *
     * if ($session->status() !== $session::SESSION_ACTIVE) {
     *     $session->start();
     * }
     * </code>
     * 
     * @return int 
     */
    public function status()
    {

        $status = session_status();

        switch ($status) {
            case PHP_SESSION_DISABLED:
                return self::SESSION_DISABLED;

            case PHP_SESSION_ACTIVE:
                return self::SESSION_ACTIVE;
        }

        return self::SESSION_NONE;
    }

    /**
     * Alias: Gets a session variable from an application context
     * 
     * @param string $index
     * @return object 
     */
    public function __get($index)
    {
        return $this->get($index);
    }

    /**
     * Alias: Sets a session variable in an application context
     */
    public function __set($index, $value)
    {
        return $this->set($index, $value);
    }

    /**
     * Alias: Check whether a session variable is set in an application context
     * 
     * @param string $index
     * @return boolean
     */
    public function __isset($index)
    {
        return $this->has($index);
    }

    /**
     * Alias: Removes a session variable from an application context
     *
     * <code>
     * unset($session->auth);
     * </code>
     * @param string $index
     */
    public function __unset($index)
    {
        $this->remove($index);
    }

    protected function removeSessionData()
    {
        $uniqueId = $this->_uniqueId;

        if (empty($_SESSION)) {
            return;
        }

        if (!empty($uniqueId)) {
            foreach ($_SESSION as $key => $_) {
                if (Text::startsWith($key, $uniqueId . "#")) {
                    unset($_SESSION[$key]);
                }
            }
        } else {
            $_SESSION = [];
        }
    }

}
