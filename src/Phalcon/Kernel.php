<?php

namespace Phalcon;

/**
 * Phalcon\Kernel
 *
 * This class allows to change the internal behavior of the framework in runtime
 */
class Kernel
{

    /**
     * Produces a pre-computed hash key based on a string.
     * This function produce different numbers in 32bit/64bit processors
     *
     * @param string $arrKey
     * @return string|null
     */
    public static function preComputeHashKey($arrKey)
    {
        if (is_string($arrKey) === false) {
            return null;
        }

        return (string) md5($arrKey);
    }

    /**
     * Produces a pre-computed hash key based on a string.
     * This function produce a hash for a 32bits processor
     *
     * @param string $arrKey
     * @return string|null
     */
    public static function preComputeHashKey32($arrKey)
    {
        return self::preComputeHashKey($arrKey);
    }

    /**
     * Produces a pre-computed hash key based on a string.
     * This function produce a hash for a 64bits processor
     *
     * @param string $arrKey
     * @return string|null
     */
    public static function preComputeHashKey64($arrKey)
    {
        return self::preComputeHashKey($arrKey);
    }

    /**
     * Extract the real class name from the namespaced class
     * @param string $className
     * @return string
     */
    public function getClassNamespace($className)
    {
        return substr($$className, strrpos($$className, '\\') + 1);
    }

    /**
     * Extract the namespace from the namespaced class
     * @param string $className
     * @return string
     */
    static function getNamespaceOfclass($className)
    {
        return substr($className, 0, strrpos($className, '\\'));
    }

}
