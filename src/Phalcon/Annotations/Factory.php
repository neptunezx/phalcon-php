<?php

namespace Phalcon\Annotations;
use Phalcon\Factory as BaseFactory;


class Factory extends BaseFactory
{
    /**
     * @param \Phalcon\Config|array config
     * @return AdapterInterface
     */
    public static function load($config)
	{
		return self::loadClass("Phalcon\\Annotations\\Adapter", $config);
	}
}