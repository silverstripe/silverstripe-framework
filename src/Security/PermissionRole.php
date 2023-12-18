<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;

/**
 * A PermissionRole represents a collection of permission codes that can be applied to groups.
 *
 * Because permission codes are very granular, this lets website administrators create more
 * business-oriented units of access control - Roles - and assign those to groups.
 *
 * If the <b>OnlyAdminCanApply</b> property is set to TRUE, the role can only be assigned
 * to new groups by a user with ADMIN privileges. This is a simple way to prevent users
 * with access to {@link SecurityAdmin} (but no ADMIN privileges) to get themselves ADMIN access
 * (which might be implied by certain roles).
 *
 * @property string Title
 * @property string OnlyAdminCanApply
 *
 * @method HasManyList<PermissionRoleCode> Codes()
 * @method ManyManyList<Group> Groups()
 */
class PermissionRole extends DataObject
{
    private static $db = [
        "Title" => "Varchar",
        "OnlyAdminCanApply" => "Boolean"
    ];

    private static $has_many = [
        "Codes" => PermissionRoleCode::class,
    ];

    private static $belongs_many_many = [
        "Groups" => Group::class,
    ];

    private static $table_name = "PermissionRole";

    private static $default_sort = '"Title"';

    private static $singular_name = 'Role';

    private static $plural_name = 'Roles';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeFieldFromTab('Root', 'Codes');
        $fields->removeFieldFromTab('Root', 'Groups');

        $fields->addFieldToTab(
            'Root.Main',
            $permissionField = new PermissionCheckboxSetField(
                'Codes',
                Permission::singleton()->i18n_plural_name(),
                'SilverStripe\\Security\\PermissionRoleCode',
                'RoleID'
            )
        );
        $permissionField->setHiddenPermissions(
            Permission::config()->hidden_permissions
        );

        return $fields;
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();

        // Delete associated permission codes
        $codes = $this->Codes();
        foreach ($codes as $code) {
            $code->delete();
        }
    }

    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Title'] = _t('SilverStripe\\Security\\PermissionRole.Title', 'Title');
        $labels['OnlyAdminCanApply'] = _t(
            'SilverStripe\\Security\\PermissionRole.OnlyAdminCanApply',
            'Only admin can apply',
            'Checkbox to limit which user can apply this role'
        );

        return $labels;
    }

    public function canView($member = null)
    {
        return Permission::check('APPLY_ROLES', 'any', $member);
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
