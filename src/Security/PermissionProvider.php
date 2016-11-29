<?php

namespace SilverStripe\Security;

/**
 * Used to let classes provide new permission codes.
 * Every implementor of PermissionProvider is accessed and providePermissions() called to get the full list of
 * permission codes.
 */
interface PermissionProvider
{

    /**
     * Return a map of permission codes to add to the dropdown shown in the Security section of the CMS.
     * array(
     *   'VIEW_SITE' => 'View the site',
     * );
     */
    public function providePermissions();
}
