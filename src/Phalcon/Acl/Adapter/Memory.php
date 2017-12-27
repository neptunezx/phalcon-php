<?php

namespace Phalcon\Acl\Adapter;

use Phalcon\Acl;
use Phalcon\Acl\Adapter;
use Phalcon\Acl\Role;
use Phalcon\Acl\RoleInterface;
use Phalcon\Acl\Resource;
use Phalcon\Acl\Exception;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Acl\RoleAware;
use Phalcon\Acl\ResourceAware;
use Phalcon\Acl\ResourceInterface;

/**
 * Phalcon\Acl\Adapter\Memory
 *
 * Manages ACL lists in memory
 *
 * <code>
 * $acl = new \Phalcon\Acl\Adapter\Memory();
 *
 * $acl->setDefaultAction(
 *     \Phalcon\Acl::DENY
 * );
 *
 * // Register roles
 * $roles = [
 *     "users"  => new \Phalcon\Acl\Role("Users"),
 *     "guests" => new \Phalcon\Acl\Role("Guests"),
 * ];
 * foreach ($roles as $role) {
 *     $acl->addRole($role);
 * }
 *
 * // Private area resources
 * $privateResources = [
 *     "companies" => ["index", "search", "new", "edit", "save", "create", "delete"],
 *     "products"  => ["index", "search", "new", "edit", "save", "create", "delete"],
 *     "invoices"  => ["index", "profile"],
 * ];
 *
 * foreach ($privateResources as $resourceName => $actions) {
 *     $acl->addResource(
 *         new \Phalcon\Acl\Resource($resourceName),
 *         $actions
 *     );
 * }
 *
 * // Public area resource
 * $publicResources = [
 *     "index"   => ["index"],
 *     "about"   => ["izndex"],
 *     "session" => ["index", "register", "start", "end"],
 *     "contact" => ["index", "send"],
 * ];
 * 
 * foreach ($publicResources as $resourceName => $actions) {
 *     $acl->addResource(
 *         new \zPhalcon\Acl\Resource($resourceName),z
 *         $actions
 *     );z
 * }
 * z
 * // Grant access to public areas to both users and guests
 * foreach ($roles as $role){
 *     foreach ($publicResources as $resource => $actions) {
 *         $acl->allow($role->getName(), $resource, "*");
 *     }
 * }
 *
 * // Grant access to private area to role Users
 * foreach ($privateResources as $resource => $actions) {
 *     foreach ($actions as $action) {
 *         $acl->allow("Users", $resource, $action);
 *     }
 * }
 * </code>
 */
class Memory extends Adapter
{

    /**
     * Roles Names
     *
     * @var array
     * @access protected
     */
    protected $_rolesNames;

    /**
     * Roles
     *
     * @var array
     * @access protected
     */
    protected $_roles;

    /**
     * Resource Names
     *
     * @var array
     * @access protected
     */
    protected $_resourcesNames;

    /**
     * Resources
     *
     * @var array
     * @access protected
     */
    protected $_resources;

    /**
     * Access
     *
     * @var array
     * @access protected
     */
    protected $_access;

    /**
     * Role Inherits
     *
     * @var array
     * @access protected
     */
    protected $_roleInherits;

    /**
     * Access List
     *
     * @var array
     * @access protected
     */
    protected $_accessList;

    /**
     * Function List
     *
     * @var mixed
     */
    protected $_func;

    /**
     * Default action for no arguments is allow
     *
     * @var mixed
     */
    protected $_noArgumentsDefaultAction = Acl::ALLOW;

    /**
     * \Phalcon\Acl\Adapter\Memory constructor
     */
    public function __construct()
    {
        $this->_resourcesNames = array('*' => true);
        $this->_accessList     = array('*!*' => true);
        $this->_roleInherits   = [];
        $this->_roles          = [];
        $this->_rolesNames     = [];
        $this->_resources      = [];
        $this->_func           = [];
        $this->_access         = [];
    }

