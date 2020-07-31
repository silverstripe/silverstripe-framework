<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataObject;

/**
 * A PermissionRoleCode represents a single permission code assigned to a {@link PermissionRole}.
 *
 * @property string Code
 * @property int RoleID
 * @method PermissionRole Role()
 */
class PermissionRoleCode extends DataObject
{
    private static $db = [
        'Code' => 'Varchar',
    ];

    private static $has_one = [
        'Role' => PermissionRole::class,
    ];

    private static $table_name = 'PermissionRoleCode';

    public function validate()
    {
        $result = parent::validate();

        // Check that new code doesn't increase privileges, unless an admin is editing.
        $privilegedCodes = Permission::config()->privileged_permissions;
        if ($this->Code
            && in_array($this->Code, $privilegedCodes, true)
            && !Permission::check('ADMIN')
        ) {
            $result->addError(
                _t(
                    self::class . '.PermsError',
                    'Can\'t assign code "{code}" with privileged permissions (requires ADMIN access)',
                    ['code' => $this->Code]
                )
            );
        }

        return $result;
    }

    public function canCreate($member = null, $context = [])
    {
        return Permission::check('APPLY_ROLES', 'any', $member);
    }

    public function canEdit($member = null)
    {
        return Permission::check('APPLY_ROLES', 'any', $member);
    }

    public function canDelete($member = null)
    {
        return Permission::check('APPLY_ROLES', 'any', $member);
    }
}
