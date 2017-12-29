<?php

namespace Phalcon\Cli\Router;

use Phalcon\Kernel;
use Phalcon\Text;

/**
 * Phalcon\Cli\Router\Route
 *
 * This class represents every route added to the router
 *
 */
class Route
{
    protected $_pattern;

    protected $_compiledPattern;

    protected $_paths;

    protected $_converters;

    protected $_id;

    protected $_name;

    protected $_beforeMatch;

    protected $_delimiter;

    protected static $_uniqueId;

    protected static $_delimiterPath;

    const DEFAULT_DELIMITER = " ";

    /**
     * Phalcon\Cli\Router\Route constructor
     *
     * @param string $pattern
     * @param array $paths
     */
    public function __construct($pattern, $paths = null)
    {
        $delimiter = self::$_delimiterPath;
        if (!$delimiter) {
            $delimiter = self::DEFAULT_DELIMITER;
        }
        $this->_delimiter = $delimiter;
        $this->reConfigure($pattern, $paths);
        $uniqueId = self::$_uniqueId;
        if ($uniqueId === null) {
            $uniqueId = 0;
        }
        $routeId = $uniqueId;
        $this->_id = $routeId;
        self::$_uniqueId = $uniqueId + 1;
    }

    /**
     * Replaces placeholders from pattern returning a valid PCRE regular expression
     * @param string $pattern
     * @return string
     */
    public function compilePattern($pattern)
    {
        if (Text::memstr($pattern, ':')) {
            $idPattern = $this->_delimiter . '([a-zA-Z0-9\\_\\-]+)';
            if (Text::memstr($pattern, ':delimiter')) {
                $pattern = str_replace(':delimiter', $this->_delimiter, $pattern);
            }
            $part = $this->_delimiter . ':module';
            if (Text::memstr($pattern, $part)) {
                $pattern = str_replace($part, $idPattern, $pattern);
            }
            $part = $this->_delimiter . ':task';
            if (Text::memstr($pattern, $part)) {
                $pattern = str_replace($part, $idPattern, $pattern);
            }
            $part = $this->_delimiter . ':namespace';
            if (Text::memstr($pattern, $part)) {
                $pattern = str_replace($part, $idPattern, $pattern);
            }
            $part = $this->_delimiter . ':action';
            if (Text::memstr($pattern, $part)) {
                $pattern = str_replace($part, $idPattern, $pattern);
            }
            $part = $this->_delimiter . ':params';
            if (Text::memstr($pattern, $part)) {
                $pattern = str_replace($part, '(' . $this->_delimiter . '.*)*', $pattern);
            }
            $part = $this->_delimiter . ':int';
            if (Text::memstr($pattern, $part)) {
                $pattern = str_replace($part, $this->_delimiter . '([0-9]+)', $pattern);
            }
        }
        if (Text::memstr($pattern, '(')) {
            return '#^' . $pattern . '$#';
        }
        if (Text::memstr($pattern, '[')) {
            return '#^' . $pattern . '$#';
        }
        return $pattern;
    }

