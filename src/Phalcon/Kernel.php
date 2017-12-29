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
        if ($className == null) {
            return '';
        }
        if (is_object($className)) {
            $className = get_class($className);
        }
        if (!is_string($className)) {
            throw new Exception("get_class_ns expects an object");
        }
        return substr($className, strrpos($className, '\\') + 1);
    }

    /**
     * Extract the namespace from the namespaced class
     * @param string $className
     * @return string
     */
    public static function getNamespaceFromClass($className)
    {
        if ($className == null) {
            return '';
        }
        if (is_object($className)) {
            $className = get_class($className);
        }
        if (!is_string($className)) {
            throw new Exception("get_ns_class expects an object");
        }
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
     * 
     */
    public static function getGlobals($name)
    {

        $key = '_PHALCON_' . strtoupper(str_replace('.', '_', $name));
        return isset($GLOBALS[$key]) ? $GLOBALS[$key] : self::getDefaultGlobals($key);
    }

    public static function getDefaultGlobals($key)
    {
        //; ----- Options to use the Phalcon Framework
        //; phalcon.db.escape_identifiers = On
        //; phalcon.db.force_casting = Off
        //; phalcon.orm.events = On
        //; phalcon.orm.virtual_foreign_keys = On
        //; phalcon.orm.column_renaming = On
        //; phalcon.orm.not_null_validations = On
        //; phalcon.orm.exception_on_failed_save = Off
        //; phalcon.orm.enable_literals = On
        //; phalcon.orm.late_state_binding = Off
        //; phalcon.orm.enable_implicit_joins = On
        //; phalcon.orm.cast_on_hydrate = Off
        //; phalcon.orm.ignore_unknown_columns = Off
        //; phalcon.orm.update_snapshot_on_save = On
        //; phalcon.orm.disable_assign_setters = Off
        $PAHLCON_INI = [
            '_PHALCON_DB_ESCAPE_IDENTIFIERS'        => true,
            '_PHALCON_DB_FORCE_CASTING'             => false,
            '_PHALCON_ORM_EVENTS'                   => true,
            '_PHALCON_ORM_VIRTUAL_FOREIGN_KEYS'     => true,
            '_PHALCON_ORM_COLUMN_RENAMING'          => true,
            '_PHALCON_ORM_NOT_NULL_VALIDATIONS'     => true,
            '_PHALCON_ORM_EXCEPTION_ON_FAILED_SAVE' => false,
            '_PHALCON_ORM_ENABLE_LITERALS'          => true,
            '_PHALCON_ORM_LATE_STATE_BINDING'       => false,
            '_PHALCON_ORM_ENABLE_IMPLICIT_JOINS'    => true,
            '_PHALCON_ORM_CAST_ON_HYDRATE'          => false,
            '_PHALCON_ORM_IGNORE_UNKNOWN_COLUMNS'   => false,
            '_PHALCON_ORM_UPDATE_SNAPSHOT_ON_SAVE'  => true,
            '_PHALCON_ORM_DISABLE_ASSIGN_SETTERS'   => false,
        ];
        return isset($PAHLCON_INI[$key]) ? $PAHLCON_INI[$key] : false;
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
