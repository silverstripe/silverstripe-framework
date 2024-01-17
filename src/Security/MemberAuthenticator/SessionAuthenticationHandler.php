<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\AuthenticationHandler;
use SilverStripe\Security\Member;
use SilverStripe\Security\SudoMode\SudoModeServiceInterface;

/**
 * Authenticate a member passed on a session cookie
 */
class SessionAuthenticationHandler implements AuthenticationHandler
{
    /**
     * @var string
     */
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
     */
    public function setSessionVariable($sessionVariable)
    {
        $this->sessionVariable = $sessionVariable;
    }

    /**
     * @param HTTPRequest $request
     * @return Member
     */
    public function authenticateRequest(HTTPRequest $request)
    {
        $session = $request->getSession();

        // Sessions are only started when a session cookie is detected
        if (!$session->isStarted()) {
            return null;
        }

        // If ID is a bad ID it will be treated as if the user is not logged in, rather than throwing a
        // ValidationException
        $id = $session->get($this->getSessionVariable());
        if (!$id) {
            return null;
        }
        $member = DataObject::get_by_id(Member::class, $id);
        return $member;
    }

    /**
     * @param Member $member
     * @param bool $persistent
     * @param HTTPRequest $request
     */
    public function logIn(Member $member, $persistent = false, HTTPRequest $request = null)
    {
        static::regenerateSessionId();
        $request = $request ?: Controller::curr()->getRequest();
        $session = $request->getSession();
        $session->set($this->getSessionVariable(), $member->ID);

        // This lets apache rules detect whether the user has logged in
        if (Member::config()->get('login_marker_cookie')) {
            Cookie::set(Member::config()->get('login_marker_cookie'), 1, 0);
        }

        // Activate sudo mode on login so the user doesn't have to reauthenticate for sudo
        // actions until the sudo mode timeout expires
        $service = Injector::inst()->get(SudoModeServiceInterface::class);
        $service->activate($session);
    }

    /**
     * Regenerate the session_id.
     */
    protected static function regenerateSessionId()
    {
        if (!Member::config()->get('session_regenerate_id')) {
            return;
        }

        // This can be called via CLI during testing.
        if (Director::is_cli()) {
            return;
        }

        $file = '';
        $line = '';

        // @ is to suppress win32 warnings/notices when session wasn't cleaned up properly
        // There's nothing we can do about this, because it's an operating system function!
        if (!headers_sent($file, $line)) {
            @session_regenerate_id(true);
        }
    }

    /**
     * @param HTTPRequest $request
     */
    public function logOut(HTTPRequest $request = null)
    {
        $request = $request ?: Controller::curr()->getRequest();
        $request->getSession()->destroy(true, $request);

        if (Member::config()->get('login_marker_cookie')) {
            Cookie::force_expiry(Member::config()->get('login_marker_cookie'));
        }
    }
}
