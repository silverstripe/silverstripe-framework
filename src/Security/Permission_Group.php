<?php

namespace SilverStripe\Security;

/**
 * Permission_Group class
 *
 * This class is used to group permissions together for showing on an
 * interface.
 */
class Permission_Group
{

    /**
     * Name of the permission group (can be used as label in an interface)
     * @var string
     */
    protected $name;

    /**
     * Associative array of permissions in this permission group. The array
     * indices are the permission codes as used in
     * {@link Permission::check()}. The value is suitable for using in an
     * interface.
     * @var string
     */
    protected $permissions = [];


    /**
     * Constructor
     *
     * @param string $name Text that could be used as label used in an
     *                     interface
     * @param array $permissions Associative array of permissions in this
     *                           permission group. The array indices are the
     *                           permission codes as used in
     *                           {@link Permission::check()}. The value is
     *                           suitable for using in an interface.
     */
    public function __construct($name, $permissions)
    {
        $this->name = $name;
        $this->permissions = $permissions;
    }

    /**
     * Get the name of the permission group
     *
     * @return string Name (label) of the permission group
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Get permissions
     *
     * @return array Associative array of permissions in this permission
     *               group. The array indices are the permission codes as
     *               used in {@link Permission::check()}. The value is
     *               suitable for using in an interface.
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
}
