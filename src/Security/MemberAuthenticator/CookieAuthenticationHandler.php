<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\Member;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\AuthenticationHandler as AuthenticationHandlerInterface;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Security;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Control\Cookie;

/**
 * Authenticate a member pased on a session cookie
 */
class CookieAuthenticationHandler implements AuthenticationHandlerInterface, IdentityStore
{

    /**
     * @var string
     */
    private $deviceCookieName;

    /**
     * @var string
     */
    private $tokenCookieName;

    /**
     * @var IdentityStore
     */
    private $cascadeInTo;

    /**
     * Get the name of the cookie used to track this device
     *
     * @return string
     */
    public function getDeviceCookieName()
    {
        return $this->deviceCookieName;
    }

    /**
     * Set the name of the cookie used to track this device
     *
     * @param string $deviceCookieName
     * @return null
     */
    public function setDeviceCookieName($deviceCookieName)
    {
        $this->deviceCookieName = $deviceCookieName;
    }

    /**
     * Get the name of the cookie used to store an login token
     *
     * @return string
     */
    public function getTokenCookieName()
    {
        return $this->tokenCookieName;
    }

    /**
     * Set the name of the cookie used to store an login token
     *
     * @param string $tokenCookieName
     */
    public function setTokenCookieName($tokenCookieName)
    {
        $this->tokenCookieName = $tokenCookieName;
    }

    /**
     * Once a member is found by authenticateRequest() pass it to this identity store
     *
     * @return IdentityStore
     */
    public function getCascadeLogInTo()
    {
        return $this->cascadeInTo;
    }

    /**
     * Set the name of the cookie used to store an login token
     *
     * @param IdentityStore $cascadeInTo
     * @return null
     */
    public function setCascadeLogInTo(IdentityStore $cascadeInTo)
    {
        $this->cascadeInTo = $cascadeInTo;
    }

    /**
     * @param HTTPRequest $request
     * @return null|Member
     */
    public function authenticateRequest(HTTPRequest $request)
    {
        $uidAndToken = Cookie::get($this->getTokenCookieName());
        $deviceID = Cookie::get($this->getDeviceCookieName());

        // @todo Consider better placement of database_is_ready test
        if ($deviceID === null || strpos($uidAndToken, ':') === false || !Security::database_is_ready()) {
            return null;
        }

        list($uid, $token) = explode(':', $uidAndToken, 2);

        if (!$uid || !$token) {
            return null;
        }

        /** @var Member $member */
        $member = Member::get()->byID($uid);

        /** @var RememberLoginHash $rememberLoginHash */
        $rememberLoginHash = null;

        // check if autologin token matches
        if ($member) {
            $hash = $member->encryptWithUserSettings($token);
            $rememberLoginHash = RememberLoginHash::get()
                ->filter(array(
                    'MemberID' => $member->ID,
                    'DeviceID' => $deviceID,
                    'Hash'     => $hash
                ))->first();

            if (!$rememberLoginHash) {
                $member = null;
            } else {
                // Check for expired token
                $expiryDate = new \DateTime($rememberLoginHash->ExpiryDate);
                $now = DBDatetime::now();
                $now = new \DateTime($now->Rfc2822());
                if ($now > $expiryDate) {
                    $member = null;
                }
            }
        }

        if ($member) {
            if ($this->cascadeInTo) {
                // @todo look at how to block "regular login" triggers from happening here
                // @todo deal with the fact that the Session::current_session() isn't correct here :-/
                $this->cascadeInTo->logIn($member, false, $request);
            }

            // @todo Consider whether response should be part of logIn() as well

            // Renew the token
            if ($rememberLoginHash) {
                $rememberLoginHash->renew();
                $tokenExpiryDays = RememberLoginHash::config()->uninherited('token_expiry_days');
                Cookie::set(
                    $this->getTokenCookieName(),
                    $member->ID . ':' . $rememberLoginHash->getToken(),
                    $tokenExpiryDays,
                    null,
                    null,
                    false,
                    true
                );
            }

            // Audit logging hook
            $member->extend('memberAutoLoggedIn');

            return $member;
        }
    }

    /**
     * @param Member $member
     * @param bool $persistent
     * @param HTTPRequest $request
     * @return HTTPResponse|void
     */
    public function logIn(Member $member, $persistent = false, HTTPRequest $request = null)
    {
        // Cleans up any potential previous hash for this member on this device
        if ($alcDevice = Cookie::get($this->getDeviceCookieName())) {
            RememberLoginHash::get()->filter('DeviceID', $alcDevice)->removeAll();
        }

        // Set a cookie for persistent log-ins
        if ($persistent) {
            $rememberLoginHash = RememberLoginHash::generate($member);
            $tokenExpiryDays = RememberLoginHash::config()->uninherited('token_expiry_days');
            $deviceExpiryDays = RememberLoginHash::config()->uninherited('device_expiry_days');
            Cookie::set(
                $this->getTokenCookieName(),
                $member->ID . ':' . $rememberLoginHash->getToken(),
                $tokenExpiryDays,
                null,
                null,
                null,
                true
            );
            Cookie::set(
                $this->getDeviceCookieName(),
                $rememberLoginHash->DeviceID,
                $deviceExpiryDays,
                null,
                null,
                null,
                true
            );
        } else {
            // Clear a cookie for non-persistent log-ins
            $this->clearCookies();
        }
    }

    /**
     * @param HTTPRequest|null $request
     * @return HTTPResponse|void
     */
    public function logOut(HTTPRequest $request = null)
    {
        $member = Security::getCurrentUser();
        if ($member) {
            RememberLoginHash::clear($member, Cookie::get('alc_device'));
        }
        $this->clearCookies();

        if ($this->cascadeInTo) {
            $this->cascadeInTo->logOut($request);
        }

        Security::setCurrentUser(null);
    }

    /**
     * Clear the cookies set for the user
     */
    protected function clearCookies()
    {
        Cookie::set($this->getTokenCookieName(), null);
        Cookie::set($this->getDeviceCookieName(), null);
        Cookie::force_expiry($this->getTokenCookieName());
        Cookie::force_expiry($this->getDeviceCookieName());
    }
}
