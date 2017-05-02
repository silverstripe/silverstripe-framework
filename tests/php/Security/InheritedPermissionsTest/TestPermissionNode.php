<?php

namespace SilverStripe\Security\Test\InheritedPermissionsTest;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Group;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;

/**
 * @method TestPermissionNode Parent()
 * @method ManyManyList ViewerGroups()
 * @method ManyManyList EditorGroups()
 * @mixin Versioned
 */
class TestPermissionNode extends DataObject implements TestOnly
{
    private static $db = [
        "Title" => "Varchar(255)",
        "CanViewType" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
        "CanEditType" => "Enum('LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
    ];

    private static $has_one = [
        "Parent" => self::class,
    ];

    private static $defaults = [
        "CanViewType" => "Inherit",
        "CanEditType" => "Inherit",
    ];

    private static $many_many = [
        "ViewerGroups" => Group::class,
        "EditorGroups" => Group::class,
    ];

    private static $table_name = 'InheritedPermissionsTest_TestPermissionNode';

    private static $extensions = [
        Versioned::class,
    ];

    /**
     * @return InheritedPermissions
     */
    public static function getInheritedPermissions()
    {
        /** @var InheritedPermissions $permissions */
        return Injector::inst()->get(InheritedPermissions::class.'.testpermissions');
    }

    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        return static::getInheritedPermissions()->canEdit($this->ID, $member);
    }

    public function canView($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        return static::getInheritedPermissions()->canView($this->ID, $member);
    }

    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        return static::getInheritedPermissions()->canDelete($this->ID, $member);
    }


}
