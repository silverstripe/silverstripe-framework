<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ManyManyList;

/**
 * Provides standard permission fields for inheritable permissions
 *
 * @property string $CanViewType
 * @property string $CanEditType
 * @method ManyManyList ViewerGroups()
 * @method ManyManyList EditorGroups()
 */
class InheritedPermissionsExtension extends DataExtension
{
    private static $db = [
        'CanViewType' => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
        'CanEditType' => "Enum('LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
    ];

    private static $many_many = [
        'ViewerGroups' => Group::class,
        'EditorGroups' => Group::class,
    ];

    private static $defaults = [
        'CanViewType' => InheritedPermissions::INHERIT,
        'CanEditType' => InheritedPermissions::INHERIT,
    ];
}
