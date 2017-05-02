<?php

namespace SilverStripe\Security;

/**
 * For root objects that have permission type "Inherit" determine the result of certain permission checks
 */
interface RootPermissions
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
