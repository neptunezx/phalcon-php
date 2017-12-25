<?php
/**
 * Created by PhpStorm.
 * User: gaopu
 * Date: 2017/12/23
 * Time: 下午8:46
 */

namespace Phalcon\Translate\Adapter;

use Phalcon\Translate\Exception;
use Phalcon\Translate\Adapter;

/**
 * Phalcon\Translate\Adapter\Gettext
 *
 * <code>
 * use Phalcon\Translate\Adapter\Gettext;
 *
 * $adapter = new Gettext(
 *     [
 *         "locale"        => "de_DE.UTF-8",
 *         "defaultDomain" => "translations",
 *         "directory"     => "/path/to/application/locales",
 *         "category"      => LC_MESSAGES,
 *     ]
 * );
 * </code>
 *
 * Allows translate using gettext
 */
class Gettext extends Adapter implements \ArrayAccess
{
    /**
     * @var string|array
     */
    protected $_directory;

    /**
     * @return array|string
     */
    public function getDirectory()
    {
        return $this->_directory;
    }

    /**
     * @var string
     */
    protected $_defaultDomain;

    /**
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->_defaultDomain;
    }

    /**
     * @var string
     */
    protected $_locale;

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->_locale;
    }

    /**
     * @var int
     */
    protected $_category;

    /**
     * @return int
     */
    public function getCategory()
    {
        return $this->_category;
    }

    /**
     * Gettext constructor.
     * @param  $options array
     * @throws Exception
     */
    public function __construct(array $options)
    {
        if (!is_array($options)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!function_exists("gettext")) {
            throw new Exception("This class requires the gettext extension for PHP");
        }

        parent::__construct($options);
        $this->prepareOptions($options);
    }

    /**
     * @param $index
     * @param $placeholders null
     * @return string
     */
    public function query($index, $placeholders = null)
    {
        $translation = gettext($index);
        return $this->replacePlaceholders($translation, $placeholders);
    }

    /**
     * @param $index
     * @return bool
     * @throws Exception
     */
    public function exists($index)
    {
        if (!is_string($index)) {
            throw new Exception('Invalid parameter type.');
        }
        $result = $this->query($index);

        return $result !== $index;
    }

    /**
     * @param $msgid1 string
     * @param $msgid2 string
     * @param $count int
     * @param $placeholders string|null
     * @param $domain string|null
     * @return mixed
     */
    public function nquery($msgid1, $msgid2, $count, $placeholders = null, $domain = null)
    {
        if (!is_string($msgid1)
            && !is_string($msgid2)
            && !is_int($count)
            && (!is_null($placeholders) && !is_string($placeholders))
            && (!is_null($domain) && !is_string($domain))
        ) {
            throw new Exception('Invalid parameter type.');
        }
        if (!$domain) {
            $translation = ngettext($msgid1, $msgid2, $count);
        } else {
            $translation = dngettext($domain, $msgid1, $msgid2, $count);
        }

        return $this->replacePlaceholders($translation, $placeholders);
    }

    /**
     * Changes the current domain (i.e. the translation file)
     * @param $domain
     * @return string
     */
    public function setDomain($domain)
    {
        return textdomain($domain);
    }

    /**
     * Sets the default domain
     * @return string
     */
    public function resetDomain()
    {
        return textdomain($this->getDefaultDomain());
    }

    /**
     * Sets the domain default to search within when calls are made to gettext()
     * @param $domain
     */
    public function setDefaultDomain($domain)
    {
        $this->_defaultDomain = $domain;
    }

    /**
     * Sets the path for a domain
     * @param string|array directory The directory path or an array of directories and domains
     */
    public function setDirectory($directory)
    {
        if (empty($directory)) {
            return;
        }
        $this->_directory = $directory;
        if (is_array($directory)) {
            foreach ($directory as $key => $value) {
                bindtextdomain($key, $value);
            }
        } else {
            bindtextdomain($this->getDefaultDomain(), $directory);
        }
    }

    /**
     * @param $category int
     * @param $locale string
     * @return boolean|string
     */
    public function setLocale($category, $locale)
    {
        $this->_locale = call_user_func_array("setlocale", func_get_args());
        $this->_category = $category;

        putenv("LC_ALL=" . $this->_locale);
        putenv("LANG=" . $this->_locale);
        putenv("LANGUAGE=" . $this->_locale);
        setlocale(LC_ALL, $this->_locale);

        return $this->_locale;
    }

    /**
     * @param array $options
     * @throws Exception
     */
    protected function prepareOptions(array $options)
    {
        if (!is_array($options)) {
            throw new Exception('Invalid parameter type.');
        }
        if (!isset($options["locale"])) {
            throw new Exception("Parameter 'locale' is required");
        }
        if (!isset($options["directory"])) {
            throw new Exception("Parameter 'directory' is required");
        }
        $options = array_merge($this->getOptionsDefault(), $options);
        $this->setLocale($options["category"], $options["locale"]);
        $this->setDefaultDomain($options["defaultDomain"]);
        $this->setDirectory($options["directory"]);
        $this->setDomain($options["defaultDomain"]);
    }

    /**
     * Gets default options
     * @return array
     */
    protected function getOptionsDefault()
    {
        return array(
            "category" => LC_ALL,
            "defaultDomain" => "messages"
        );
    }
}
