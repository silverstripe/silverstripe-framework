<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Extends the ConfirmationMiddleware with checks for user permissions
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
class PermissionAwareConfirmationMiddleware extends ConfirmationMiddleware
{
    /**
     * List of permissions affected by the middleware
     *
     * @see setAffectedPermissions method for more details
     *
     * @var string[]
     */
    private $affectedPermissions = [];

    /**
     * Wthether the middleware should redirect to a login form
     * if the user is not authenticated
     *
     * @var bool
     */
    private $enforceAuthentication = true;

    /**
     * Returns the list of permissions that are affected
     *
     * @return string[]
     */
    public function getAffectedPermissions()
    {
        return $this->affectedPermissions;
    }

    /**
     * Set the list of affected permissions
     *
     * If the user doesn't have at least one of these, we assume they
     * don't have access to the protected action, so we don't ask
     * for a confirmation
     *
     * @param string[] $permissions list of affected permissions
     *
     * @return $this
     */
    public function setAffectedPermissions($permissions)
    {
        $this->affectedPermissions = $permissions;
        return $this;
    }

    /**
     * Returns flag whether we want to enforce authentication or not
     *
     * @return bool
     */
    public function getEnforceAuthentication()
    {
        return $this->enforceAuthentication;
    }

    /**
     * Set whether we want to enforce authentication
     *
     * We either enforce authentication (redirect to a login form)
     * or silently assume the user does not have permissions and
     * so we don't have to ask for a confirmation
     *
     * @param bool $enforce
     *
     * @return $this
     */
    public function setEnforceAuthentication($enforce)
    {
        $this->enforceAuthentication = $enforce;
        return $this;
    }

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
        foreach ($this->getAffectedPermissions() as $permission) {
            if (Permission::check($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns HTTPResponse with a redirect to a login page
     *
     * @param HTTPRequest $request
     *
     * @return HTTPResponse redirect to a login page
     */
    protected function getAuthenticationRedirect(HTTPRequest $request)
    {
        $backURL = $request->getURL(true);

        $loginPage = sprintf(
            '%s?BackURL=%s',
            Director::absoluteURL(Security::config()->get('login_url')),
            urlencode($backURL ?? '')
        );

        $result = new HTTPResponse();
        $result->redirect($loginPage);
        return $result;
    }

    protected function processItems(HTTPRequest $request, callable $delegate, $items)
    {
        if (!Security::getCurrentUser()) {
            if ($this->getEnforceAuthentication()) {
                return $this->getAuthenticationRedirect($request);
            } else {
                // assume the user does not have permissions anyway
                return $delegate($request);
            }
        }

        if (!$this->hasAccess($request)) {
            return $delegate($request);
        }

        return parent::processItems($request, $delegate, $items);
    }
}
