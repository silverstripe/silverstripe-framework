<?php

namespace SilverStripe\Security;

/**
 * Calculates edit / view / delete permissions for one or more objects
 */
interface PermissionChecker
{

    /**
     * Get the 'can edit' information for a number of SiteTree pages.
     *
     * @param array $ids An array of IDs of the objects to look up
     * @param Member $member Member object
     * @param bool $useCached Return values from the permission cache if they exist
     * @return array A map where the IDs are keys and the values are
     * booleans stating whether the given object can be edited
     */
    public function canEditMultiple($ids, Member $member = null, $useCached = true);

    /**
     * Get the canView information for a number of objects
     *
     * @param array $ids
     * @param Member $member
     * @param bool $useCached
     * @return mixed
     */
    public function canViewMultiple($ids, Member $member = null, $useCached = true);

    /**
     * Get the 'can edit' information for a number of SiteTree pages.
     *
     * @param array $ids An array of IDs of the objects pages to look up
     * @param Member $member Member object
     * @param bool $useCached Return values from the permission cache if they exist
     * @return array
     */
    public function canDeleteMultiple($ids, Member $member = null, $useCached = true);

    /**
     * Check delete permission for a single record ID
     *
     * @param int $id
     * @param Member $member
     * @return bool
     */
    public function canDelete($id, Member $member = null);

    /**
     * Check edit permission for a single record ID
     *
     * @param int $id
     * @param Member $member
     * @return bool
     */
    public function canEdit($id, Member $member = null);

    /**
     * Check view permission for a single record ID
     *
     * @param int $id
     * @param Member $member
     * @return bool
     */
    public function canView($id, Member $member = null);
}
