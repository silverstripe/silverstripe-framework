<?php

namespace SilverStripe\Security\Test\InheritedPermissionsTest;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\InheritedPermissionsExtension;
use SilverStripe\Security\Member;
use SilverStripe\Security\PermissionChecker;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;

/**
 * @method TestPermissionNode Parent()
 * @mixin Versioned
 * @mixin InheritedPermissionsExtension
 */
class TestPermissionNode extends DataObject implements TestOnly
{
    private static $db = [
        "Title" => "Varchar(255)",
    ];

    private static $has_one = [
        "Parent" => self::class,
    ];

    private static $table_name = 'InheritedPermissionsTest_TestPermissionNode';

    private static $extensions = [
        Versioned::class,
        InheritedPermissionsExtension::class,
    ];

    /**
     * @return InheritedPermissions
     */
    public static function getInheritedPermissions()
    {
        /** @var InheritedPermissions $permissions */
        return Injector::inst()->get(PermissionChecker::class . '.testpermissions');
    }

    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        return static::getInheritedPermissions()->canEdit($this->ID, $member);
    }

    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        return static::getInheritedPermissions()->canView($this->ID, $member);
    }

    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        return static::getInheritedPermissions()->canDelete($this->ID, $member);
    }
}
