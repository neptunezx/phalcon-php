<?php

namespace Phalcon\Mvc;

use Phalcon\DiInterface;
use Phalcon\Mvc\UrlInterface;
use Phalcon\Mvc\Url\Exception;
use Phalcon\Mvc\RouterInterface;
use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Text;

/**
 * Phalcon\Mvc\Url
 *
 * This components helps in the generation of: URIs, URLs and Paths
 *
 * <code>
 * // Generate a URL appending the URI to the base URI
 * echo $url->get("products/edit/1");
 *
 * // Generate a URL for a predefined route
 * echo $url->get(
 *     [
 *         "for"   => "blog-post",
 *         "title" => "some-cool-stuff",
 *         "year"  => "2012",
 *     ]
 * );
 * </code>
 */
class Url implements UrlInterface, InjectionAwareInterface
{

    protected $_dependencyInjector;
    protected $_baseUri       = null;
    protected $_staticBaseUri = null;
    protected $_basePath      = null;
    protected $_router;

    /**
     * Sets the DependencyInjector container
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = dependencyInjector;
    }

    /**
     * Returns the DependencyInjector container
     */
    public function getDI()
    {
        return $this->_dependencyInjector;
    }

    /**
     * Sets a prefix for all the URIs to be generated
     *
     * <code>
     * $url->setBaseUri("/invo/");
     *
     * $url->setBaseUri("/invo/index.php/");
     * </code>
     */
    public function setBaseUri($baseUri)
    {
        $this->_baseUri = $baseUri;
        if ($this->_staticBaseUri === null) {
            $this->_staticBaseUri = $baseUri;
        }
        return $this;
    }

    /**
     * Sets a prefix for all static URLs generated
     *
     * <code>
     * $url->setStaticBaseUri("/invo/");
     * </code>
     */
    public function setStaticBaseUri($staticBaseUri)
    {
        $this->_staticBaseUri = $staticBaseUri;
        return $this;
    }

    /**
     * Returns the prefix for all the generated urls. By default /
     */
    public function getBaseUri()
    {

        $baseUri = $this->_baseUri;
        if ($baseUri === null) {

            if (isset($_SERVER["PHP_SELF"])) {
                $uri = phalcon_get_uri($_SERVER["PHP_SELF"]);
            } else {
                $uri = null;
            }

            if (!$uri) {
                $baseUri = "/";
            } else {
                $baseUri = "/" . $uri . "/";
            }

            $this->_baseUri = $baseUri;
        }
        return $baseUri;
    }

    /**
     * Returns the prefix for all the generated static urls. By default /
     */
    public function getStaticBaseUri()
    {
        $staticBaseUri = $this->_staticBaseUri;
        if ($staticBaseUri !== null) {
            return $staticBaseUri;
        }
        return $this->getBaseUri();
    }

