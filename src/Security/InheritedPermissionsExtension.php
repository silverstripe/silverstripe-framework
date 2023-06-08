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
    private static array $db = [
        'CanViewType' => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
        'CanEditType' => "Enum('LoggedInUsers, OnlyTheseUsers, Inherit', 'Inherit')",
    ];

    private static array $many_many = [
        'ViewerGroups' => Group::class,
        'EditorGroups' => Group::class,
    ];

    private static array $defaults = [
        'CanViewType' => InheritedPermissions::INHERIT,
        'CanEditType' => InheritedPermissions::INHERIT,
    ];

    private static array $cascade_duplicates = [
        'ViewerGroups',
        'EditorGroups',
    ];
}
