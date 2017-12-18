<?php

/**
 * Text
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
 */

namespace Phalcon;

use \Phalcon\Exception;

/**
 * Phalcon\Text
 *
 * Provides utilities to work with texts
 */
abstract class Text
{

    /**
     * Random: Alphanumeric
     *
     * @var int
     */
    const RANDOM_ALNUM = 0;

    /**
     * Random: Alpha
     *
     * @var int
     */
    const RANDOM_ALPHA = 1;

    /**
     * Random: Hexdecimal
     *
     * @var int
     */
    const RANDOM_HEXDEC = 2;

    /**
     * Random: Numeric
     *
     * @var int
     */
    const RANDOM_NUMERIC = 3;

    /**
     * Random: No Zero
     *
     * @var int
     */
    const RANDOM_NOZERO = 4;

    /**
     * Random: Distinct
     *
     * @var int
     */
    const RANDOM_DISTINCT = 5;

    /**
     * find needle in $haystack
     * 
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    public static function memstr($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Converts strings to camelize style
     *
     * <code>
     *  echo \Phalcon\Text::camelize('coco_bongo'); //CocoBongo
     * </code>
     *
     * @param string $str
     * @return string
     * @throws Exception
     */
    public static function camelize($str, $delimiter = '_-')
    {
        if (is_string($str) === false) {
            //@warning The Exception is an E_ERROR in the original API
            throw new Exception('Invalid arguments supplied for camelize()');
        }

        if (is_null($delimiter)) {
            $delimiter = '_-';
        }

        $l         = strlen($str);
        $camelized = '';

        for ($i = 0; $i < $l; ++$i) {
            if ($i === 0 || strpos($delimiter, $str[$i]) !== false) {
                if (strpos($delimiter, $str[$i]) !== false) {
                    ++$i;
                }

                if (isset($str[$i]) === true) {
                    $camelized .= strtoupper($str[$i]);
                } else {
                    //Prevent pointer overflow, c emulation of strtoupper
                    $camelized .= chr(0);
                }
            } else {
                $camelized .= strtolower($str[$i]);
            }
        }

        return $camelized;
    }

    /**
     * Uncamelize strings which are camelized
     *
     * <code>
     *  echo \Phalcon\Text::camelize('CocoBongo'); //coco_bongo
     * </code>
     *
     * @param string $str
     * @return string
     * @throws Exception
     */
    public static function uncamelize($str, $delimiter = '_')
    {
        if (is_string($str) === false) {
            //@warning The Exception is an E_ERROR in the original API
            //@note changed "camelize" to "uncamelize"
            throw new Exception('Invalid arguments supplied for uncamelize()');
        }

        if (is_null($delimiter)) {
            $delimiter = '_';
        }
        $l           = strlen($str);
        $uncamelized = '';

        for ($i = 0; $i < $l; ++$i) {
            $ch = ord($str[$i]);

            if ($ch === 0) {
                break;
            }

            if ($ch >= 65 && $ch <= 90) {
                if ($i > 0) {
                    $uncamelized .= $delimiter;
                }
                $uncamelized .= chr($ch + 32);
            } else {
                $uncamelized .= $str[$i];
            }
        }

        return $uncamelized;
    }