    /**
     * Sets a base path for all the generated paths
     *
     * <code>
     * $url->setBasePath("/var/www/htdocs/");
     * </code>
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = $basePath;
        return $this;
    }

    /**
     * Returns the base path
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * Generates a URL
     *
     * <code>
     * // Generate a URL appending the URI to the base URI
     * echo $url->get("products/edit/1");
     *
     * // Generate a URL for a predefined route
     * echo $url->get(
     *     [
     *         "for"   => "blog-post",
     *         "title" => "some-cool-stuff",
     *         "year"  => "2015",
     *     ]
     * );
     *
     * // Generate a URL with GET arguments (/show/products?id=1&name=Carrots)
     * echo $url->get(
     *     "show/products",
     *     [
     *         "id"   => 1,
     *         "name" => "Carrots",
     *     ]
     * );
     *
     * // Generate an absolute URL by setting the third parameter as false.
     * echo $url->get(
     *     "https://phalconphp.com/",
     *     null,
     *     false
     * );
     * </code>
     */
    public function get($uri = null, $args = null, $local = null, $baseUri = null)
    {
        if ($local == null) {
            if (is_string($uri) && (Text::memstr($uri, "//") || Text::memstr($uri, ":"))) {
                if (preg_match("#^((//)|([a-z0-9]+://)|([a-z0-9]+:))#i", $uri)) {
                    $local = false;
                } else {
                    $local = true;
                }
            } else {
                $local = true;
            }
        }

        if (!is_string($baseUri)) {
            $baseUri = $this->getBaseUri();
        }

        if (is_array($uri)) {

            if (!isset($uri["for"])) {
                throw new Exception("It's necessary to define the route name with the parameter 'for'");
            }

            $routeName = $uri["for"];
            $router    = $this->_router;

            /**
             * Check if the router has not previously set
             */
            if (!is_object($router)) {

                $dependencyInjector = $this->_dependencyInjector;
                if ($dependencyInjector != "object") {
                    throw new Exception("A dependency injector container is required to obtain the 'router' service");
                }

                $router        = $dependencyInjector->getShared("router");
                $this->_router = $router;
            }

            /**
             * Every route is uniquely differenced by a name
             */
            $route = $router->getRouteByName(routeName);
            if (!is_object($route)) {
                throw new Exception("Cannot obtain a route using the name '" . $routeName . "'");
            }

            /**
             * Replace the patterns by its variables
             */
            $uri = self::replacePaths($route->getPattern(), $route->getReversedPaths(), $uri);
        }

        if ($local) {
            $strUri = (string) $uri;
            if ($baseUri == "/" && strlen($strUri) > 2 && $strUri[0] == '/' && $strUri[1] != '/') {
                $uri = $baseUri . substr($strUri, 1);
            } else {
                if ($baseUri == "/" && strlen($strUri) == 1 && $strUri[0] == '/') {
                    $uri = $baseUri;
                } else {
                    $uri = $baseUri . $strUri;
                }
            }
        }

        if ($args) {
            $queryString = self::httpBuildQuery($args);
            if (is_string($queryString) && strlen($queryString)) {
                if (strpos($uri, "?") !== false) {
                    $uri .= "&" . $queryString;
                } else {
                    $uri .= "?" . $queryString;
                }
            }
        }

        return uri;
    }

    /**
     * Generates a URL for a static resource
     *
     * <code>
     * // Generate a URL for a static resource
     * echo $url->getStatic("img/logo.png");
     *
     * // Generate a URL for a static predefined route
     * echo $url->getStatic(
     *     [
     *         "for" => "logo-cdn",
     *     ]
     * );
     * </code>
     */
    public function getStatic($uri = null)
    {
        return $this->get($uri, null, null, $this->getStaticBaseUri());
    }

    /**
     * Generates a local path
     */
    public function path($path = null)
    {
        return $this->_basePath . $path;
    }

    /**
     * Replace Marker
     *
     * @param boolean $named
     * @param string $pattern
     * @param array $paths
     * @param array $replacements
     * @param int $position
     * @param int $cursor
     * @param int $marker
     */
    private static function replaceMarker($pattern, $named, &$paths, &$replacements, &$position, &$cursor, &$marker)
    {
        $notValid = false;
        /*
         * $marker: string index of the start char (e.g. "{")
         * $cursor: string index of the character before end (e.g. "}")
         * $pattern: string to handle
         * $named: is named marker?
         * $replacements: parameter data to use
         */

        if ($named === true) {
            $length    = $cursor - $marker - 1;    //Length of the name
            $item      = substr($pattern, $marker + 1, $length); //The name
            $cursorVar = $marker + 1;
            $marker    = $marker + 1;
            for ($j = 0; $j < $length; ++$j) {
                $ch = $pattern[$cursorVar];
                if ($ch === "\0") {
                    $notValid = true;
                    break;
                }

                $z = ord($ch);
                if ($j === 0 && !(($z >= 97 && $z <= 122) || ($z >= 65 && $z <= 90))) {
                    $notValid = true;
                    break;
                }

                if (($z >= 97 && $z <= 122) || ($z >= 65 && $z <= 90) || ($z >= 48 &&
                    $z <= 57) || $ch === '-' || $ch === '_' || $ch === ':') {
                    if ($ch === ':') {
                        $variableLength = $cursorVar - $marker;
                        $variable       = substr($pattern, $marker, $variableLength);
                        break;
                    }
                } else {
                    $notValid = true;
                    break;
                }
                $cursorVar++;
            }
        }

        if ($notValid === false) {
            if (isset($paths[$position])) {
                if ($named === true) {
                    if (isset($variable) === true) {
                        $item   = $variable;
                        $length = $variableLength;
                    }

                    if (isset($replacements[$item]) === true) {
                        $position++;
                        return $replacements[$item];
                    }
                } else {
                    if (isset($paths[$position]) === true) {
                        $zv = $paths[$position];
                        if (is_string($zv) === true) {
                            if (isset($replacements[$zv]) === true) {
                                $position++;
                                return $replacements[$zv];
                            }
                        }
                    }
                }
            }

            $position++;
        }

        return null;
    }

