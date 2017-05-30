<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\RequestFilter;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataModel;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;

class AuthenticationRequestFilter implements RequestFilter, IdentityStore
{

    use Configurable;

    /**
     * @var array|AuthenticationHandler[]
     */
    protected $handlers;

    /**
     * This method currently uses a fallback as loading the handlers via YML has proven unstable
     *
     * @return array|AuthenticationHandler[]
     */
    protected function getHandlers()
    {
        if (is_array($this->handlers)) {
            return $this->handlers;
        }

        return array_map(
            function ($identifier) {
                return Injector::inst()->get($identifier);
            },
            static::config()->get('handlers')
        );
    }

    /**
     * Set an associative array of handlers
     *
     * @param array|AuthenticationHandler[] $handlers
     */
    public function setHandlers($handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * Identify the current user from the request
     *
     * @param HTTPRequest $request
     * @param Session $session
     * @param DataModel $model
     * @return bool|void
     * @throws HTTPResponse_Exception
     */
    public function preRequest(HTTPRequest $request, Session $session, DataModel $model)
    {
        try {
            /** @var AuthenticationHandler $handler */
            foreach ($this->getHandlers() as $name => $handler) {
                // @todo Update requestfilter logic to allow modification of initial response
                // in order to add cookies, etc
                $member = $handler->authenticateRequest($request);
                if ($member) {
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
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     * @param DataModel $model
     * @return bool|void
     */
    public function postRequest(HTTPRequest $request, HTTPResponse $response, DataModel $model)
    {
    }

    /**
     * Log into the identity-store handlers attached to this request filter
     *
     * @param Member $member
     * @param bool $persistent
     * @param HTTPRequest $request
     * @return HTTPResponse|void
     */
    public function logIn(Member $member, $persistent = false, HTTPRequest $request = null)
    {
        $member->beforeMemberLoggedIn();

        foreach ($this->getHandlers() as $handler) {
            if ($handler instanceof IdentityStore) {
                $handler->logIn($member, $persistent, $request);
            }
        }

        Security::setCurrentUser($member);
        $member->afterMemberLoggedIn();
    }

    /**
     * Log out of all the identity-store handlers attached to this request filter
     *
     * @param HTTPRequest $request
     * @return HTTPResponse|void
     */
    public function logOut(HTTPRequest $request = null)
    {
        foreach ($this->getHandlers() as $handler) {
            if ($handler instanceof IdentityStore) {
                $handler->logOut($request);
            }
        }

        Security::setCurrentUser(null);
    }
}
