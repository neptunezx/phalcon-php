<?php

namespace Phalcon\Cli;

use Phalcon\Application as BaseApplication;
use Phalcon\DiInterface;
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
    protected $_options   = [];

    /**
     * Merge modules with the existing ones
     *
     * <code>
     * $application->addModules(
     *     [
     *         "admin" => [
     *             "className" => "Multiple\\Admin\\Module",
     *             "path"      => "../apps/admin/Module.php",
     *         ],
     *     ]
     * );
     * </code>
     */
    public function addModules(array $modules)
    {
        return $this->registerModules($modules, true);
    }

    /**
     * Handle the whole command-line tasks
     * @param array |null $arguments
     * @throws Exception
     * @return mixed
     */
    public function handle(array $arguments = null)
    {
        $className          = null;
        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) === false) {
            throw new Exception("A dependency injection object is required to access internal services");
        }
        $eventsManager = $this->_eventsManager;
        if (is_object($eventsManager) && $eventsManager instanceof ManagerInterface) {
            if ($eventsManager->Manger->fire('console:boot', $this) === false) {
                return false;
            }
        }

        $router = $dependencyInjector->getShared('router');
        if (is_object($router) && !$router instanceof Router) {
            throw new Exception('Invalid annotations reader');
        }
        if (!count($arguments) && $this->_arguments) {
            $router->handle($this->_arguments);
        } else {
            $router->handle($arguments);
        }

        /**
         * If the router doesn't return a valid module we use the default module
         */
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
        
        $dispatcher = $dependencyInjector->getShared("dispatcher");
        if (!$dispatcher instanceof Dispatcher) {
            throw new Exception('Wrong dispatcher service');
        }

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
     * 
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
        $args       = [];
        $opts       = [];
        $handleArgs = [];
        if ($shift && count($arguments)) {
            array_shift($arguments);
        }
        foreach ($arguments as $arg) {
            if (is_string($arg)) {
                if (strncmp($arg, '--', 2) == 0) {
                    $pos = strpos($arg, '=');
                    if ($pos) {
                        $opts[trim(substr($arg, 2, $pos - 2))] = trim(substr($arg, $pos + 1));
                    } else {
                        $opts[trim(substr($arg, 2))] = true;
                    }
                } else {
                    if (strncmp($arg, '-', 1) == 0) {
                        $opts[substr($arg, 1)] = true;
                    } else {
                        $args[] = $arg;
                    }
                }
            } else {
                $args[] = $arg;
            }
        }
        if ($str) {
            $this->_arguments = implode(Route::getDelimiter(), $args);
        } else {
            if (count($args)) {
                $handleArgs['task'] = array_shift($args);
            }
            if (count($args)) {
                $handleArgs['action'] = array_shift($args);
            }
            if (count($args)) {
                $handleArgs = array_merge($handleArgs, $args);
            }
            $this->_arguments = $handleArgs;
        }
        $this->_options = $opts;
        return $this;
    }

}
