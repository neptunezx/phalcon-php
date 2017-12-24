<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/23
 * Time: 下午10:05
 */

namespace Phalcon\Translate;

/**
 * Phalcon\Translate\AdapterInterface
 *
 * Interface for Phalcon\Translate adapters
 */
interface InterpolatorInterface
{
    /**
     * @param $translation string
     * @param $placeholders mixed|null
     * @return string
     */
    public function replacePlaceholders($translation, $placeholders = null);
}