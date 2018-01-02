<?php

namespace Phalcon\Mvc\Router;

use \Phalcon\Text;
use \Phalcon\Kernel;

/**
 * Phalcon\Mvc\Router\Route
 *
 * This class represents every route added to the router
 */
class Route implements RouteInterface
{

    /**
     * Pattern
     *
     * @var null|string
     * @access protected
     */
    protected $_pattern;

    /**
     * Compiled Pattern
     *
     * @var null|string
     * @access protected
     */
    protected $_compiledPattern;

    /**
     * Paths
     *
     * @var null|array
     * @access protected
     */
    protected $_paths;

    /**
     * Methods
     *
     * @var null|array|string
     * @access protected
     */
    protected $_methods;

    /**
     * Hostname
     *
     * @var null|string
     * @access protected
     */
    protected $_hostname;

    /**
     * Converters
     *
     * @var null|array
     * @access protected
     */
    protected $_converters;

    /**
     * ID
     *
     * @var null|int
     * @access protected
     */
    protected $_id;

    /**
     * Name
     *
     * @var null|string
     * @access protected
     */
    protected $_name;

    /**
     * Before Match
     *
     * @var null|callback
     * @access protected
     */
    protected $_beforeMatch;
    protected $_match;
    protected $_group;

    /**
     * Unique ID
     *
     * @var null|int
     * @access protected
     */
    protected static $_uniqueId;

