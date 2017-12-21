<?php
/*
 +------------------------------------------------------------------------+
 | Phalcon Framework                                                      |
 +------------------------------------------------------------------------+
 | Copyright (c) 2011-2016 Phalcon Team (http://www.phalconphp.com)       |
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
 |          Wojciech Ślawski <jurigag@gmail.com>                          |
 +------------------------------------------------------------------------+
 */

namespace Phalcon\Mvc\Model;

use Phalcon\Cache\BackendInterface;

/**
 * Phalcon\Mvc\Model\BinderInterface
 *
 * Interface for Phalcon\Mvc\Model\Binder
 */
interface BinderInterface
{
	/**
	 * Gets active bound models
     *
     * @return array
	 */
	public function getBoundModels();

	/**
	 * Gets cache instance
     *
     * @return BackendInterface
	 */
	public function getCache();

	/**
	 * Sets cache instance
     *
     * @param BackendInterface $cache
     * @return BinderInterface
	 */
	public function setCache($cache);

    /**
     * Bind models into params in proper handler
     *
     * @param array
     * @param array  $params
     * @param string $cacheKey
     * @param string $methodName
     *
     * @return array
     */
	public function bindToHandler($handler, $params, $cacheKey, $methodName = null);
}
