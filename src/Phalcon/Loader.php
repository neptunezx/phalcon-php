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
 +------------------------------------------------------------------------+
 */
namespace Phalcon;

use Phalcon\Events\ManagerInterface;
use Phalcon\Events\EventsAwareInterface;

/**
 * Phalcon\Loader
 *
 * This component helps to load your project classes automatically based on some conventions
 *
 * <code>
 * //Creates the autoloader
 * $loader = new Phalcon\Loader();
 *
 * //Register some namespaces
 * $loader->registerNamespaces(array(
 *   'Example\Base' => 'vendor/example/base/',
 *   'Example\Adapter' => 'vendor/example/adapter/',
 *   'Example' => 'vendor/example/'
 * ));
 *
 * //register autoloader
 * $loader->register();
 *
 * //Requiring this class will automatically include file vendor/example/adapter/Some.php
 * $adapter = Example\Adapter\Some();
 * </code>
 *
 * @see https://github.com/phalcon/cphalcon/blob/1.2.6/ext/loader.c
 */
class Loader implements EventsAwareInterface
{

    /**
     * Events Manager
     *
     * @var \Phalcon\Events\ManagerInterface|null
     * @access protected
     */
    protected $_eventsManager = null;

    /**
     * Found Path
     *
     * @var string|null
     * @access protected
     */
    protected $_foundPath = null;

    /**
     * Checked Path
     *
     * @var string|null
     * @access protected
     */
    protected $_checkedPath = null;

    /**
     * Classes
     *
     * @var array
     * @access protected
     */
    protected $_classes = [];

    /**
     * Extensions
     *
     * @var array
     * @access protected
     */
    protected $_extensions = ["php"];

    /**
     * Namespaces
     *
     * @var array
     * @access protected
     */
    protected $_namespaces = [];

    /**
     * Directories
     *
     * @var array|null
     * @access protected
     */
    protected $_directories = [];

    /**
     * Files
     * @var array
     * @access protected
     */
    protected $_files = [];

    /**
     * Registered
     *
     * @var boolean
     * @access protected
     */
    protected $_registered = false;

