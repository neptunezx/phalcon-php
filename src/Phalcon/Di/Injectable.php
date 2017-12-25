<?php

namespace Phalcon\Di;

use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Di\Exception;
use Phalcon\Session\BagInterface;

/**
 * Phalcon\Di\Injectable
 *
 * This class allows to access services in the services container by just only accessing a public property
 * with the same name of a registered service
 *
 * @property \Phalcon\Mvc\Dispatcher|\Phalcon\Mvc\DispatcherInterface $dispatcher
 * @property \Phalcon\Mvc\Router|\Phalcon\Mvc\RouterInterface $router
 * @property \Phalcon\Mvc\Url|\Phalcon\Mvc\UrlInterface $url
 * @property \Phalcon\Http\Request|\Phalcon\Http\RequestInterface $request
 * @property \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface $response
 * @property \Phalcon\Http\Response\Cookies|\Phalcon\Http\Response\CookiesInterface $cookies
 * @property \Phalcon\Filter|\Phalcon\FilterInterface $filter
 * @property \Phalcon\Session\Adapter\Files|\Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface $session
 * @property \Phalcon\Events\Manager|\Phalcon\Events\ManagerInterface $eventsManager
 * @property \Phalcon\Db\AdapterInterface $db
 * @property \Phalcon\Security $security
 * @property \Phalcon\Crypt|\Phalcon\CryptInterface $crypt
 * @property \Phalcon\Escaper|\Phalcon\EscaperInterface $escaper
 * @property \Phalcon\Annotations\Adapter\Memory|\Phalcon\Annotations\Adapter $annotations
 * @property \Phalcon\Mvc\Model\Manager|\Phalcon\Mvc\Model\ManagerInterface $modelsManager
 * @property \Phalcon\Mvc\Model\MetaData\Memory|\Phalcon\Mvc\Model\MetadataInterface $modelsMetadata
 * @property \Phalcon\Mvc\Model\Transaction\Manager|\Phalcon\Mvc\Model\Transaction\ManagerInterface $transactionManager
 * @property \Phalcon\Di|\Phalcon\DiInterface $di
 * @property \Phalcon\Session\Bag|\Phalcon\Session\BagInterface $persistent
 * @property \Phalcon\Mvc\View|\Phalcon\Mvc\ViewInterface $view
 */
abstract class Injectable implements InjectionAwareInterface, EventsAwareInterface
{

    /**
     * Dependency Injector
     *
     * @var null|\Phalcon\DiInterface
     * @access protected
     */
    protected $_dependencyInjector;

    /**
     * Events Manager
     *
     * @var null|\Phalcon\Events\ManagerInterface
     * @access protected
     */
    protected $_eventsManager;

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     * @throws Exception
     */
    public function setDI(DiInterface $dependencyInjector)
    {

        if ($dependencyInjector instanceof DiInterface === false) {
            $this->_throwDispatchException('Invalid parameter type.');
            return null;
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface|null
     */
    public function getDI()
    {
        if (is_object($this->_dependencyInjector)) {
            return $this->_dependencyInjector;
        } else {
            return DI::getDefault();
        }
    }

    /**
     * Sets the event manager
     *
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     * @throws Exception
     */
    public function setEventsManager(ManagerInterface $eventsManager)
    {
        if (is_object($eventsManager) === false ||
            $eventsManager instanceof ManagerInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     *
     * @return \Phalcon\Events\ManagerInterface|null
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * Magic method __get
     *
     * @param string $propertyName
     * @return mixed
     */
    public function __get($propertyName)
    {
        if (is_string($propertyName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) === false) {
            $dependencyInjector = DI::getDefault();

            if (is_object($dependencyInjector) === false) {
                throw new Exception('A dependency injector object is required to access the application services');
            }
        }

        //Fallback to the PHP userland if the cache is not available
        if ($dependencyInjector->has($propertyName) === true) {
            $service             = $dependencyInjector->getShared($propertyName);
            $this->$propertyName = $service;
            return $service;
        }

        //Dependency Injector
        if ($propertyName === 'di') {
            $this->di = $dependencyInjector;
            return $dependencyInjector;
        }

        //Accessing the persistent property will create a session bag in any class
        if ($propertyName === 'persistent') {
            $persistent       = $dependencyInjector->get('sessionBag', array(get_class($this)));
            $this->persistent = $persistent;
            return $persistent;
        }

        //A notice is shown if the property is not defined and isn't a valid service
        trigger_error('Access to undefined property ' . $propertyName, \E_USER_WARNING);
    }

}
