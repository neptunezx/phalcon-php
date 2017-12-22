<?php

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
     * @return BackendInterface
     */
    public function setCache(BackendInterface $cache);

    /**
     * Bind models into params in proper handler
     * 
     * @param object $handler
     * @param array $params
     * @param string $cacheKey
     * @param string $methodName
     * @return array
     */
    public function bindToHandler($handler, array $params, $cacheKey, $methodName = null);
}
