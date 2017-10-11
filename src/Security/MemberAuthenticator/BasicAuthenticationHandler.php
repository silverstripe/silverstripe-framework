<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\AuthenticationHandler;
use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\Member;

/**
 * Authenticates requests using {@link \SilverStripe\Security\BasicAuth}. Can be applied to routes as middleware
 * via the {@link \SilverStripe\Security\AuthenticationMiddleware} middleware.
 */
class BasicAuthenticationHandler implements AuthenticationHandler
{
    /**
     * Try to authenticate a member with basic authentication
     *
     * {@inheritDoc}
     */
    public function authenticateRequest(HTTPRequest $request)
    {
        try {
            return BasicAuth::requireLogin($request, 'Restricted');
        } catch (HTTPResponse_Exception $ex) {
            throw new ValidationException($ex->getMessage());
        }
    }

    public function logIn(Member $member, $persistent = false, HTTPRequest $request = null)
    {
        // no op
    }

    public function logOut(HTTPRequest $request = null)
    {
        // no op
    }
}