    /**
     * Extracts parameters from a string
     *
     * @param string|array $pattern
     * @return array|boolean
     */
    public function extractNamedParams($pattern)
    {
        $bracketCount = 0;
        $parenthesesCount = 0;
        $intermediate = 0;
        $numberMatches = 0;
        $marker = 0;
        $notValid = false;
        if (strlen($pattern)) {
            return false;
        }
        $matches = [];
        $route = '';
        foreach ($pattern as $cursor => $ch) {
            if ($parenthesesCount == 0) {
                if ($ch == '{') {
                    if ($bracketCount == 0) {
                        $marker = $cursor + 1;
                        $intermediate = 0;
                        $notValid = false;
                    }
                    $bracketCount++;
                } else {
                    if ($ch == '}') {
                        $bracketCount--;
                        if ($intermediate > 0) {
                            if ($bracketCount == 0) {
                                $numberMatches++;
                                $variable = null;
                                $regexp = null;
                                $item = (string)substr($pattern, $marker, $cursor - $marker);
                                for ($i = 0; $i < strlen($item); $i++) {
                                    if ($item[$i]) {
                                        break;
                                    }
                                    if ($i == 0 && !(($item[$i] >= 'a' && $item[$i] <= 'z') || ($item[$i] >= 'A' && $item[$i] <= 'Z'))) {
                                        $notValid = true;
                                        break;
                                    }
                                    if (($item[$i] >= 'a' && $item[$i] <= 'z') || ($item[$i] >= 'A' && $item[$i] <= 'Z') || ($item[$i] >= '0' && $item[$i] <= '9') || $item[$i] == '-' || $item[$i] == '_' || $item[$i] == ':') {
                                        if ($item[$i] == ':') {
                                            $variable = (string)substr($item, 0, $i);
                                            $regexp = (string)substr($item, $i + 1);
                                            break;
                                        }
                                    } else {
                                        $notValid = true;
                                        break;
                                    }
                                }
                                if (!$notValid) {
                                    $tmp = $numberMatches;
                                    if ($variable && $regexp) {
                                        $foundPattern = 0;
                                        foreach ($regexp as $ch1) {
                                            if ($ch1 == '\0') {
                                                break;
                                            }
                                            if (!$foundPattern) {
                                                if ($ch1 == '(') {
                                                    $foundPattern = 1;
                                                }
                                            } else {
                                                if ($ch1 == ')') {
                                                    $foundPattern = 2;
                                                    break;
                                                }
                                            }
                                        }
                                        if ($foundPattern != 2) {
                                            $route .= '(';
                                            $route .= $regexp;
                                            $route .= ')';
                                        } else {
                                            $route .= $regexp;
                                        }
                                        $matches[$variable] = $tmp;
                                    } else {
                                        $route .= '([^' . $this->_delimiter . ']*)';
                                        $matches[$item] = $tmp;
                                    }
                                } else {
                                    $route .= '{';
                                    $route .= $item;
                                    $route .= '}';
                                }
                                continue;
                            }
                        }
                    }
                }
            }
            if ($bracketCount == 0) {
                if ($ch == '(') {
                    $parenthesesCount++;
                } else {
                    if ($ch == ')') {
                        $parenthesesCount--;
                        if ($parenthesesCount == 0) {
                            $numberMatches++;
                        }
                    }
                }
            }
            if ($bracketCount > 0) {
                $intermediate++;
            } else {
                $route .= $ch;
            }
        }
        return [$route, $matches];
    }


