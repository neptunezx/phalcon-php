<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/25
 * Time: 下午7:48
 */

namespace Phalcon\Translate\Interpolator;

use Phalcon\Translate\InterpolatorInterface;

class IndexedArray implements InterpolatorInterface
{

    /**
     * @param string $translation
     * @param null $placeholders
     * @return mixed|string
     */
    public function replacePlaceholders($translation, $placeholders = null)
    {
        if (!is_string($translation)) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_array($placeholders) && count($placeholders)) {
            array_unshift($placeholders, $translation);
            return call_user_func_array("sprintf", $placeholders);
        }
        return $translation;
    }
}