<?php

namespace SilverStripe\Security\Test\InheritedPermissionsTest;

use SilverStripe\Security\Member;
use SilverStripe\Security\DefaultPermissionChecker;

class TestDefaultPermissionChecker implements DefaultPermissionChecker
{
    protected $canEdit = true;

    protected $canView = true;

    /**
     * Can root be edited?
     *
     * @param Member $member
     * @return bool
     */
    public function canEdit(Member $member = null)
    {
        return $this->canEdit;
    }

    /**
     * Can root be viewed?
     *
     * @param Member $member
     * @return bool
     */
    public function canView(Member $member = null)
    {
        return $this->canView;
    }

    /**
     * Can root be deleted?
     *
     * @param Member $member
     * @return bool
     */
    public function canDelete(Member $member = null)
    {
        return $this->canEdit;
    }

    /**
     * Can root objects be created?
     *
     * @param Member $member
     * @return bool
     */
    public function canCreate(Member $member = null)
    {
        return $this->canEdit;
    }

    public function setCanEdit($canEdit)
    {
        $this->canEdit = $canEdit;
        return $this;
    }

    public function setCanView($canView)
    {
        $this->canView = $canView;
        return $this;
    }
}
