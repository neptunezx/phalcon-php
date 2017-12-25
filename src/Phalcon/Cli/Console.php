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
 |          Rack Lin <racklin@gmail.com>                                  |
 +------------------------------------------------------------------------+
 */

namespace Phalcon\Cli;

use Phalcon\Application as BaseApplication;
use Phalcon\Cli\Router\Route;
use Phalcon\Events\ManagerInterface;
use Phalcon\Cli\Console\Exception;
/**
 * Phalcon\Cli\Console
 *
 * This component allows to create CLI applications using Phalcon
 */
class Console extends BaseApplication
{

    protected $_arguments = [];

    protected $_options = [];

    /**
     * @param array $modules
     * @return BaseApplication
     */
    public function addModules($modules)
    {
        return $this->registerModules($modules, true);
    }

    /**
     * Handle the whole command-line tasks
     * @param array |null $arguments
     * @throws Exception
     * @return mixed
     */
    public function handle($arguments = null)
    {
        $className = null;
        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) === false) {
            throw new Exception("A dependency injection object is required to access internal services");
        }
        if (is_object($this->_eventsManager) === false ||
            !$this->_eventsManager instanceof ManagerInterface) {
            throw new Exception('Invalid annotations reader');
        }
        $eventsManager = $this->_eventsManager;
        if (is_object($eventsManager)) {
            if ($eventsManager->Manger->fire('console:boot', $this) === false) {
                return false;
            }
        }
        if (is_object($dependencyInjector->getShared('router')) === false ||
            !$dependencyInjector->getShared('router') instanceof Router) {
            throw new Exception('Invalid annotations reader');
        }
        $router = $dependencyInjector->getShared('router');
        if (!count($arguments) && $this->_arguments) {
            $router->handle($this->_arguments);
        } else {
            $router->handle($arguments);
        }
        $moduleName = $router->getModuleName();
        if (!$moduleName) {
            $moduleName = $this->_defaultModule;
        }
        if ($moduleName) {
            if (is_object($eventsManager)) {
                if ($eventsManager->Manager->fire('console:beforeStartModule', $this, $moduleName) === false) {
                    return false;
                }
            }
            $modules = $this->_modules;
            if (!isset($modules[$moduleName])) {
                throw new Exception("Module '" . $moduleName . "' isn't registered in the console container");
            }
            $module = $modules[$moduleName];
            if (is_array($module) === false) {
                throw new Exception('Invalid module definition path');
            }
            if (isset($module['path'])) {
                $path = $module['path'];
                if (!file_exists($path)) {
                    throw new Exception("Module definition path '" . $path . "' doesn't exist");
                }
                require $path;
            }
            if (!isset($module['className'])) {
                $className = 'Module';
            }
            $moduleObject = $dependencyInjector->get($className);
            $moduleObject->registerAutoloaders();
            $moduleObject->registerServices($dependencyInjector);
            if (is_object($eventsManager)) {
                if ($eventsManager->Manager->fire('console:afterStarModule', $this, $moduleObject) === false) {
                    return false;
                }
            }

        }
        if (is_object($dependencyInjector->getShared("dispatcher")) === false ||
            $dependencyInjector->getShared("dispatcher") instanceof Dispatcher) {
            throw new Exception('Invalid module definition path');
        }
        $dispatcher = $dependencyInjector->getShared("dispatcher");
        $dispatcher->setTaskName($router->getTaskName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($router->getParams());
        $dispatcher->setOptions($this->_options);
        if (is_object($eventsManager)) {
            if ($eventsManager->Manager->fire('console:beforeHandleTask', $this, $dispatcher) === false) {
                return false;
            }
        }
        $task = $dispatcher->dispatch();
        if (is_object($eventsManager)) {
            $eventsManager->Manager->fire('console:afterHandleTask', $this, $task);
        }
        return $task;
    }

    /**
     * Set an specific argument
     * @param array |null $arguments
     * @param boolean $str
     * @param boolean $shift
     * @return Console
     * @throws Exception
     */
    public function setArgument($arguments = null, $str = true, $shift = true)
    {
        if (is_array($arguments) === false ||
            is_bool($str) || is_bool($shift)) {
            throw new Exception('Invalid module definition path');
        }
        $args = [];
        $opts = [];
        $handleArgs = [];
        if ($shift&&count($arguments)){
            array_shift($arguments);
        }
        foreach ($arguments as $arg){
            if (is_string($arg)){
                if (strncmp($arg,'--',2)==0){
                    $pos = strpos($arg,'=');
                    if ($pos){
                        $opts[trim(substr($arg,2,$pos-2))] = trim(substr($arg,$pos+1));
                    }else{
                        $opts[trim(substr($arg,2))] = true;
                    }
                }else{
                    if (strncmp($arg,'-',1)==0){
                        $opts[substr($arg,1)] =true;
                    }else{
                        $args[] = $arg;
                    }
                }
            }else{
                $args[] = $arg;
            }
        }
        if ($str){
            $this->_arguments = implode(Route::getDelimiter(),$args);
        }else{
            if (count($args)){
                $handleArgs['task'] = array_shift($args);
            }
            if (count($args)){
                $handleArgs['action'] = array_shift($args);
            }
            if (count($args)){
                $handleArgs = array_merge($handleArgs,$args);
            }
            $this->_arguments = $handleArgs;
        }
        $this->_options = $opts;
        return $this;
    }
}

