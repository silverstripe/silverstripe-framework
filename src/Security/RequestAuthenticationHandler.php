<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;

/**
 * Core authentication handler / store
 */
class RequestAuthenticationHandler implements AuthenticationHandler
{
    /**
     * @var AuthenticationHandler[]
     */
    protected $handlers = [];

    /**
     * This method currently uses a fallback as loading the handlers via YML has proven unstable
     *
     * @return AuthenticationHandler[]
     */
    protected function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Set an associative array of handlers
     *
     * @param AuthenticationHandler[] $handlers
     * @return $this
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = $handlers;
        return $this;
    }

    public function authenticateRequest(HTTPRequest $request)
    {
        foreach ($this->getHandlers() as $name => $handler) {
            // in order to add cookies, etc
            $member = $handler->authenticateRequest($request);
            if ($member) {
                Security::setCurrentUser($member);
                return;
            }
        }
    }
    /**
     * Log into the identity-store handlers attached to this request filter
     *
     * @param Member $member
     * @param bool $persistent
     * @param HTTPRequest $request
     */
    public function logIn(Member $member, $persistent = false, HTTPRequest $request = null)
    {
        $member->beforeMemberLoggedIn();

        foreach ($this->getHandlers() as $handler) {
            $handler->logIn($member, $persistent, $request);
        }

        Security::setCurrentUser($member);
        $member->afterMemberLoggedIn();
    }

    /**
     * Log out of all the identity-store handlers attached to this request filter
     *
     * @param HTTPRequest $request
     */
    public function logOut(HTTPRequest $request = null)
    {
        $member = Security::getCurrentUser();
        if ($member) {
            $member->beforeMemberLoggedOut($request);
        }

        foreach ($this->getHandlers() as $handler) {
            $handler->logOut($request);
        }

        Security::setCurrentUser(null);

        if ($member) {
            $member->afterMemberLoggedOut($request);
        }
    }
}
