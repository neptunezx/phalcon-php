<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/22
 * Time: 下午12:11
 */
namespace Phalcon\Paginator;

use Phalcon\Factory as BaseFactory;

/**
 * Loads Paginator Adapter class using 'adapter' option
 *
 *<code>
 * use Phalcon\Paginator\Factory;
 * $builder = $this->modelsManager->createBuilder()
 *                 ->columns("id, name")
 *                 ->from("Robots")
 *                 ->orderBy("name");
 *
 * $options = [
 *     "builder" => $builder,
 *     "limit"   => 20,
 *     "page"    => 1,
 *     "adapter" => "queryBuilder",
 * ];
 * $paginator = Factory::load($options);
 *</code>
 */
class Factory extends BaseFactory
{
    /**
     * load function
     *
     * @param \Phalcon\Config|array config
     * @return AdapterInterface
     * @throws
     */
    public static function load($config)
	{
		return self::loadClass("Phalcon\\Paginator\\Adapter", $config);
	}
}
