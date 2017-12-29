<?php


namespace Phalcon\Image;

use Phalcon\Factory as BaseFactory;
use Phalcon\Factory\Exception;
use Phalcon\Config;
use Phalcon\Text;

/**
 * Loads Image Adapter class using 'adapter' option
 *
 *<code>
 * use Phalcon\Image\Factory;
 *
 * $options = [
 *     "width"   => 200,
 *     "height"  => 200,
 *     "file"    => "upload/test.jpg",
 *     "adapter" => "imagick",
 * ];
 * $image = Factory::load($options);
 *</code>
 */
class Factory extends BaseFactory
{
    /**
     * @param mixed $config
     * @return AdapterInterface
     */
    public static function load($config)
	{
		return self::loadClass("Phalcon\\Image\\Adapter", $config);
	}

protected static function loadClass($namespace,$config)
	{
	    if (is_string($namespace)===false){
	        throw new \Phalcon\Image\Exception('Invalid parameter type.');
        }

		if (is_object($config) && $config instanceof Config) {
        $config = $config->toArray();
		}

		if (!is_array($config)) {
        throw new Exception("Config must be array or Phalcon\\Config object");
    }

        if (isset($config['file'])){
	        $file = $config['file'];
        }else{
            throw new Exception("You must provide 'file' option in factory config parameter.");
        }

        if (isset($config['adapter'])) {
            $adapter = $config['asapter'];
        $className = $namespace."\\".Text::camelize($adapter);

			if (isset($config['width'])){
			    $width = $config['width'];
                if (isset($config['heigth'])){
                    $height = $config['heigth'];
                return new $className($file, $width,$height);
            }

				return new $className($file, $width);
			}

			return new $className($file);
		}

		throw new Exception("You must provide 'adapter' option in factory config parameter.");
	}
}
