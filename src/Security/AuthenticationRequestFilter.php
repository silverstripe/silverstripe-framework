<?php

namespace SilverStripe\Security;

use SilverStripe\Control\RequestFilter;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\ORM\DataModel;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

class AuthenticationRequestFilter implements RequestFilter, IdentityStore
{

    use Configurable;

    protected function getHandlers()
    {
        return array_map(
            function ($identifier) {
                return Injector::inst()->get($identifier);
            },
            $this->config()->get('handlers')
        );
    }

    /**
     * Identify the current user from the request
     */
    public function preRequest(HTTPRequest $request, Session $session, DataModel $model)
    {
        try {
            foreach ($this->getHandlers() as $handler) {
                // @todo Update requestfilter logic to allow modification of initial response
                // in order to add cookies, etc
                $member = $handler->authenticateRequest($request, new HTTPResponse());
                if ($member) {
                    // @todo Remove the static coupling here
                    Security::setCurrentUser($member);
                    break;
                }
            }
        } catch (ValidationException $e) {
            throw new HTTPResponse_Exception(
                "Bad log-in details: " . $e->getMessage(),
                400
            );
        }
    }

    /**
     * No-op
     */
    public function postRequest(HTTPRequest $request, HTTPResponse $response, DataModel $model)
    {
    }

    /**
     * Log into the identity-store handlers attached to this request filter
     *
     * @inherit
     */
    public function logIn(Member $member, $persistent, HTTPRequest $request)
    {
        // @todo Coupling here isn't ideal.
        $member->beforeMemberLoggedIn();

        foreach ($this->getHandlers() as $handler) {
            if ($handler instanceof IdentityStore) {
                $handler->logIn($member, $persistent, $request);
            }
        }

        // @todo Coupling here isn't ideal.
        Security::setCurrentUser($member);
        $member->afterMemberLoggedIn();
    }

    /**
     * Log out of all the identity-store handlers attached to this request filter
     *
     * @inherit
     */
    public function logOut(HTTPRequest $request)
    {
        foreach ($this->getHandlers() as $handler) {
            if ($handler instanceof IdentityStore) {
                $handler->logOut($request);
            }
        }

        // @todo Coupling here isn't ideal.
        Security::setCurrentUser(null);
    }
}