    /**
     * Sets the events manager
     * @param object $eventsManager
     * @throws
     */
    public function setEventsManager($eventsManager)
    {
        if (is_object($eventsManager) === false ||
            $eventsManager instanceof ManagerInterface === false) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     * @return \Phalcon\Events\ManagerInterface|null
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * Sets an array of extensions that the loader must try in each attempt to locate the file
     *
     * @param array $extensions
     * @return object
     * @throws
     */
    public function setExtensions($extensions)
    {
        if (is_array($extensions) === false) {
            throw new LoaderException('Parameter extension must be an array');
        }

        $this->_extensions = $extensions;

        return $this;
    }

    /**
     * Return file extensions registered in the loader
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->_extensions;
    }

    /**
     * Register namespaces and their related directories
     *
     * @param array $namespaces
     * @param boolean $merge
     * @return \Phalcon\Loader
     * @throws \Phalcon\Loader\Exception
     */
    public function registerNamespaces($namespaces, $merge = false)
    {
        if(!is_array($namespaces)){
            throw new LoaderException('namespace must be array!');
        }
        if(!is_bool($merge)){
            throw new LoaderException('merge must be boolean!');
        }
		$preparedNamespaces = $this->prepareNamespace($namespaces);

		if( $merge ){
            foreach($preparedNamespaces as $name => $paths ){
                if( !isset($this->_namespaces[$name]) ){
                    $this->_namespaces[$name] = [];
				}

				$this->_namespaces[$name] = array_merge($this->_namespaces[$name], $paths);
			}
        } else {
            $this->_namespaces = $preparedNamespaces;
		}
		return $this;
    }

    /**
     * @param $namespace array
     * @return array
     * @throws LoaderException
     */
    protected function prepareNamespace($namespace){
        if ( ! is_array($namespace) ){
            throw new LoaderException('namespace must be array!');
        }
        $prepared = [];
        foreach($namespace as $name=>$paths){
            if( !is_array($paths) ){
                $localPaths = [$paths];
            }else{
                $localPaths = $paths;
            }
            $prepared[$name] = $localPaths;
        }
        return $prepared;
	}

    /**
     * Return current namespaces registered in the autoloader
     *
     * @return array|null
     */
    public function getNamespaces()
    {
        return $this->_namespaces;
    }

    /**
     * Register directories in which "not found" classes could be found
     * @param $directories array
     * @param $merge bool
     * @return object
     * @throws
     */
    public function registerDirs($directories, $merge = false)
	{
		if(!is_array($directories)){
            throw new LoaderException('params 1 must be array,'.gettype($directories)." given");
        }
        if(!is_bool($merge)){
            throw new LoaderException('params 2 must be boolean,'.gettype($merge)." given");
        }
	    if ($merge) {
			$this->_directories = array_merge($this->_directories, $directories);
		} else {
            $this->_directories = $directories;
		}

        return $this;
    }

    /**
     * Return current directories registered in the autoloader
     * @return array|null
     */
    public function getDirs()
    {
        return $this->_directories;
    }

    /**
     * Registers files that are "non-classes" hence need a "require". This is very useful for including files that only
     * have functions
     * @param $files array
     * @param $merge boolean
     * @throws
     * @return object
     */
    public function registerFiles($files, $merge = false)
	{
		if(!is_array($files)){
            throw new LoaderException('params 1 must be array,'.gettype($files)." give");
        }

        if(!is_bool($merge)){
            throw new LoaderException('params 2 must be boolean,'.gettype($merge)." give");
        }

	    if ( $merge === true ) {
			$this->_files = array_merge($this->_files, $files);
		} else {
            $this->_files = $files;
		}

    return $this;
}

    /**
     * Returns the files currently registered in the autoloader
     * @return array
     */
    public function getFiles()
	{
        return $this->_files;
	}

    /**
     * Register classes and their locations
     *
     * @param array $classes
     * @param boolean|null $merge
     * @return object
     * @throws
     */
    public function registerClasses($classes, $merge = false)
    {
        if(!is_array($classes)){
            throw new LoaderException('params 1 must be array,'.gettype($classes)." give");
        }

        if(!is_bool($merge)){
            throw new LoaderException('params 2 must be boolean,'.gettype($merge)." give");
        }
        if($merge) {
            $this->_classes = array_merge($this->_classes, $classes);
		} else {
            $this->_classes = $classes;
		}

        return $this;
    }

    /**
     * Return the current class-map registered in the autoloader
     *
     * @return array|null
     */
    public function getClasses()
    {
        return $this->_classes;
    }

    /**
     * Register the autoload method
     * @param $prepend bool
     * @return \Phalcon\Loader
     */
    public function register( $prepend = null)
    {
        if ($this->_registered === false) {
            $this->loadFiles();
            spl_autoload_register([$this, "autoLoad"], true, $prepend);
            $this->_registered = true;
        }

        return $this;
    }



    /**
     * Unregister the autoload method
     *
     * @return \Phalcon\Loader
     */
    public function unregister()
    {
        if ($this->_registered === true) {
            spl_autoload_unregister(array($this, 'autoLoad'));
            $this->_registered = false;
        }

        return $this;
    }

    /**
     * Checks if a file exists and then adds the file by doing virtual require
     */
    public function loadFiles()
    {
        foreach($this->_files as $filePath) {
            if( is_object($this->_eventsManager) ){
                $this->_checkedPath = $filePath;
                $this->_eventsManager->fire("loader:beforeCheckPath", $this, $filePath);
			}

			/**
             * Check if the file specified even exists
             */
			if( is_file($filePath) ){

                /**
                 * Call 'pathFound' event
                 */
				if( is_object($this->_eventsManager) ){
                    $this->_foundPath = $filePath;
                    $this->_eventsManager->fire("loader:pathFound", $this, $filePath);
				}

				/**
                 * Simulate a require
                 */
				require($filePath);
			}
		}
	}


    /**
     * Autoloads the registered classes
     * @param $className string
     * @return boolean
     */
    public function autoLoad($className){
		$eventsManager = $this->_eventsManager;
		if (is_object($eventsManager)){
			$eventsManager->fire("loader:beforeCheckClass", $this, $className);
		}

        $classes = $this->_classes;
		if ( isset($classes[$className]) && !empty($classes[$className]) ){
		    $filePath = $classes[$className];
            if( is_object($eventsManager) ){
                $this->_foundPath = $filePath;
				$eventsManager->fire("loader:pathFound", $this, $filePath);
			}
			require($filePath);
			return true;
		}

		$extensions = $this->_extensions;

		$ds = DIRECTORY_SEPARATOR;
        $ns = "\\";

		/**
         * Checking in namespaces
         */
		$namespaces = $this->_namespaces;
		foreach($namespaces as $nsPrefix=>$directories){
            if( Text::startsWith($className, $nsPrefix) === false){
				continue;
            }

            /**
             * Append the namespace separator to the prefix
             */
            $fileName = substr($className, strlen($nsPrefix . $ns));

            if( !$fileName ){
                continue;
            }

            $fileName = str_replace($ns, $ds, $fileName);
            foreach($directories as $directory){
                $fixedDirectory = rtrim($directory, $ds) . $ds;
                foreach($extensions as $extension){
                    $filePath = $fixedDirectory . $fileName . "." . $extension;

					/**
                     * Check if a events manager is available
                     */
					if( is_object($eventsManager) ){
                        $this->_checkedPath = $filePath;
						$eventsManager->fire("loader:beforeCheckPath", $this);
					}

					/**
                     * This is probably a good path, let's check if the file exists
                     */
					if( is_file($filePath) ){

						if ( is_object($eventsManager) ) {
                            $this->_foundPath = $filePath;
							$eventsManager->fire("loader:pathFound", $this, $filePath);
						}

						/**
                         * Simulate a require
                         */
						require($filePath);

						/**
                         * Return true mean success
                         */
						return true;
					}
                }
            }
//
//            /**
//             * Change the namespace separator by directory separator too
//             */
//            $nsClassName = str_replace("\\", $ds, $className);
//
//            /**
//             * Checking in directories
//             */
//		    $directories = $this->_directories;
//
//		    foreach($directories as $directory){
//                /**
//                 * Add a trailing directory separator if the user forgot to do that
//                 */
//                $fixedDirectory = rtrim($directory, $ds) . $ds;
//
//			    foreach($extensions as $extension){
//                    /**
//                     * Create a possible path for the file
//                     */
//                    $filePath = $fixedDirectory . $nsClassName . "." . $extension;
//
//				if (is_object($eventsManager) ){
//                    $this->_checkedPath = $filePath;
//					$eventsManager->fire("loader:beforeCheckPath", $this, $filePath);
//				}
//
//				/**
//                 * Check in every directory if the class exists here
//                 */
//				if( is_file($filePath) ){
//
//                    /**
//                     * Call 'pathFound' event
//                     */
//					if( is_object($eventsManager) ){
//                        $this->_foundPath = $filePath;
//						$eventsManager->fire("loader:pathFound", $this, $filePath);
//					}
//
//					/**
//                     * Simulate a require
//                     */
//					require($filePath);
//
//					/**
//                     * Return true meaning success
//                     */
//					return true;
//				}
//			}
//		}
//
//		/**
//         * Call 'afterCheckClass' event
//         */
//		if( is_object($eventsManager) ){
//                $eventsManager->fire("loader:afterCheckClass", $this, $className);
//		}
//
//		/**
//         * Cannot find the class, return false
//         */
//		return false;
        }
	}

    /**
     * Get the path when a class was found
     *
     * @return string|null
     */
    public function getFoundPath()
    {
        return $this->_foundPath;
    }

    /**
     * Get the path the loader is checking for a path
     *
     * @return string|null
     */
    public function getCheckedPath()
    {
        return $this->_checkedPath;
    }

}