    /**
     * Reconfigure the route adding a new pattern and a set of paths
     *
     * @param string $pattern
     * @param array|null $paths
     * @throws Exception
     */
    public function reConfigure($pattern, $paths = null)
    {
        if (is_string($paths) === false) {
         //   throw new Exception('Invalid annotations reader');
        }
        if ($paths !== null) {
            if (is_string($paths)) {
                $moduleName = null;
                $taskName = null;
                $actionName = null;
                $part = explode('::', $paths);
                switch (count($part)) {
                    case 3:
                        $moduleName = $paths[0];
                        $taskName = $paths[1];
                        $actionName = $paths[2];
                        break;
                    case 2:
                        $taskName = $paths[0];
                        $actionName = $paths[1];
                        break;
                    case 1:
                        $taskName = $paths[0];
                        break;
                }
                $routePaths = [];
                if ($moduleName !== null) {
                    $routePaths['module'] = $moduleName;
                }
                if ($taskName !== null) {
                    if (Text::memstr($taskName, "\\")) {
                        $realClassName = Kernel::getClassNameFromClass($taskName);
                        $namespaceName = Kernel::getNamespaceFromClass($taskName);
                        if ($namespaceName) {
                            $routePaths['namespace'] = $namespaceName;
                        }
                    } else {
                        $realClassName = $taskName;
                    }
                    $routePaths['task'] = Text::uncamelize($realClassName);
                }
                if ($actionName !== null) {
                    $routePaths['action'] = $actionName;
                }
            } else {
                $routePaths = $paths;
            }
        } else {
            $routePaths = [];
        }
        if (is_array($routePaths) === false) {
            throw new Exception("The route contains invalid paths");
        }
        /**
         * If the route starts with '#' we assume that it is a regular expression
         */
        if (Text::startsWith($pattern, '#')) {
            /**
             * The route has named parameters so we need to extract them
             */
            if (Text::memstr($pattern, '{')) {
                $extracted = $this->extractNamedParams($pattern);
                $pcrePattern = $extracted[0];
                if (is_array($extracted[1])) {
                    $routePaths = array_merge($routePaths, $extracted[1]);
                }
            } else {
                $pcrePattern = $pattern;
            }
            $compiledPattern = $this->compilePattern($pcrePattern);
        } else {
            if (Text::memstr($pattern, ':delimiter')) {
                $pattern = str_replace(':delimiter', $this->_delimiter, $pattern);
            }
            /**
             * Transform the route's pattern to a regular expression
             */
            $compiledPattern = $pattern;
        }
        /**
         * Update the original pattern
         */
        $this->_pattern = $pattern;
        /**
         * Update the compiled pattern
         */
        $this->_compiledPattern = $compiledPattern;
        /**
         * Update the route's paths
         */
        $this->_paths = $routePaths;
    }

    /**
     * Returns the route's name
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the route's name
     *
     *<code>
     * $router->add(
     *     "/about",
     *     [
     *         "controller" => "about",
     *     ]
     * )->setName("about");
     *</code>
     * @param string $name
     * @return Route
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * Sets a callback that is called if the route is matched.
     * The developer can implement any arbitrary conditions here
     * If the callback returns false the route is treated as not matched
     *
     * @param  $callback
     * @return Route
     */
    public function beforeMatch($callback)
    {
        $this->_beforeMatch = $callback;
        return $this;
    }

    /**
     * Returns the 'before match' callback if any
     *
     * @return mixed
     */
    public function getBeforeMatch()
    {
        return $this->_beforeMatch;
    }

    /**
     * Returns the route's id
     * @return string
     */
    public function getRouteId()
    {
        return $this->_id;
    }

    /**
     * Returns the route's pattern
     * @return string
     */
    public function getPattern()
    {
        return $this->_pattern;
    }

    /**
     * Returns the route's compiled pattern
     * @return string
     */
    public function getCompiledPattern()
    {
        return $this->_compiledPattern;
    }

    /**
     * Returns the paths
     * @return array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * Returns the paths using positions as keys and names as values
     * @return array
     */
    public function getReversedPaths()
    {
        $reversed = [];
        foreach ($this->_paths as $path => $position) {
            $reversed[$position] = $path;
        }
        return $reversed;
    }

    /**
     * Adds a converter to perform an additional transformation for certain parameter
     *
     * @param string $name
     * @param callable $converter
     * @return Route
     * @throws Exception
     */
    public function convert($name, $converter)
    {
        if (is_string($name) === false) {
            throw new Exception('\'Invalid parameter type.\'');
        }
        $this->_converters[$name] = $converter;
        return $this;
    }

    /**
     * Returns the router converter
     * @return array
     */
    public function getConverters()
    {
        return $this->_converters;
    }

    /**
     * Resets the internal route id generator
     */
    public static function reset()
    {
        self::$_uniqueId = null;
    }

    /**
     * Set the routing delimiter
     * @param string | null $delimiter
     */
    public static function delimiter($delimiter = null)
    {
        self::$_delimiterPath = $delimiter;
    }

    /**
     * Get routing delimiter
     * @return string
     */
    public static function getDelimiter()
    {
        $delimiter = self::$_delimiterPath;
        if (!$delimiter){
            $delimiter = self::DEFAULT_DELIMITER;
        }
        return $delimiter;
    }

}