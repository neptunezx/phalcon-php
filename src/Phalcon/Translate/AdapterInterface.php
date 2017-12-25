<?php

namespace Phalcon\Translate;

/**
 * Phalcon\Translate\AdapterInterface initializer
 *
 */
interface AdapterInterface
{
    /**
     * Returns the translation string of the given key
     *
     * @param	string translateKey
     * @param	array placeholders
     * @return	string
     */
    public function t($translateKey,array $placeholders = null);

    /**
     * Returns the translation related to the given key
     *
     * @param string $index
     * @param array|null $placeholders
     * @return string
     */
    public function query($index,array $placeholders = null);

    /**
     * Check whether is defined a translation key in the internal array
     *
     * @param string $index
     * @return bool
     */
    public function exists($index);
}
