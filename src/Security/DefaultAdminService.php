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
     * $default_username is a generic variable to mask 'unique_identifier_field': can be a custom unique_identifier_field,
     * a username, an email or other things
     * @var string
     */
    protected static $default_username = null; 

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
     * @param string $username 
     * @param string $password
     * @param string $email
     */
    public static function setDefaultAdmin($username, $password, $email = null) 
    {
        // don't overwrite if already set
        if (static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "Default admin already exists. Use clearDefaultAdmin() first."
            );
        }

        $adminEmptyParams = [];
        $uniqueIdentifierFieldName = Member::config()->get('unique_identifier_field'); 

        if($uniqueIdentifierFieldName =! 'Email') {
            // $username treated like $uniqueIdentifierFieldName
            if(empty($username))
                $adminEmptyParams[] = strtolower($uniqueIdentifierFieldName);

            if(empty($email))
                $adminEmptyParams[] = 'email';    

        } else {
            if(empty($username)) {
                if(empty(Environment::getEnv('SS_DEFAULT_ADMIN_EMAIL')))
                    $adminEmptyParams[] = 'username';
                else
                    $adminEmptyParams[] = 'email';
            }   
        }

        if(empty($password))
            $adminEmptyParams[] = 'password';

        if(count($adminEmptyParams) > 0)
            throw new InvalidArgumentException("Default admin ". implode(" and ", $adminEmptyParams) ." cannot be empty");

        static::$default_username = $username;
        static::$default_password = $password;

        if(!empty($email))
            static::$default_email = $email;
            
        static::$has_default_admin = true;
    }

    /**
     * @return string The default admin identifier
     * @throws BadMethodCallException Throws exception if there is no default admin
     */
    public static function getDefaultAdminUsername() 
    {
        $uniqueIdentifierFieldName = Member::config()->get('unique_identifier_field');
        $defaultAdminEmail = static::$default_email ?: Environment::getEnv('SS_DEFAULT_ADMIN_EMAIL');

        if (!static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "No default admin configured. Please call hasDefaultAdmin() before getting default admin " . ($uniqueIdentifierFieldName != 'email' ? strtolower($uniqueIdentifierFieldName) : (!empty($defaultAdminEmail) ? "email" : "username"))
            );
        }

        if($uniqueIdentifierFieldName != 'Email')
            $identifierVariable = 'SS_DEFAULT_ADMIN_' . strtoupper($uniqueIdentifierFieldName);
        else {
            if(empty($defaultAdminEmail))
                $identifierVariable = 'SS_DEFAULT_ADMIN_USERNAME';
            else
                $identifierVariable = 'SS_DEFAULT_ADMIN_EMAIL';
        }

        return static::$default_username ?: Environment::getEnv($identifierVariable);
    }

    /**
     * @return string The default admin email or NULL
     */
    public static function getDefaultAdminEmail() 
    {
        if (!static::hasDefaultAdmin()) {
            throw new BadMethodCallException(
                "No default admin configured. Please call hasDefaultAdmin() before getting default admin email"
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
            $uniqueIdentifierFieldName = Member::config()->get('unique_identifier_field');
            if($uniqueIdentifierFieldName != 'Email') {
                return !empty(Environment::getEnv('SS_DEFAULT_ADMIN_' . strtoupper($uniqueIdentifierFieldName)))
                    && !empty(Environment::getEnv('SS_DEFAULT_ADMIN_EMAIL'))
                    && !empty(Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD'));
            } else {
                if(!empty(Environment::getEnv('SS_DEFAULT_ADMIN_EMAIL')))
                    $default_admin_identifier = Environment::getEnv('SS_DEFAULT_ADMIN_EMAIL');
                else
                    $default_admin_identifier = Environment::getEnv('SS_DEFAULT_ADMIN_USERNAME');
                return !empty($default_admin_identifier)
                    && !empty(Environment::getEnv('SS_DEFAULT_ADMIN_PASSWORD'));
            }
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
            $uniqueIdentifierFieldName = Member::config()->get('unique_identifier_field');
            throw new BadMethodCallException(
                "No default admin configured. Please check the environment configuration. Did you set the SS_DEFAULT_ADMIN_". ($uniqueIdentifierFieldName != 'Email' ? strtoupper($uniqueIdentifierFieldName) . "? Did you set the SS_DEFAULT_ADMIN_EMAIL?" : "USERNAME?")." Did you set the SS_DEFAULT_ADMIN_PASSWORD?"
            );
            return null;
        }

        // Create admin with default admin uniqueIdentifier 
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
     * @skipUpgrade
     * @param string $username 
     * @param string $name
     * @return Member
     */
    public function findOrCreateAdmin($username, $name = null) 
    {
        $this->extend('beforeFindOrCreateAdmin', $username, $name); 

        // Find member
        /** @var Member $admin */

        $uniqueIdentifierFieldName = Member::config()->get('unique_identifier_field'); 

        $admin = Member::get()
            ->filter($uniqueIdentifierFieldName, $username) 
            ->first();

        // Find or create admin group
        $adminGroup = $this->findOrCreateAdminGroup();

        // If no admin is found, create one
        if ($admin) {
            $inGroup = $admin->inGroup($adminGroup);
        } else {
            // Note: This user won't be able to login until a password is set
            // Set '$uniqueIdentifierFieldName' to identify this as the default admin 
            $inGroup = false;
            $admin = Member::create();
            $admin->FirstName = $name ?: $username; 
            
            //if($uniqueIdentifierFieldName != 'Email')
            $admin->$uniqueIdentifierFieldName = $username;

            if($uniqueIdentifierFieldName != 'Email' && !empty(static::getDefaultAdminEmail()))
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
     * @param string $uniqueIdentifier
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