    /**
     * Adds a number to a string or increment that number if it already is defined
     *
     * <code>
     *  echo \Phalcon\Text::increment("a"); // "a_1"
     *  echo \Phalcon\Text::increment("a_1"); // "a_2"
     * </code>
     *
     * @param string $str
     * @param string|null $separator
     * @return string
     * @throws Exception
     */
    public static function increment($str, $separator = null)
    {
        if (is_string($str) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($separator) === true) {
            $separator = '_';
        } elseif (is_string($separator) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $parts = explode($separator, $str);

        if (isset($parts[1]) === true) {
            $number = (int) $parts[1];
            $number++;
        } else {
            $number = 1;
        }

        return $parts[0] . $separator . $number;
    }

    /**
     * Generates a random string based on the given type. Type is one of the RANDOM_* constants
     *
     * <code>
     *  echo \Phalcon\Text::random(Phalcon\Text::RANDOM_ALNUM); //"aloiwkqz"
     * </code>
     *
     * @param int $type
     * @param int|null $length
     * @return string
     * @throws Exception
     */
    public static function random($type, $length = 8)
    {
        if (is_int($type) === false || $type < self::RANDOM_ALNUM ||
            $type > self::RANDOM_DISTINCT) {
            //@warning The function returns NULL in the original API
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($length) === true) {
            $length = 8;
        } elseif (is_int($length) === false) {
            //@warning The function returns NULL in the original API
            throw new Exception('Invalid parameter type.');
        }

        switch ($type) {

            case Text::RANDOM_ALPHA:
                $pool = array_merge(range("a", "z"), range("A", "Z"));
                break;

            case Text::RANDOM_HEXDEC:
                $pool = array_merge(range(0, 9), range("a", "f"));
                break;

            case Text::RANDOM_NUMERIC:
                $pool = range(0, 9);
                break;

            case Text::RANDOM_NOZERO:
                $pool = range(1, 9);
                break;

            case Text::RANDOM_DISTINCT:
                $pool = str_split("2345679ACDEFHJKLMNPRSTUVWXYZ");
                break;

            default:
                // Default type \Phalcon\Text::RANDOM_ALNUM
                $pool = array_merge(range(0, 9), range("a", "z"), range("A", "Z"));
                break;
        }

        $end = count($pool) - 1;

        $str = '';
        while (strlen($str) < $length) {
            $str .= $pool[mt_rand(0, $end)];
        }

        return $str;
    }

    /**
     * Check if a string starts with a given string
     *
     * <code>
     *  echo \Phalcon\Text::startsWith("Hello", "He"); // true
     *  echo \Phalcon\Text::startsWith("Hello", "he"); // false
     *  echo \Phalcon\Text::startsWith("Hello", "he", true); // true
     * </code>
     *
     * @param string $str
     * @param string $start
     * @param boolean $ignoreCase
     * @return boolean
     * @throws Exception
     */
    public static function startsWith($str, $start, $ignoreCase = true)
    {
        if (is_string($str) === false || is_string($start) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if ($ignoreCase) {
            return (stripos($str, $start) === 0 ? true : false);
        } else {
            return (strpos($str, $start) === 0 ? true : false);
        }
    }

    /**
     * Check if a string ends with a given string
     *
     * <code>
     *  echo \Phalcon\Text::endsWith("Hello", "llo"); // true
     *  echo \Phalcon\Text::endsWith("Hello", "LLO"); // false
     *  echo \Phalcon\Text::endsWith("Hello", "LLO", true); // true
     * </code>
     *
     * @param string $str
     * @param string $end
     * @param boolean|null $ignoreCase
     * @return boolean
     * @throws Exception
     */
    public static function endsWith($str, $end, $ignoreCase = true)
    {
        if (is_string($str) === false || is_string($end) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $g = strlen($str) - strlen($end);
        if ($ignoreCase) {
            return (strripos($str, $end) === $g ? true : false);
        } else {
            return (strrpos($str, $end) === $g ? true : false);
        }
    }

    /**
     * Lowercases a string, this function makes use of the mbstring extension if available
     *
     * @param string $str
     * @return string
     * @throws Exception
     */
    public static function lower($str, $encoding = "UTF-8")
    {
        if (is_string($str) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (function_exists('mb_strtolower') === true) {
            return mb_strtolower($str, $encoding);
        }

        return strtolower($str);
    }

    /**
     * Uppercases a string, this function makes use of the mbstring extension if available
     *
     * @param string $str
     * @return string
     * @throws Exception
     */
    public static function upper($str)
    {
        if (is_string($str) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (function_exists('mb_strtoupper') === true) {
            return mb_strtoupper($str);
        }

        return strtoupper($str);
    }

    /**
     * Reduces multiple slashes in a string to single slashes
     *
     * <code>
     * echo Phalcon\Text::reduceSlashes("foo//bar/baz"); // foo/bar/baz
     * echo Phalcon\Text::reduceSlashes("http://foo.bar///baz/buz"); // http://foo.bar/baz/buz
     * </code>
     */
    public static function reduceSlashes($str)
    {
        return preg_replace("#(?<!:)//+#", "/", $str);
    }

    /**
     * Concatenates strings using the separator only once without duplication in places concatenation
     *
     * <code>
     * $str = Phalcon\Text::concat(
     *     "/",
     *     "/tmp/",
     *     "/folder_1/",
     *     "/folder_2",
     *     "folder_3/"
     * );
     *
     * // /tmp/folder_1/folder_2/folder_3/
     * echo $str;
     * </code>
     *
     * @param string separator
     * @param string a
     * @param string b
     * @param string ...N
     * @return string
     */
    //public static function concat(string! separator, string! a, string! b) -> string
    public static function concat()
    {
        /**
         * TODO:
         * Remove after solve https://github.com/phalcon/zephir/issues/938,
         * and also replace line 214 to 213
         */
        $separator = $a         = $b         = '';
        $separator = func_get_arg(0);
        $a         = func_get_arg(1);
        $b         = func_get_arg(2);
        //END


        if (func_num_args() > 3) {
            $as = array_slice(func_get_args(), 3);
            foreach ($as as $c) {
                $b = rtrim($b, $separator) . $separator . ltrim($c, $separator);
            }
        }

        return rtrim($a, $separator) . $separator . ltrim($b, $separator);
    }

    /**
     * Generates random text in accordance with the template
     *
     * <code>
     * // Hi my name is a Bob
     * echo Phalcon\Text::dynamic("{Hi|Hello}, my name is a {Bob|Mark|Jon}!");
     *
     * // Hi my name is a Jon
     * echo Phalcon\Text::dynamic("{Hi|Hello}, my name is a {Bob|Mark|Jon}!");
     *
     * // Hello my name is a Bob
     * echo Phalcon\Text::dynamic("{Hi|Hello}, my name is a {Bob|Mark|Jon}!");
     *
     * // Hello my name is a Zyxep
     * echo Phalcon\Text::dynamic("[Hi/Hello], my name is a [Zyxep/Mark]!", "[", "]", "/");
     * </code>
     * 
     * @param string $text
     * @param string $leftDelimiter
     * @param string $rightDelimiter
     * @param string $separator
     */
    public static function dynamic($text, $leftDelimiter = "{", $rightDelimiter = "}", $separator = "|")
    {
        if (substr_count($text, $leftDelimiter) !== substr_count($text, $rightDelimiter)) {
            throw new \RuntimeException("Syntax error in string \"" . $text . "\"");
        }

        $ldS     = preg_quote($leftDelimiter);
        $rdS     = preg_quote($rightDelimiter);
        $pattern = "/" . $ldS . "([^" . $ldS . $rdS . "]+)" . $rdS . "/";
        $matches = [];

        if (!preg_match_all($pattern, $text, $matches, 2)) {
            return $text;
        }

        if (is_array($matches)) {
            foreach ($matches as $match) {
                if (!isset($match[0]) || !isset($match[1])) {
                    continue;
                }

                $words = explode($separator, $match[1]);
                $word  = $words[array_rand($words)];
                $sub   = preg_quote($match[0], $separator);
                $text  = preg_replace("/" . $sub . "/", $word, $text, 1);
            }
        }

        return $text;
    }

    /**
     * Makes a phrase underscored instead of spaced
     *
     * <code>
     * echo Phalcon\Text::underscore("look behind"); // "look_behind"
     * echo Phalcon\Text::underscore("Awesome Phalcon"); // "Awesome_Phalcon"
     * </code>
     * 
     * @param string $text
     * @return string
     */
    public static function underscore($text)
    {
        return preg_replace("#\s+#", "_", trim($text));
    }

    /**
     * Makes an underscored or dashed phrase human-readable
     *
     * <code>
     * echo Phalcon\Text::humanize("start-a-horse"); // "start a horse"
     * echo Phalcon\Text::humanize("five_cats"); // "five cats"
     * </code>
     * 
     * @param string $text
     * @return string
     */
    public static function humanize($text)
    {
        return preg_replace("#[_-]+#", " ", trim($text));
    }

}