    /**
     * Replace Paths
     *
     * @param string $pattern
     * @param array $paths
     * @param array $replacements
     * @return string|boolean
     * @throws Exception
     */
    private static function replacePaths($pattern, $paths, $replacements)
    {
        if (is_string($pattern) === false ||
            is_array($replacements) === false ||
            is_array($paths) === false) {
            throw new Exception('Invalid arguments supplied for phalcon_replace_paths()');
        }


        $l = strlen($pattern);

        if ($l <= 0) {
            return false;
        }

        if ($pattern[0] === '/') {
            $i = 1;
        } else {
            $i = 0;
        }

        if (empty($paths) === true) {
            return substr($pattern, 1);
        }

        $cursor             = 1;        //Cursor for $pattern; Ignoring the first character
        $marker             = null;
        $bracketCount       = 0;
        $parenthesesCount   = 0;
        $intermediate       = 0;
        $ch                 = null;
        $routeStr           = '';
        $position           = 1;
        $lookingPlaceholder = false;

        for ($i = 1; $i < $l; ++$i) {
            $ch = $pattern[$cursor];
            if ($ch === "\0") {
                break;
            }

            if ($parenthesesCount === 0 && $lookingPlaceholder === false) {
                if ($ch === '{') {
                    if ($bracketCount === 0) {
                        $marker       = $cursor;
                        $intermediate = 0;
                    }
                    ++$bracketCount;
                } else {
                    if ($ch === '}') {
                        --$bracketCount;
                        if ($intermediate > 0) {
                            if ($bracketCount === 0) {
                                $replace = self::replaceMarker($pattern, true, $paths, $replacements, $position, $cursor, $marker);
                                if (isset($replace) === true) {
                                    if (is_string($replace) === false) {
                                        $replace = (string) $replace;
                                    }

                                    $routeStr .= $replace;
                                }
                                ++$cursor;
                                continue;
                            }
                        }
                    }
                }
            }

            if ($bracketCount === 0 && $lookingPlaceholder === false) {
                if ($ch === '(') {
                    if ($parenthesesCount === 0) {
                        $marker       = $cursor;
                        $intermediate = 0;
                    }
                    ++$parenthesesCount;
                } else {
                    if ($ch === ')') {
                        --$parenthesesCount;
                        if ($intermediate > 0) {
                            if ($parenthesesCount === 0) {
                                $replace = self::replaceMarker($pattern, false, $paths, $replacements, $position, $cursor, $marker);

                                if (isset($replace) === true) {
                                    if (is_string($replace) === false) {
                                        $replace = (string) $replace;
                                    }

                                    $routeStr .= $replace;
                                }
                                ++$cursor;
                                continue;
                            }
                        }
                    }
                }
            }

            if ($bracketCount === 0 && $parenthesesCount === 0) {
                if ($lookingPlaceholder === true) {
                    if ($intermediate > 0) {
                        $chord = ord($ch);
                        if ($chord < 97 || $chord > 122 || $i === ($l - 1)) {
                            $replace = self::replaceMarker($pattern, false, $paths, $replacements, $position, $cursor, $marker);
                            if (isset($replace) === true) {
                                if (is_string($replace) === false) {
                                    $replace = (string) $replace;
                                }

                                $routeStr .= $replace;
                            }

                            $lookingPlaceholder = false;
                            continue;
                        }
                    }
                } else {
                    if ($ch === ':') {
                        $lookingPlaceholder = true;
                        $marker             = $cursor;
                        $intermediate       = 0;
                    }
                }
            }

            if ($bracketCount > 0 || $parenthesesCount > 0 ||
                $lookingPlaceholder === true) {
                ++$intermediate;
            } else {
                $routeStr .= $ch;
            }

            ++$cursor;
        }

        return $routeStr;
    }

    /**
     * Build HTTP Query
     *
     * @param array $params
     * @param string $sep
     * @return string
     */
    private static function httpBuildQuery($params, $sep = '&')
    {
        if (is_array($params) === false ||
            is_string($sep) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $d = '';

        foreach ($params as $key => $param) {
            if (isset($key) === false) {
                $d .= $sep . $param;
            } else {
                $d .= $sep . $key . '=' . $param;
            }
        }

        return substr($d, strlen($sep));
    }

}
