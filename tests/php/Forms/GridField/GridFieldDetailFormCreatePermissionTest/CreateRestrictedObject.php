<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormCreatePermissionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

/**
 * A model whose create permission is stricter than its edit permission, which is the
 * scenario where the server-side create authorisation of GridFieldDetailForm matters.
 */
class CreateRestrictedObject extends DataObject implements TestOnly
{
    public const CREATE_PERMISSION = 'GRIDFIELD_CREATE_RESTRICTED_OBJECT';

    private static $table_name = 'GridFieldDetailFormCreatePermissionTest_CreateRestrictedObject';

    private static $db = [
        'Title' => 'Varchar',
    ];

    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return true;
    }

    public function canCreate($member = null, $context = [])
    {
        return Permission::check(CreateRestrictedObject::CREATE_PERMISSION, 'any', $member);
    }

    public function canDelete($member = null)
    {
        return true;
    }
}
