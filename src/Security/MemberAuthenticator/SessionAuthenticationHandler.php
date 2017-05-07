<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Control\Director;
use SilverStripe\Security\AuthenticationHandler as AuthenticationHandlerInterface;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\IdentityStore;

/**
 * Authenticate a member pased on a session cookie
 */
class SessionAuthenticationHandler implements AuthenticationHandlerInterface, IdentityStore
{

    private $sessionVariable;

    /**
     * Get the session variable name used to track member ID
     *
     * @return string
     */
    public function getSessionVariable()
    {
        return $this->sessionVariable;
    }

    /**
     * Set the session variable name used to track member ID
     *
     * @param string $sessionVariable
     * @return null
     */
    public function setSessionVariable($sessionVariable)
    {
        $this->sessionVariable = $sessionVariable;
    }

    /**
     * @inherit
     */
    public function authenticateRequest(HTTPRequest $request)
    {
        // @todo couple the session to a request object
        // $session = $request->getSession();

        if ($id = Session::get($this->getSessionVariable())) {
            // If ID is a bad ID it will be treated as if the user is not logged in, rather than throwing a
            // ValidationException
            return DataObject::get_by_id(Member::class, $id);
        }

        return null;
    }

    /**
     * @inherit
     */
    public function logIn(Member $member, $persistent, HTTPRequest $request)
    {
        // @todo couple the session to a request object
        // $session = $request->getSession();

        $this->regenerateSessionId();
        Session::set($this->getSessionVariable(), $member->ID);

        // This lets apache rules detect whether the user has logged in
        // @todo make this a settign on the authentication handler
        if (Member::config()->login_marker_cookie) {
            Cookie::set(Member::config()->login_marker_cookie, 1, 0);
        }
    }

    /**
     * Regenerate the session_id.
     */
    protected static function regenerateSessionId()
    {
        if (!Member::config()->session_regenerate_id) {
            return;
        }

        // This can be called via CLI during testing.
        if (Director::is_cli()) {
            return;
        }

        $file = '';
        $line = '';

        // @ is to supress win32 warnings/notices when session wasn't cleaned up properly
        // There's nothing we can do about this, because it's an operating system function!
        if (!headers_sent($file, $line)) {
            @session_regenerate_id(true);
        }
    }
    /**
     * @inherit
     */
    public function logOut(HTTPRequest $request)
    {
        // @todo couple the session to a request object
        // $session = $request->getSession();

        Session::clear($this->getSessionVariable());
    }
}
