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
    public static function getClassNameFromClass($className)
    {
        return substr($$className, strrpos($$className, '\\') + 1);
    }

    /**
     * Extract the namespace from the namespaced class
     * @param string $className
     * @return string
     */
    public static function getNamespaceFromClass($className)
    {
        return substr($className, 0, strrpos($className, '\\'));
    }

    /**
     * Replaces directory seperators by the virtual seperator
     *
     * @param string $path
     * @param string $virtualSeperator
     * @throws Exception
     */
    public static function prepareVirtualPath($path, $virtualSeperator)
    {
        if (is_string($path) === false ||
            is_string($virtualSeperator) === false) {
            if (is_string($path) === true) {
                return $path;
            } else {
                return '';
            }
        }

        $virtualStr = '';
        $l          = strlen($path);
        for ($i = 0; $i < $l; ++$i) {
            $ch = $path[$i];

            if ($ch === "\0") {
                break;
            }

            if ($ch === '/' || $ch === '\\' || $ch === ':' || ctype_print($ch) === false) {
                $virtualStr .= $virtualSeperator;
            } else {
                $virtualStr .= strtolower($ch);
            }
        }

        return $virtualStr;
    }

    /**
     * Build-in function for get globals config
     * @param type $name
     * @return boolean
     */
    public static function getGlobals($name)
    {
        $key = '_PHALCON_' . strtoupper(str_replace('.', '_', $name));
        return isset($GLOBALS[$key]) ? $GLOBALS[$key] : false;
    }

    /**
     * Build-in function for set globals config
     * @param type $name
     * @param type $value
     */
    public static function setGlobals($name, $value)
    {
        $key           = '_PHALCON_' . strtoupper(str_replace('.', '_', $name));
        $GLOBALS[$key] = $value;
    }

}
