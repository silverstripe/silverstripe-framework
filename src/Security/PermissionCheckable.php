<?php

namespace SilverStripe\Security;

/**
 * Model with permissions that can be checked using PermissionChecker
 */
interface PermissionCheckable
{
    /**
     * Get the permission checker for this model
     */
    public function getPermissionChecker(): PermissionChecker;
}
