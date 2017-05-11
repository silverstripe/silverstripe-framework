<?php

namespace SilverStripe\Security;

/**
 * Allows objects to enforce permissions for the "root" level,
 * where permissions can not be tied to a particular database record.
 * Objects below the "root" level should use their own can*()
 * implementations instead of this interface.
 */
interface DefaultPermissionChecker
{
    /**
     * Can root be edited?
     *
     * @param Member $member
     * @return bool
     */
    public function canEdit(Member $member = null);

    /**
     * Can root be viewed?
     *
     * @param Member $member
     * @return bool
     */
    public function canView(Member $member = null);

    /**
     * Can root be deleted?
     *
     * @param Member $member
     * @return bool
     */
    public function canDelete(Member $member = null);

    /**
     * Can root objects be created?
     *
     * @param Member $member
     * @return bool
     */
    public function canCreate(Member $member = null);
}
