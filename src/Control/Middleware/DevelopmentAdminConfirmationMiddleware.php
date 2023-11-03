<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Security\Permission;

/**
 * Extends the PermissionAwareConfirmationMiddleware with checks for user permissions
 *
 * Respects users who don't have enough access and does not
 * ask them for confirmation
 *
 * By default it enforces authentication by redirecting users to a login page.
 *
 * How it works:
 *  - if user can bypass the middleware, then pass request further
 *  - if there are no confirmation items, then pass request further
 *  - if user is not authenticated and enforceAuthentication is false, then pass request further
 *  - if user does not have at least one of the affected permissions, then pass request further
 *  - otherwise, pass handling to the parent (ConfirmationMiddleware)
 */
class DevelopmentAdminConfirmationMiddleware extends PermissionAwareConfirmationMiddleware
{

    /**
     * Check whether the user has permissions to perform the target operation
     * Otherwise we may want to skip the confirmation dialog.
     *
     * WARNING! The user has to be authenticated beforehand
     *
     * @param HTTPRequest $request
     *
     * @return bool
     */
    public function hasAccess(HTTPRequest $request)
    {
        $action = $request->remaining();
        if (empty($action)) {
            return false;
        }

        $registeredRoutes = DevelopmentAdmin::config()->get('registered_controllers');
        while (!isset($registeredRoutes[$action]) && strpos($action, '/') !== false) {
            // Check for the parent route if a specific route isn't found
            $action = substr($action, 0, strrpos($action, '/'));
        }

        if (isset($registeredRoutes[$action]['controller'])) {
            $initPermissions = Config::forClass($registeredRoutes[$action]['controller'])->get('init_permissions');
            foreach ($initPermissions as $permission) {
                if (Permission::check($permission)) {
                    return true;
                }
            }
        }

        return false;
    }
}
