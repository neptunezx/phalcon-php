<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/25
 * Time: 下午7:44
 */

namespace Phalcon\Translate\Interpolator;

use Phalcon\Translate\InterpolatorInterface;

class AssociativeArray implements InterpolatorInterface
{

    /**
     * @param string $translation
     * @param null $placeholders
     * @return string
     */
    public function replacePlaceholders($translation, $placeholders = null)
    {
        if (!is_string($translation)) {
            throw new Exception('Invalid parameter type.');
        }
        if (is_array($placeholders) && count($placeholders)) {
            foreach ($placeholders as $key => $value) {
                $translation = str_replace("%" . $key . "%", $value, $translation);
            }
        }

        return $translation;
    }
}