    /**
     * \Phalcon\Mvc\Router\Route constructor
     *
     * @param string            $pattern
     * @param array|null        $paths
     * @param array|string|null $httpMethods
     *
     * @throws Exception
     */
    public function __construct($pattern, $paths = null, $httpMethods = null)
    {
        if (is_string($pattern) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Configure the route (extract parameters, paths, etc)
        $this->reConfigure($pattern, $paths);

        //Update the HTTP method constraints
        $this->_methods = $httpMethods;

        //Get the unique Id from the static member _uniqueId
        $uniqueId = self::$_uniqueId;
        if (is_null($uniqueId) === true) {
            $uniqueId = 0;
        }

        //TODO: Add a function that increases static members
        $this->_id       = $uniqueId;
        self::$_uniqueId = $uniqueId + 1;
    }

    /**
     * Replaces placeholders from pattern returning a valid PCRE regular expression
     *
     * @param string $pattern
     *
     * @return string
     * @throws Exception
     */
    public function compilePattern($pattern)
    {
        if (is_string($pattern) === false) {
            throw new Exception('Invalid parameter type.');
        }


        //If a pattern contains ':', maybe there are placeholders to replace
        if (Text::memstr($pattern, ':') !== false) {

            // This is a pattern for valid identifiers
            $idPattern = "/([\\w0-9\\_\\-]+)";

            // Replace the module part
            if (Text::memstr($pattern, "/:module")) {
                $pattern = str_replace("/:module", $idPattern, $pattern);
            }

            // Replace the controller placeholder
            if (Text::memstr($pattern, "/:controller")) {
                $pattern = str_replace("/:controller", $idPattern, $pattern);
            }

            // Replace the namespace placeholder
            if (Text::memstr($pattern, "/:namespace")) {
                $pattern = str_replace("/:namespace", $idPattern, $pattern);
            }

            // Replace the action placeholder
            if (Text::memstr($pattern, "/:action")) {
                $pattern = str_replace("/:action", $idPattern, $pattern);
            }

            // Replace the params placeholder
            if (Text::memstr($pattern, "/:params")) {
                $pattern = str_replace("/:params", "(/.*)*", $pattern);
            }

            // Replace the int placeholder
            if (Text::memstr($pattern, "/:int")) {
                $pattern = str_replace("/:int", "/([0-9]+)", $pattern);
            }
        }

        // Check if the pattern has parentheses in order to add the regex delimiters
        if (Text::memstr($pattern, "(")) {
            return "#^" . $pattern . "$#u";
        }

        // Square brackets are also checked
        if (Text::memstr($pattern, "[")) {
            return "#^" . $pattern . "$#u";
        }

        return $pattern;
    }

    /**
     * Set one or more HTTP methods that constraint the matching of the route
     * <code>
     * $route->via('GET');
     * $route->via(array('GET', 'POST'));
     * </code>
     *
     * @param string|array $httpMethods
     *
     * @return \Phalcon\Mvc\Router\Route
     */
    public function via($httpMethods)
    {
        $this->_methods = $httpMethods;
        return $this;
    }

    /**
     * Extracts parameters from a string
     *
     * @param string $pattern
     *
     * @return array|boolean
     */
    public function extractNamedParams($pattern)
    {
        if (!is_string($pattern)) {
            throw new Exception("Invalid parameter type.");
        }
        $prevCh           = '\0';
        $bracketCount     = 0;
        $parenthesesCount = 0;
        $notValid         = false;
        $marker           = 0;
        $intermediate     = 0;
        $numberMatches    = 0;

        if (strlen($pattern) <= 0) {
            return false;
        }

        $matches = [];
        $route   = "";

        $count = strlen($pattern);
        for ($cursor = 0; $cursor < $count; ++$cursor) {
            $ch = $pattern[$cursor];

            if ($parenthesesCount == 0) {
                if ($ch == '{') {
                    if ($bracketCount == 0) {
                        $marker       = $cursor + 1;
                        $intermediate = 0;
                        $notValid     = false;
                    }
                    $bracketCount++;
                } else {
                    if ($ch == '}') {
                        $bracketCount--;
                        if ($intermediate > 0) {
                            if ($bracketCount == 0) {
                                $numberMatches++;
                                $variable = null;
                                $regexp   = null;
                                $item     = substr(
                                    $pattern, $marker, $cursor - $marker
                                );

                                for ($cursorVar = 0; $cursorVar < strlen($item); ++$cursorVar) {
                                    $ch = $item[$cursorVar];
                                    if ($ch == '\0') {
                                        break;
                                    }

                                    if ($cursorVar == 0 && !(($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z'))
                                    ) {
                                        $notValid = true;
                                        break;
                                    }

                                    if (($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z') || ($ch >= '0' && $ch <= '9') || $ch == '-' || $ch == '_' || $ch == ':'
                                    ) {
                                        if ($ch == ':') {
                                            $variable = (string) substr(
                                                    $item, 0, $cursorVar
                                            );
                                            $regexp   = (string) substr(
                                                    $item, $cursorVar + 1
                                            );
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
                                        for ($i = 0; $i < strlen($regexp); ++$i) {
                                            $ch = $regexp[$i];

                                            if ($ch == '\0') {
                                                break;
                                            }
                                            if (!$foundPattern) {
                                                if ($ch == '(') {
                                                    $foundPattern = 1;
                                                }
                                            } else {
                                                if ($ch == ')') {
                                                    $foundPattern = 2;
                                                    break;
                                                }
                                            }
                                        }

                                        if ($foundPattern != 2) {
                                            $route .= "(" . $regexp . ")";
                                        } else {
                                            $route .= $regexp;
                                        }
                                        $matches[$variable] = $tmp;
                                    } else {
                                        $route          .= "([^/]*)";
                                        $matches[$item] = $tmp;
                                    }
                                } else {
                                    $route .= "{" . $item . "}";
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
                if ($parenthesesCount == 0 && $prevCh != '\\') {
                    if ($ch == '.' || $ch == '+' || $ch == '|' || $ch == '#'
                    ) {
                        $route .= '\\';
                    }
                }
                $route  .= $ch;
                $prevCh = $ch;
            }
        }
        return [$route, $matches];
    }

    /**
     * Reconfigure the route adding a new pattern and a set of paths
     *
     * @param string $pattern
     * @param        $paths
     *
     * @throws Exception
     */
    public function reConfigure($pattern, $paths = null)
    {
        if (is_string($pattern) === false) {
            throw new Exception('The pattern must be string');
        }

        $routePaths = self::getRoutePaths($paths);

        /**
         * If the route starts with '#' we assume that it is a regular expression
         */
        if (!Text::startsWith($pattern, "#")) {

            if (Text::memstr($pattern, "{")) {
                /**
                 * The route has named parameters so we need to extract them
                 */
                $extracted   = $this->extractNamedParams($pattern);
                $pcrePattern = $extracted[0];
                $routePaths  = array_merge($routePaths, $extracted[1]);
            } else {
                $pcrePattern = $pattern;
            }

            /**
             * Transform the route's pattern to a regular expression
             */
            $compiledPattern = $this->compilePattern($pcrePattern);
        } else {
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
     * Returns routePaths
     *
     * @return array
     */
    public static function getRoutePaths($paths = null)
    {
        if ($paths !== null) {
            if (is_string($paths)) {

                $moduleName     = null;
                $controllerName = null;
                $actionName     = null;

                // Explode the short paths using the :: separator
                $parts = explode("::", $paths);

                // Create the array paths dynamically
                switch (count($parts)) {

                    case 3:
                        $moduleName     = $parts[0];
                        $controllerName = $parts[1];
                        $actionName     = $parts[2];
                        break;

                    case 2:
                        $controllerName = $parts[0];
                        $actionName     = $parts[1];
                        break;

                    case 1:
                        $controllerName = $parts[0];
                        break;
                }

                $routePaths = [];

                // Process module name
                if ($moduleName !== null) {
                    $routePaths["module"] = $moduleName;
                }

                // Process controller name
                if ($controllerName !== null) {

                    // Check if we need to obtain the namespace
                    if (Text::memstr($controllerName, "\\")) {

                        // Extract the real class name from the namespaced class
                        $realClassName = Kernel::getClassNameFromClass(
                                $controllerName
                        );

                        // Extract the namespace from the namespaced class
                        $namespaceName = Kernel::getNamespaceFromClass(
                                $controllerName
                        );

                        // Update the namespace
                        if ($namespaceName) {
                            $routePaths["namespace"] = $namespaceName;
                        }
                    } else {
                        $realClassName = $controllerName;
                    }
                    // Always pass the controller to lowercase
                    $routePaths["controller"] = strtolower($realClassName);
                }


                // Process action name
                if ($actionName !== null) {
                    $routePaths["action"] = $actionName;
                }
            } else {
                $routePaths = $paths;
            }
        } else {
            $routePaths = [];
        }

        if (!is_array($routePaths)) {
            throw new Exception("The route contains invalid paths");
        }
        return $routePaths;
    }

    /**
     * Returns the route's name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the route's name
     * <code>
     * $router->add('/about', array(
     *     'controller' => 'about'
     * ))->setName('about');
     * </code>
     *
     * @param string $name
     *
     * @return \Phalcon\Mvc\Router\Route
     * @throws Exception
     */
    public function setName($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_name = $name;

        return $this;
    }

    /**
     * Sets a callback that is called if the route is matched.
     * The developer can implement any arbitrary conditions here
     * If the callback returns false the route is treaded as not matched
     *
     * @param callback $callback
     *
     * @return \Phalcon\Mvc\Router\Route
     * @throws Exception
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
     * Allows to set a callback to handle the request directly in the route
     *
     * <code>
     * $router->add(
     *     "/help",
     *     []
     * )->match(
     *     function () {
     *         return $this->getResponse()->redirect("https://support.google.com/", true);
     *     }
     * );
     * </code>
     */
    public function match($callback)
    {
        $this->_match = $callback;
        return $this;
    }

    /**
     * Returns the 'match' callback if any
     *
     * @return
     */
    public function getMatch()
    {
        return $this->_match;
    }

    /**
     * Returns the route's id
     *
     * @return string
     */
    public function getRouteId()
    {
        return $this->_id;
    }

    /**
     * Returns the route's pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->_pattern;
    }

    /**
     * Returns the route's compiled pattern
     *
     * @return string
     */
    public function getCompiledPattern()
    {
        return $this->_compiledPattern;
    }

    /**
     * Returns the paths
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * Returns the paths using positions as keys and names as values
     *
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
     * Sets a set of HTTP methods that constraint the matching of the route (alias of via)
     * <code>
     * $route->setHttpMethods('GET');
     * $route->setHttpMethods(array('GET', 'POST'));
     * </code>
     *
     * @param string|array $httpMethods
     *
     * @return \Phalcon\Mvc\Router\Route
     * @throws Exception
     */
    public function setHttpMethods($httpMethods)
    {
        $this->_methods = $httpMethods;
        return $this;
    }

    /**
     * Returns the HTTP methods that constraint matching the route
     *
     * @return string|array
     */
    public function getHttpMethods()
    {
        return $this->_methods;
    }

    /**
     * Sets a hostname restriction to the route
     * <code>
     * $route->setHostname('localhost');
     * </code>
     *
     * @param string $hostname
     *
     * @return \Phalcon\Mvc\Router\Route
     * @throws Exception
     */
    public function setHostname($hostname)
    {
        if (is_string($hostname) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_hostname = $hostname;
        return $this;
    }

    /**
     * Returns the hostname restriction if any
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->_hostname;
    }

    /**
     * Sets the group associated with the route
     *
     * @param GroupInterface $group
     * @return Route
     */
    public function setGroup($group)
    {
        $this->_group = $group;
        return $this;
    }

    /**
     * Returns the group associated with the route
     *
     * @return GroupInterface|null
     */
    public function getGroup()
    {
        return $this->_group;
    }

    /**
     * Adds a converter to perform an additional transformation for certain parameter
     *
     * @param string   $name
     * @param $converter
     *
     * @return \Phalcon\Mvc\Router\Route
     * @throws Exception
     */
    public function convert($name, $converter)
    {
        if (is_string($name) === false || is_callable($converter) === false) {
            throw new Exception('Invalid parameter type.');
        }
        $this->_converters[$name] = $converter;

        return $this;
    }

    /**
     * Returns the router converter
     *
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
        self::$_uniqueId = 0;
    }

}