    /**
     * Adds a role to the ACL list. Second parameter allows inheriting access data from other existing role
     *
     * Example:
     * <code>
     * $acl->addRole(
     *     new Phalcon\Acl\Role("administrator"),
     *     "consultant"
     * );
     *
     * $acl->addRole("administrator", "consultant");
     * </code>
     *
     * @param  array|string         accessInherits
     * @param  RoleInterface|string role
     */
    public function addRole($role, $accessInherits = null)
    {
        $object = null;
        if (is_object($role) === true &&
            $role instanceof RoleInterface === true) {
            $roleName = $role->getName();
            $object   = $role;
        } elseif (is_string($role) === true) {
            $roleName = $role;
            $object   = new Role($role);
        } else {
            throw new Exception("Role must be either an string or implement RoleInterface");
        }

        if (isset($this->_rolesNames[$roleName]) === true) {
            return false;
        }

        $this->_roles[]               = $object;
        $this->_rolesNames[$roleName] = true;

        if (is_null($accessInherits) === false) {
            return $this->addInherit($roleName, $accessInherits);
        }

        return true;
    }

    /**
     * Do a role inherit from another existing role
     *
     * @param string $roleName
     * @param string|\Phalcon\Acl\RoleInterface $roleToInherit
     * @return boolean
     * @throws Exception
     */
    public function addInherit($roleName, $roleToInherit)
    {
        if (is_string($roleName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_rolesNames[$roleName]) === false) {
            throw new Exception("Role '" . $roleName . "' does not exist in the role list");
        }

        //Determine roleInheritName
        if (is_object($roleToInherit) === true &&
            $roleToInherit instanceof RoleInterface === true) {
            $roleInheritName = $roleToInherit->getName();
        } elseif (is_string($roleToInherit) === true) {
            $roleInheritName = $roleToInherit;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        /**
         * Deep inherits
         */
        if (isset($this->_roleInherits[$roleInheritName])) {
            foreach ($this->_roleInherits[$roleInheritName] as $deepInheritName) {
                $this->addInherit($roleName, $deepInheritName);
            }
        }

        /**
         * Check if the role to inherit is valid
         */
        if (!isset($this->_rolesNames[$roleInheritName])) {
            throw new Exception("Role '" . $roleInheritName . "' (to inherit) does not exist in the role list");
        }

        if ($roleName == $roleInheritName) {
            return false;
        }

        if (!isset($this->_roleInherits[$roleName])) {
            $this->_roleInherits[$roleName] = [];
        }

        $this->_roleInherits[$roleName][] = $roleInheritName;

        return true;
    }

    /**
     * Check whether role exist in the roles list
     *
     * @param string $roleName
     * @return boolean
     * @throws Exception
     */
    public function isRole($roleName)
    {
        if (is_string($roleName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->_rolesNames[$roleName]);
    }

    /**
     * Check whether resource exist in the resources list
     *
     * @param string $resourceName
     * @return boolean
     * @throws Exception
     */
    public function isResource($resourceName)
    {
        if (is_string($resourceName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($this->_resourcesNames[$resourceName]);
    }

    /**
     * Adds a resource to the ACL list
     *
     * Access names can be a particular action, by example
     * search, update, delete, etc or a list of them
     *
     * Example:
     * <code>
     * // Add a resource to the the list allowing access to an action
     * $acl->addResource(
     *     new Phalcon\Acl\Resource("customers"),
     *     "search"
     * );
     *
     * $acl->addResource("customers", "search");
     *
     * // Add a resource  with an access list
     * $acl->addResource(
     *     new Phalcon\Acl\Resource("customers"),
     *     [
     *         "create",
     *         "search",
     *     ]
     * );
     *
     * $acl->addResource(
     *     "customers",
     *     [
     *         "create",
     *         "search",
     *     ]
     * );
     * </code>
     *
     * @param   \Phalcon\Acl\Resource |string resourceValue
     * @param   array|string accessList
     * @return  void
     * @throws Exception type error
     */
    public function addResource($resource, $accessList = null)
    {
        $object = null;
        if (is_object($resource) === true &&
            $resource instanceof ResourceInterface === true) {
            $resourceName = $resource->getName();
            $object       = $resource;
        } elseif (is_string($resource) === true) {
            $resourceName = $resource;
            $object       = new Resource($resource);
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (!isset($this->_resourcesNames[$resourceName])) {
            $this->_resources[]                   = $object;
            $this->_resourcesNames[$resourceName] = true;
        }

        return $this->addResourceAccess($resourceName, $accessList);
    }

    /**
     * Adds access to resources
     *
     * @param string $resourceName
     * @param array|string|null $accessList
     * @return boolean
     * @throws Exception
     */
    public function addResourceAccess($resourceName, $accessList)
    {
        if (is_string($resourceName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($this->_resourcesNames[$resourceName]) === false) {
            throw new Exception("Resource '" . $resourceName . "' does not exist in ACL");
        }

        if (is_array($accessList) === true) {
            foreach ($accessList as $accessName) {
                $key = $resourceName . '!' . $accessName;
                if (isset($this->_accessList[$key]) === false) {
                    $this->_accessList[$key] = true;
                }
            }
        } elseif (is_string($accessList) === true) {
            $key = $resourceName . '!' . $accessList;
            if (isset($this->_accessList[$key]) === false) {
                $this->_accessList[$key] = true;
            }
        } elseif (is_null($accessList) === false) {
            //@note null can be passed by addResource() and is not handled
            throw new Exception('Invalid value for accessList');
        }

        return true;
    }

    /**
     * Removes an access from a resource
     *
     * @param string $resourceName
     * @param array|string $accessList
     * @throws Exception
     */
    public function dropResourceAccess($resourceName, $accessList)
    {
        if (is_string($resourceName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($accessList) === true) {
            foreach ($accessList as $accessName) {
                unset($this->_accessList[$resourceName . '!' . $accessName]);
            }
        } elseif (is_string($accessList) === true) {
            unset($this->_accessList[$resourceName . '!' . $accessList]);
        } else {
            throw new Exception('Invalid parameter type.');
        }
    }

    /**
     * Allows or denies the access to a resource
     *
     * @param string $roleName
     * @param string $resourceName
     * @param string|array $access
     * @param int $action
     * @throws Exception
     */
    protected function _allowOrDeny($roleName, $resourceName, $access, $action, $func = null)
    {
        if (!isset($this->_rolesNames[$roleName])) {
            throw new Exception("Role '" . $roleName . "' does not exist in ACL");
        }

        if (!isset($this->_resourcesNames[$resourceName])) {
            throw new Exception("Resource '" . $resourceName . "' does not exist in ACL");
        }

        $accessList = $this->_accessList;

        if (is_array($access)) {
            foreach ($access as $accessName) {
                $accessKey = $resourceName . "!" . $accessName;
                if (!isset($accessList[$accessKey])) {
                    throw new Exception("Access '" . $accessName . "' does not exist in resource '" . $resourceName . "'");
                }
            }

            foreach ($access as $accessName) {

                $accessKey                 = $roleName . "!" . $resourceName . "!" . $accessName;
                $this->_access[$accessKey] = $action;
                if ($func != null) {
                    $this->_func[$accessKey] = $func;
                }
            }
        } else {

            if ($access != "*") {
                $accessKey = $resourceName . "!" . $access;
                if (!isset($accessList[$accessKey])) {
                    throw new Exception("Access '" . $access . "' does not exist in resource '" . $resourceName . "'");
                }
            }

            $accessKey = $roleName . "!" . $resourceName . "!" . $access;

            /**
             * Define the access action for the specified accessKey
             */
            $this->_access[$accessKey] = $action;
            if ($func != null) {
                $this->_func[$accessKey] = $func;
            }
        }
    }

    /**
     * Allow access to a role on a resource
     *
     * You can use '*' as wildcard
     *
     * Example:
     * <code>
     * //Allow access to guests to search on customers
     * $acl->allow("guests", "customers", "search");
     *
     * //Allow access to guests to search or create on customers
     * $acl->allow("guests", "customers", ["search", "create"]);
     *
     * //Allow access to any role to browse on products
     * $acl->allow("*", "products", "browse");
     *
     * //Allow access to any role to browse on any resource
     * $acl->allow("*", "*", "browse");
     * </code>
     */
    public function allow($roleName, $resourceName, $access, $func = null)
    {
        if ($roleName != "*") {
            return $this->_allowOrDeny($roleName, $resourceName, $access, Acl::ALLOW, $func);
        } else {
            foreach ($this->_rolesNames as $innerRoleName => $_) {
                $this->_allowOrDeny($innerRoleName, $resourceName, $access, Acl::ALLOW, $func);
            }
        }
    }

    /**
     * Deny access to a role on a resource
     *
     * You can use '*' as wildcard
     *
     * Example:
     * <code>
     * //Deny access to guests to search on customers
     * $acl->deny('guests', 'customers', 'search');
     *
     * //Deny access to guests to search or create on customers
     * $acl->deny('guests', 'customers', array('search', 'create'));
     *
     * //Deny access to any role to browse on products
     * $acl->deny('*', 'products', 'browse');
     *
     * //Deny access to any role to browse on any resource
     * $acl->deny('*', '*', 'browse');
     * </code>
     *
     * @param string $roleName
     * @param string $resourceName
     * @param string|array $access
     */
    public function deny($roleName, $resourceName, $access, $func = null)
    {
        if ($roleName != "*") {
            return $this->_allowOrDeny($roleName, $resourceName, $access, Acl::DENY, $func);
        } else {
            foreach ($this->_rolesNames as $innerRoleName => $_) {
                $this->_allowOrDeny($innerRoleName, $resourceName, $access, Acl::DENY, $func);
            }
        }
    }

    /**
     * Check whether a role is allowed to access an action from a resource
     *
     * <code>
     * //Does andres have access to the customers resource to create?
     * $acl->isAllowed('andres', 'Products', 'create');
     *
     * //Do guests have access to any resource to edit?
     * $acl->isAllowed('guests', '*', 'edit');
     * </code>
     *
     * @param RoleInterface|RoleAware|string $roleName
     * @param ResourceInterface|ResourceAware|string $resourceName
     * @param string $access
     * @param array $parameters
     * @return boolean
     * @throws Exception
     */
    public function isAllowed($roleName, $resourceName, $access, array $parameters = null)
    {
        $haveAccess     = null;
        $resourceObject = null;
        $funcAccess     = null;
        $roleObject     = null;
        $inheritedRoles = null;
        if (is_object($roleName)) {
            if ($roleName instanceof RoleAware) {
                $roleObject = $roleName;
                $roleName   = $roleObject->getRoleName();
            } else if ($roleName instanceof RoleInterface) {
                $roleName = $roleName->getName();
            } else {
                throw new Exception("Object passed as roleName must implement Phalcon\\Acl\\RoleAware or Phalcon\\Acl\\RoleInterface");
            }
        }

        if (is_object($resourceName)) {
            if ($resourceName instanceof ResourceAware) {
                $resourceObject = $resourceName;
                $resourceName   = $resourceObject->getResourceName();
            } else if ($resourceName instanceof ResourceInterface) {
                $resourceName = $resourceName->getName();
            } else {
                throw new Exception("Object passed as resourceName must implement Phalcon\\Acl\\ResourceAware or Phalcon\\Acl\\ResourceInterface");
            }
        }

        if (is_null($access)) {
            $access = '';
        }
        if (!is_string($access)) {
            throw new Exception('Parameter access should be a string type');
        }

        $this->_activeRole     = $roleName;
        $this->_activeResource = $resourceName;
        $this->_activeAccess   = $access;
        $accessList            = $this->_access;
        $eventsManager         = $this->_eventsManager;
        $funcList              = $this->_func;

        if (is_object($eventsManager)) {
            if ($eventsManager->fire("acl:beforeCheckAccess", $this) === false) {
                return false;
            }
        }

        /**
         * Check if the role exists
         */
        $rolesNames = $this->_rolesNames;
        if (!isset($rolesNames[$roleName])) {
            return ($this->_defaultAccess == Acl::ALLOW);
        }

        $accessKey = $roleName . "!" . $resourceName . "!" . $access;

        /**
         * Check if there is a direct combination for role-resource-access
         */
        if (isset($accessList[$accessKey])) {
            $haveAccess = $accessList[$accessKey];
        }

        $funcAccess = isset($funcList[$accessKey]) ? $funcList[$accessKey] : null;

        /**
         * Check in the inherits roles
         */
        if ($haveAccess == null) {

            $roleInherits = $this->_roleInherits;
            if (isset($roleInherits[$roleName])) {
                $inheritedRoles = $roleInherits[$roleName];
                if (is_array($inheritedRoles)) {
                    foreach ($inheritedRoles as $inheritedRole) {
                        $accessKey = $inheritedRole . "!" . $resourceName . "!" . $access;

                        /**
                         * Check if there is a direct combination in one of the inherited roles
                         */
                        if (isset($accessList[$accessKey])) {
                            $haveAccess = $accessList[$accessKey];
                        }
                        $funcAccess = isset($funcList[$accessKey]) ? $funcList[$accessKey] : null;
                    }
                }
            }
        }

        /**
         * If access wasn't found yet, try role-resource-*
         */
        if ($haveAccess == null) {

            $accessKey = $roleName . "!" . $resourceName . "!*";

            /**
             * In the direct role
             */
            if (isset($accessList[$accessKey])) {
                $haveAccess = $accessList[$accessKey];
                $funcAccess = isset($funcList[$accessKey]) ? $funcList[$accessKey] : null;
            } else {
                if (is_array($inheritedRoles)) {
                    foreach ($inheritedRoles as $inheritedRole) {
                        $accessKey = $inheritedRole . "!" . $resourceName . "!*";

                        /**
                         * In the inherited roles
                         */
                        $funcAccess = isset($funcList[$accessKey]) ? $funcList[$accessKey] : null;
                        if (isset($accessList[$accessKey])) {
                            $haveAccess = $accessList[$accessKey];
                            break;
                        }
                    }
                }
            }
        }

        /**
         * If access wasn't found yet, try role-*-*
         */
        if ($haveAccess == null) {

            $accessKey = $roleName . "!*!*";

            /**
             * Try in the direct role
             */
            if (isset($accessList[$accessKey])) {
                $haveAccess = $accessList[$accessKey];
                $funcAccess = isset($funcList[$accessKey]) ? $funcList[$accessKey] : null;
            } else {
                if (is_array($inheritedRoles)) {
                    foreach ($inheritedRoles as $inheritedRole) {
                        $accessKey = $inheritedRole . "!*!*";

                        /**
                         * In the inherited roles
                         */
                        $funcAccess = isset($funcList[$accessKey]) ? $funcList[$accessKey] : null;
                        if (isset($accessList[$accessKey])) {
                            $haveAccess = $accessList[$accessKey];
                            break;
                        }
                    }
                }
            }
        }

        $this->_accessGranted = $haveAccess;
        if (is_object($eventsManager)) {
            $eventsManager->fire("acl:afterCheckAccess", $this);
        }

        if ($haveAccess == null) {
            return $this->_defaultAccess == Acl::ALLOW;
        }

        /**
         * If we have funcAccess then do all the checks for it
         */
        if ($funcAccess !== null) {
            $reflectionFunction   = new \ReflectionFunction($funcAccess);
            $reflectionParameters = $reflectionFunction->getParameters();
            $parameterNumber      = count($reflectionParameters);

            // No parameters, just return haveAccess and call function without array
            if ($parameterNumber === 0) {
                return $haveAccess == Acl::ALLOW && call_user_func($funcAccess);
            }

            $parametersForFunction      = [];
            $numberOfRequiredParameters = $reflectionFunction->getNumberOfRequiredParameters();
            $userParametersSizeShouldBe = $parameterNumber;

            foreach ($reflectionParameters as $reflectionParameter) {
                $reflectionClass  = $reflectionParameter->getClass();
                $parameterToCheck = $reflectionParameter->getName();

                if ($reflectionClass !== null) {
                    // roleObject is this class
                    if ($roleObject !== null && $reflectionClass->isInstance($roleObject)) {
                        $parametersForFunction[] = $roleObject;
                        $userParametersSizeShouldBe--;

                        continue;
                    }

                    // resourceObject is this class
                    if ($resourceObject !== null && $reflectionClass->isInstance($resourceObject)) {
                        $parametersForFunction[] = $resourceObject;
                        $userParametersSizeShouldBe--;

                        continue;
                    }

                    // This is some user defined class, check if his parameter is instance of it
                    if (isset($parameters[$parameterToCheck]) && is_object($parameters[$parameterToCheck]) && !$reflectionClass->isInstance($parameters[$parameterToCheck])) {
                        throw new Exception(
                        "Your passed parameter doesn't have the same class as the parameter "
                        . "in defined function when check " . $roleName . " can " . $access . " "
                        . $resourceName . ". Class passed: " . get_class($parameters[$parameterToCheck])
                        . " , Class in defined function: " . $reflectionClass->getName() . "."
                        );
                    }
                }

                if (isset($parameters[$parameterToCheck])) {
                    // We can't check type of ReflectionParameter in PHP 5.x so we just add it as it is
                    $parametersForFunction[] = $parameters[$parameterToCheck];
                }
            }

            if (count($parameters) > $userParametersSizeShouldBe) {
                trigger_error(
                    "Number of parameters in array is higher than the number of "
                    . "parameters in defined function when check " . $roleName
                    . " can " . $access . " " . $resourceName
                    . ". Remember that more parameters than defined in function will be ignored.", E_USER_WARNING
                );
            }

            // We dont have any parameters so check default action
            if (count($parametersForFunction) == 0) {
                if ($numberOfRequiredParameters > 0) {
                    trigger_error(
                        "You didn't provide any parameters when check "
                        . $roleName . " can " . $access . " " . $resourceName . ". We will use default action when no arguments."
                    );

                    return $haveAccess == Acl::ALLOW && $this->_noArgumentsDefaultAction == Acl::ALLOW;
                }

                // Number of required parameters == 0 so call funcAccess without any arguments
                return $haveAccess == Acl::ALLOW && call_user_func($funcAccess);
            }

            // Check necessary parameters
            if (count($parametersForFunction) >= $numberOfRequiredParameters) {
                return $haveAccess == Acl::ALLOW && call_user_func_array($funcAccess, $parametersForFunction);
            }

            // We don't have enough parameters
            throw new Exception(
            "You didn't provide all necessary parameters for defined function when check "
            . $roleName . " can " . $access . " " . $resourceName
            );
        }

        return $haveAccess == Acl::ALLOW;
    }

    /**
     * Return an array with every role registered in the list
     *
     * @return RoleInterface[]
     */
    public function getRoles()
    {
        return $this->_roles;
    }

    /**
     * Return an array with every resource registered in the list
     *
     * @return array
     */
    public function getResources()
    {
        return $this->_resources;
    }

    /**
     * Sets the default access level (Phalcon\Acl::ALLOW or Phalcon\Acl::DENY)
     * for no arguments provided in isAllowed action if there exists func for
     * accessKey
     * 
     * @param int $defaultAccess
     */
    public function setNoArgumentsDefaultAction($defaultAccess)
    {
        $this->_noArgumentsDefaultAction = $defaultAccess;
    }

    /**
     * Returns the default ACL access level for no arguments provided in
     * isAllowed action if there exists func for accessKey
     * 
     * @return int
     */
    public function getNoArgumentsDefaultAction()
    {
        return $this->_noArgumentsDefaultAction;
    }

}
