<?php

namespace Phalcon\Acl;

/**
 * Phalcon\Acl\AdapterInterface
 *
 * Interface for Phalcon\Acl adapters
 */
interface AdapterInterface
{

    /**
     * Sets the default access level (Phalcon\Acl::ALLOW or \Phalcon\Acl::DENY)
     *
     * @param int $defaultAccess
     */
    public function setDefaultAction($defaultAccess);

    /**
     * Returns the default ACL access level
     *
     * @return int
     */
    public function getDefaultAction();

    /**
     * Sets the default access level (Phalcon\Acl::ALLOW or Phalcon\Acl::DENY)
     * for no arguments provided in isAllowed action if there exists func for accessKey
     * 
     * @return int
     */
    public function setNoArgumentsDefaultAction($defaultAccess);

    /**
     * Returns the default ACL access level for no arguments provided in
     * isAllowed action if there exists func for accessKey
     * 
     * @return int
     */
    public function getNoArgumentsDefaultAction();

    /**
     * Adds a role to the ACL list. Second parameter lets to inherit access data from other existing role
     *
     * @param \Phalcon\Acl\RoleInterface|string $role
     * @param \Phalcon\Acl\RoleInterface|string|null $accessInherits
     * @return boolean
     */
    public function addRole($role, $accessInherits = null);

    /**
     * Do a role inherit from another existing role
     *
     * @param string $roleName
     * @param string|\Phalcon\Acl\RoleInterface $roleToInherit
     * @return boolean|null
     */
    public function addInherit($roleName, $roleToInherit);

    /**
     * Check whether role exist in the roles list
     *
     * @param string $roleName
     * @return boolean
     */
    public function isRole($roleName);

    /**
     * Check whether resource exist in the resources list
     *
     * @param string $resourceName
     * @return boolean
     */
    public function isResource($resourceName);

    /**
     * Adds a resource to the ACL list
     *
     * Access names can be a particular action, by example
     * search, update, delete, etc or a list of them
     *
     * @param \Phalcon\Acl\ResourceInterface|string $resource
     * @param array|string|null $accessList
     * @return boolean
     */
    public function addResource($resource, $accessList = null);

    /**
     * Adds access to resources
     *
     * @param string $resourceName
     * @param array|string|null $accessList
     * @return boolean
     */
    public function addResourceAccess($resourceName, $accessList);

    /**
     * Removes an access from a resource
     *
     * @param string $resourceName
     * @param array|string $accessList
     */
    public function dropResourceAccess($resourceName, $accessList);

    /**
     * Allow access to a role on a resource
     *
     * @param string $roleName
     * @param string $resourceName
     * @param string|array $access
     */
    public function allow($roleName, $resourceName, $access, $func = null);

    /**
     * Deny access to a role on a resource
     *
     * @param string $roleName
     * @param string $resourceName
     * @param string|array $access
     * @return boolean
     */
    public function deny($roleName, $resourceName, $access, $func = null);

    /**
     * Check whether a role is allowed to access an action from a resource
     *
     * @param string $role
     * @param string $resource
     * @param string $access
     * @return boolean
     */
    public function isAllowed($role, $resource, $access, array $parameters = null);

    /**
     * Returns the role which the list is checking if it's allowed to certain resource/access
     *
     * @return string|null
     */
    public function getActiveRole();

    /**
     * Returns the resource which the list is checking if some role can access it
     *
     * @return string|null
     */
    public function getActiveResource();

    /**
     * Returns the access which the list is checking if some role can access it
     *
     * @return string|null
     */
    public function getActiveAccess();

    /**
     * Return an array with every role registered in the list
     *
     * @return RoleInterface[]
     */
    public function getRoles();

    /**
     * Return an array with every resource registered in the list
     *
     * @return ResourceInterface[]
     */
    public function getResources();
}
