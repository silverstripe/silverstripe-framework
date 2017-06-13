<?php

namespace SilverStripe\Security\Service;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class DefaultAdminService
{

    use Extensible;
    use Configurable;

    /**
     * @var bool
     */
    protected static $has_default_admin = true;

    /**
     * @var string
     */
    protected static $default_username;

    /**
     * @var string
     */
    protected static $default_password;

    /**
     * Set the default admin credentials
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function setDefaultAdmin($username, $password)
    {
        // don't overwrite if already set
        if (static::$default_username || static::$default_password) {
            throw new \LogicException('Default admin is already set', 255);
        }

        static::$default_username = $username;
        static::$default_password = $password;
        static::$has_default_admin = !empty($username) && !empty($password);

        return true;
    }

    /**
     * @return string The default admin username
     */
    public static function getDefaultAdminUsername()
    {
        return static::$default_username;
    }

    /**
     * @return string The default admin password
     */
    public static function getDefaultAdminPassword()
    {
        return static::$default_password;
    }

    /**
     * Check if there is a default admin
     *
     * @return bool
     */
    public static function hasDefaultAdmin()
    {
        return static::$has_default_admin;
    }

    /**
     * Flush the default admin credentials
     */
    public static function clearDefaultAdmin()
    {
        self::$default_username = null;
        self::$default_password = null;
    }


    /**
     * @return null|Member
     */
    public function findOrCreateDefaultAdmin()
    {
        $this->extend('beforeFindAdministrator');

        // Check if we have default admins
        if (
            !static::$has_default_admin ||
            empty(static::$default_username) ||
            empty(static::$default_password)
        ) {
            return null;
        }

        // Find or create ADMIN group
        Group::singleton()->requireDefaultRecords();
        $adminGroup = Permission::get_groups_by_permission('ADMIN')->first();

        if (!$adminGroup) {
            Group::singleton()->requireDefaultRecords();
            $adminGroup = Permission::get_groups_by_permission('ADMIN')->first();
        }

        // Find member
        /** @skipUpgrade */
        $admin = Member::get()
            ->filter('Email', static::getDefaultAdminUsername())
            ->first();
        // If no admin is found, create one
        if (!$admin) {
            // 'Password' is not set to avoid creating
            // persistent logins in the database. See Security::setDefaultAdmin().
            // Set 'Email' to identify this as the default admin
            $admin = Member::create();
            $admin->FirstName = _t(__CLASS__ . '.DefaultAdminFirstname', 'Default Admin');
            $admin->Email = static::getDefaultAdminUsername();
            $admin->write();
        }

        // Ensure this user is in the admin group
        if (!$admin->inGroup($adminGroup)) {
            // Add member to group instead of adding group to member
            // This bypasses the privilege escallation code in Member_GroupSet
            $adminGroup
                ->DirectMembers()
                ->add($admin);
        }

        $this->extend('afterFindAnAdministrator');

        return $admin;
    }

    /**
     * @param string $username
     * @param string $password
     * @return ValidationResult
     */
    public function validateDefaultAdmin($username, $password)
    {
        $result = new ValidationResult();
        if (
            static::$default_username === $username
            && static::$default_password === $password
            && static::$has_default_admin
        ) {
            return $result;
        }

        $result->addError('No valid default admin found');

        return $result;
    }
}