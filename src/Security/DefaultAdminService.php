<?php

namespace SilverStripe\Security;

use BadMethodCallException;
use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Provides access to the default admin
 */
class DefaultAdminService
{
    use Extensible;
    use Configurable;
    use Injectable;

    /**
     * Can be set to explicitly true or false, or left null.
     * If null, hasDefaultAdmin() will be inferred from environment.
     *
     * @var bool|null
     */
    protected static $has_default_admin = null;

    /**
     * @var string
     */
    protected static $default_username = null;

    /**
     * @var string
     */
    protected static $default_password = null;

    public function __construct()
    {
    }

    /**
     * Set the default admin credentials
     *
     * @param string $username
     * @param string $password
     */
    public static function setDefaultAdmin($username, $password)
    {
        // don't overwrite if already set
        if (static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "Default admin already exists. Use clearDefaultAdmin() first."
            );
        }

        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException("Default admin username / password cannot be empty");
        }

        static::$default_username = $username;
        static::$default_password = $password;
        static::$has_default_admin = true;
    }

    /**
     * @return string The default admin username
     * @throws BadMethodCallException Throws exception if there is no default admin
     */
    public static function getDefaultAdminUsername()
    {
        if (!static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "No default admin configured. Please call hasDefaultAdmin() before getting default admin username"
            );
        }
        return static::$default_username ?: Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME');
    }

    /**
     * @return string The default admin password
     * @throws BadMethodCallException Throws exception if there is no default admin
     */
    public static function getDefaultAdminPassword()
    {
        if (!static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "No default admin configured. Please call hasDefaultAdmin() before getting default admin password"
            );
        }
        return static::$default_password ?: Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD');
    }

    /**
     * Check if there is a default admin
     *
     * @return bool
     */
    public static function hasDefaultAdmin()
    {
        // Check environment if not explicitly set
        if (!isset(static::$has_default_admin)) {
            return !empty(Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME'))
                && !empty(Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD'));
        }
        return static::$has_default_admin;
    }

    /**
     * Flush the default admin credentials.
     */
    public static function clearDefaultAdmin()
    {
        static::$has_default_admin = false;
        static::$default_username = null;
        static::$default_password = null;
    }

    /**
     * @return Member|null
     */
    public function findOrCreateDefaultAdmin()
    {
        $this->extend('beforeFindOrCreateDefaultAdmin');

        // Check if we have default admins
        if (!static::hasDefaultAdmin()) {
            return null;
        }

        // Create admin with default admin username
        $admin = $this->findOrCreateAdmin(
            static::getDefaultAdminUsername(),
            _t(__CLASS__ . '.DefaultAdminFirstname', 'Default Admin')
        );

        $this->extend('afterFindOrCreateDefaultAdmin', $admin);

        return $admin;
    }

    /**
     * Find or create a Member with admin permissions
     *
     * @param string $email
     * @param string $name
     * @return Member
     */
    public function findOrCreateAdmin($email, $name = null)
    {
        $this->extend('beforeFindOrCreateAdmin', $email, $name);

        // Find member
        $admin = Member::get()
            ->filter('Email', $email)
            ->first();

        // Find or create admin group
        $adminGroup = $this->findOrCreateAdminGroup();

        // If no admin is found, create one
        if ($admin) {
            $inGroup = $admin->inGroup($adminGroup);
        } else {
            // Note: This user won't be able to login until a password is set
            // Set 'Email' to identify this as the default admin
            $inGroup = false;
            $admin = Member::create();
            $admin->FirstName = $name ?: $email;
            $admin->Email = $email;
            $admin->PasswordEncryption = Security::config()->get('password_encryption_algorithm');
            $admin->write();
        }

        // Ensure this user is in an admin group
        if (!$inGroup) {
            // Add member to group instead of adding group to member
            // This bypasses the privilege escallation code in Member_GroupSet
            $adminGroup
                ->DirectMembers()
                ->add($admin);
        }

        $this->extend('afterFindOrCreateAdmin', $admin);

        return $admin;
    }

    /**
     * Ensure a Group exists with admin permission
     *
     * @return Group
     */
    protected function findOrCreateAdminGroup()
    {
        // Check pre-existing group
        $adminGroup = Permission::get_groups_by_permission('ADMIN')->first();
        if ($adminGroup) {
            return $adminGroup;
        }

        // Check if default records create the group
        Group::singleton()->requireDefaultRecords();
        $adminGroup = Permission::get_groups_by_permission('ADMIN')->first();
        if ($adminGroup) {
            return $adminGroup;
        }

        // Create new admin group directly
        $adminGroup = Group::create();
        $adminGroup->Code = 'administrators';
        $adminGroup->Title = _t('SilverStripe\\Security\\Group.DefaultGroupTitleAdministrators', 'Administrators');
        $adminGroup->Sort = 0;
        $adminGroup->write();
        Permission::grant($adminGroup->ID, 'ADMIN');
        return $adminGroup;
    }

    /**
     * Check if the user is a default admin.
     * Returns false if there is no default admin.
     *
     * @param string $username
     * @return bool
     */
    public static function isDefaultAdmin($username)
    {
        return static::hasDefaultAdmin()
            && $username
            && $username === static::getDefaultAdminUsername();
    }

    /**
     * Check if the user credentials match the default admin.
     * Returns false if there is no default admin.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function isDefaultAdminCredentials($username, $password)
    {
        return static::isDefaultAdmin($username)
            && $password
            && $password === static::getDefaultAdminPassword();
    }
}
