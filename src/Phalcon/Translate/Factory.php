<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/23
 * Time: 下午10:05
 */

namespace Phalcon\Translate;

use Phalcon\Factory as BaseFactory;

/**
 * Loads Translate Adapter class using 'adapter' option
 *
 *<code>
 * use Phalcon\Translate\Factory;
 *
 * $options = [
 *     "locale"        => "de_DE.UTF-8",
 *     "defaultDomain" => "translations",
 *     "directory"     => "/path/to/application/locales",
 *     "category"      => LC_MESSAGES,
 *     "adapter"       => "gettext",
 * ];
 * $translate = Factory::load($options);
 *</code>
 */
class Factory extends BaseFactory
{
    /**
     * @param $config \Phalcon\Config|array
     * @return mixed|object
     * @throws BaseFactory\Exception
     */
    public static function load($config)
	{
		return self::loadClass("Phalcon\\Translate\\Adapter", $config);
	}
}