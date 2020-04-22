<?php

namespace SilverStripe\Security;

use BadMethodCallException;
use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Member;

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
    protected static $default_uniqueIdentifier = null;
    
    /**
     * @var string
     */
    protected static $default_email = null;

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
     * @param string $email
     * @param string $password
     * @param string $uniqueIdentifier
     */
    public static function setDefaultAdmin($email, $password, $uniqueIdentifier = null)
    {
        // don't overwrite if already set
        if (static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "Default admin already exists. Use clearDefaultAdmin() first."
            );
        }
        
        $uniqueIdentifierFieldName = Member::config()->unique_identifier_field;

        if (empty($email) || empty($password || (empty($uniqueIdentifier) && $uniqueIdentifierFieldName != 'Email'))) {
            throw new InvalidArgumentException("Default admin ". ($uniqueIdentifierFieldName != 'Email' ? strtolower($uniqueIdentifierFieldName)." / " : "") ."email / password cannot be empty");
        }

        static::$default_uniqueIdentifier = $uniqueIdentifier;
        static::$default_email = $email;
        static::$default_password = $password;
        static::$has_default_admin = true;
    }

    /**
     * @return string The default admin uniqueIdentifier with fallback to the default admin email
     * @throws BadMethodCallException Throws exception if there is no default admin
     */
    public static function getDefaultAdminUniqueIdentifier()
    {
        $uniqueIdentifierFieldName = Member::config()->unique_identifier_field;
        
        if($uniqueIdentifierFieldName == 'Email')
            return static::getDefaultAdminEmail();

        if (!static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "No default admin configured. Please call hasDefaultAdmin() before getting default admin " . strtolower($uniqueIdentifierFieldName)
            );
        }
        return static::$default_uniqueIdentifier ?: Environment::getEnv('SS_DEFAULT_ADMIN_' . strtoupper($uniqueIdentifierFieldName));
    }
    
    /**
     * @return string The default admin email
     * @throws BadMethodCallException Throws exception if there is no default admin
     */
    public static function getDefaultAdminEmail()
    {
        if (!static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "No default admin configured. Please call hasDefaultAdmin() before getting default admin username"
            );
        }
        return static::$default_email ?: Environment::getEnv('SS_DEFAULT_ADMIN_EMAIL');
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
            $uniqueIdentifierFieldName = Member::config()->unique_identifier_field;
            return ($uniqueIdentifierFieldName == 'Email' || ($uniqueIdentifierFieldName != 'Email' && !empty(Environment::getEnv('SS_DEFAULT_ADMIN_' . strtoupper($uniqueIdentifierFieldName)))))
                && !empty(Environment::getEnv('SS_DEFAULT_ADMIN_EMAIL'))
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
        static::$default_uniqueIdentifier = null;
        static::$default_email = null;
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

        // Create admin with default admin uniqueIdentifier
        $admin = $this->findOrCreateAdmin(
            static::getDefaultAdminUniqueIdentifier(),
            _t(__CLASS__ . '.DefaultAdminFirstname', 'Default Admin')
        );

        $this->extend('afterFindOrCreateDefaultAdmin', $admin);

        return $admin;
    }

    /**
     * Find or create a Member with admin permissions
     *
     * @skipUpgrade
     * @param string $uniqueIdentifier
     * @param string $name
     * @return Member
     */
    public function findOrCreateAdmin($uniqueIdentifier, $name = null)
    {
        $this->extend('beforeFindOrCreateAdmin', $uniqueIdentifier, $name);

        // Find member
        /** @var Member $admin */
        $uniqueIdentifierFieldName = Member::config()->unique_identifier_field;
        $admin = Member::get()
            ->filter($uniqueIdentifierFieldName, $uniqueIdentifier)
            ->first();

        // Find or create admin group
        $adminGroup = $this->findOrCreateAdminGroup();

        // If no admin is found, create one
        if ($admin) {
            $inGroup = $admin->inGroup($adminGroup);
        } else {
            // Note: This user won't be able to login until a password is set
            // Set 'uniqueIdentifierFieldName' to identify this as the default admin
            $inGroup = false;
            $admin = Member::create();
            $admin->FirstName = $name ?: static::getDefaultAdminUniqueIdentifier();
            
            if($uniqueIdentifierFieldName != 'Email')
                $admin->$uniqueIdentifierFieldName = $uniqueIdentifier;
            
            $admin->Email = static::getDefaultAdminEmail();
            $admin->PasswordEncryption = 'none';
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
     * @param string $uniqueIdentifier
     * @return bool
     */
    public static function isDefaultAdmin($uniqueIdentifier)
    {
        return static::hasDefaultAdmin()
            && $uniqueIdentifier
            && $uniqueIdentifier === static::getDefaultAdminUniqueIdentifier();
    }

    /**
     * Check if the user credentials match the default admin.
     * Returns false if there is no default admin.
     *
     * @param string $uniqueIdentifier
     * @param string $password
     * @return bool
     */
    public static function isDefaultAdminCredentials($uniqueIdentifier, $password)
    {
        return static::isDefaultAdmin($uniqueIdentifier)
            && $password
            && $password === static::getDefaultAdminPassword();
    }
}
