<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ManyManyList;

/**
 * Provides standard permission fields for inheritable permissions
 *
 * @property string $CanViewType
 * @property string $CanEditType
 * @method ManyManyList<Group> EditorGroups()
 * @method ManyManyList<Member> EditorMembers()
 * @method ManyManyList<Group> ViewerGroups()
 * @method ManyManyList<Member> ViewerMembers()
 *
 * @extends DataExtension<DataObject>
 */
class InheritedPermissionsExtension extends DataExtension
{
    private static array $db = [
        'CanViewType' => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, OnlyTheseMembers, Inherit', 'Inherit')",
        'CanEditType' => "Enum('LoggedInUsers, OnlyTheseUsers, OnlyTheseMembers, Inherit', 'Inherit')",
    ];

    private static array $many_many = [
        'ViewerGroups' => Group::class,
        'EditorGroups' => Group::class,
        'ViewerMembers' => Member::class,
        'EditorMembers' => Member::class,
    ];

    private static array $defaults = [
        'CanViewType' => InheritedPermissions::INHERIT,
        'CanEditType' => InheritedPermissions::INHERIT,
    ];

    private static array $cascade_duplicates = [
        'ViewerGroups',
        'EditorGroups',
        'ViewerMembers',
        'EditorMembers',
    ];
}
