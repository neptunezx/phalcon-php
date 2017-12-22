<?php

namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\Controller\BindModelInterface;
use Phalcon\Mvc\Model\Binder\BindableInterface;
use Phalcon\Cache\BackendInterface;

/**
 * Phalcon\Mvc\Model\Binding
 *
 * This is an class for binding models into params for handler
 */
class Binder implements BinderInterface
{

    /**
     * Array for storing active bound models
     *
     * @var array
     */
    protected $boundModels = [];

    /**
     * Cache object used for caching parameters for model binding
     */
    protected $cache;

    /**
     * Internal cache for caching parameters for model binding during request
     */
    protected $internalCache = [];

    /**
     * Array for original values
     */
    protected $originalValues = [];

    /**
     * Phalcon\Mvc\Model\Binder constructor
     */
    public function __construct(BackendInterface $cache = null)
    {
        $this->cache = $cache;
    }

    public function getBoundModels()
    {
        return $this->boundModels;
    }

    public function getOriginalValues()
    {
        return $this->originalValues;
    }

    /**
     * Gets cache instance
     * 
     * @param BackendInterface $cache
     * @return BinderInterface
     */
    public function setCache(BackendInterface $cache)
    {
        $this->cache = cache;

        return $this;
    }

    /**
     * Sets cache instance
     * 
     * @return BinderInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Bind models into params in proper handler
     */
    public function bindToHandler(object $handler, array $params, $cacheKey, $methodName = null)
    {
        $this->originalValues = [];
        if ($handler instanceof \Closure || $methodName != null) {
            $this->boundModels = [];
            $paramsCache       = $this->getParamsFromCache($cacheKey);
            if (is_array($paramsCache)) {
                foreach ($paramsCache as $paramKey => $className) {
                    $paramValue                      = $params[$paramKey];
                    $boundModel                      = $this->findBoundModel($paramValue, $className);
                    $this->originalValues[$paramKey] = $paramValue;
                    $params[$paramKey]               = $boundModel;
                    $this->boundModels[$paramKey]    = $boundModel;
                }

                return $params;
            }

            return $this->getParamsFromReflection($handler, $params, $cacheKey, $methodName);
        }
        throw new Exception("You must specify methodName for handler or pass Closure as handler");
    }

    /**
     * Find the model by param value.
     */
    protected function findBoundModel($paramValue, $className)
    {
        return $className::findFirst($paramValue);
    }

    /**
     * Get params classes from cache by key
     */
    protected function getParamsFromCache($cacheKey)
    {
        if (isset($this->internalCache[$cacheKey])) {
            return $internalParams;
        }

        $cache = $this->cache;

        if ($cache != null && $cache->exists($cacheKey)) {
            $internalParams                 = $cache->get($cacheKey);
            $this->internalCache[$cacheKey] = $internalParams;

            return $internalParams;
        }

        return null;
    }

    /**
     * Get modified params for handler using reflection
     */
    protected function getParamsFromReflection($handler, array $params, $cacheKey, $methodName)
    {
        $reflection  = null;
        $realClasses = null;
        $paramsCache = [];
        if ($methodName != null) {
            $reflection = new \ReflectionMethod($handler, $methodName);
        } else {
            $reflection = new \ReflectionFunction($handler);
        }

        $cache = $this->cache;

        $methodParams = $reflection->getParameters();
        $paramsKeys   = array_keys($params);
        foreach ($methodParams as $paramKey => $methodParam) {
            $reflectionClass = $methodParam->getClass();

            if (!$reflectionClass) {
                continue;
            }

            $className = $reflectionClass->getName();
            if (!isset($params[$paramKey])) {
                $paramKey = $paramsKeys[$paramKey];
            }
            $boundModel = null;
            $paramValue = $params[$paramKey];

            if ($className == "Phalcon\\Mvc\\Model") {
                if ($realClasses == null) {
                    if ($handler instanceof BindModelInterface) {
                        $handlerClass = get_class($handler);
                        $realClasses  = $handlerClass::getModelName();
                    } elseif ($handler instanceof BindableInterface) {
                        $realClasses = $handler->getModelName();
                    } else {
                        throw new Exception("Handler must implement Phalcon\\Mvc\\Model\\Binder\\BindableInterface in order to use Phalcon\\Mvc\\Model as parameter");
                    }
                }
                if (is_array($realClasses)) {
                    if (isset($realClasses[$paramKey])) {
                        $boundModel = $this->findBoundModel($paramValue, $realClasses[$paramKey]);
                    } else {
                        throw new Exception("You should provide model class name for " . $paramKey . " parameter");
                    }
                } elseif (is_string($realClasses)) {
                    $className  = $realClasses;
                    $boundModel = $this->findBoundModel($paramValue, $className);
                } else {
                    throw new Exception("getModelName should return array or string");
                }
            } elseif (is_subclass_of($className, "Phalcon\\Mvc\\Model")) {
                $boundModel = $this->findBoundModel($paramValue, $className);
            }

            if ($boundModel != null) {
                $this->originalValues[$paramKey] = $paramValue;
                $params[$paramKey]               = $boundModel;
                $this->boundModels[$paramKey]    = $boundModel;
                $paramsCache[$paramKey]          = $className;
            }
        }

        if ($cache != null) {
            $cache->save($cacheKey, $paramsCache);
        }

        $this->internalCache[$cacheKey] = $paramsCache;

        return params;
    }

}
