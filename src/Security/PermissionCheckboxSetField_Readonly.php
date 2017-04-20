<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataObjectInterface;

/**
 * Readonly version of a {@link PermissionCheckboxSetField} -
 * uses the same structure, but has all checkboxes disabled.
 */
class PermissionCheckboxSetField_Readonly extends PermissionCheckboxSetField
{

    protected $readonly = true;

    public function saveInto(DataObjectInterface $record)
    {
        return false;
    }
}
