<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\AuthenticationHandler;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Security;

/**
 * Authenticate a member passed on a session cookie
 */
class CookieAuthenticationHandler implements AuthenticationHandler
{
    private string $deviceCookieName = '';

    private string $tokenCookieName = '';

    private bool $tokenCookieSecure = true;

    /**
     * The SameSite value to use for authentication cookies.
     * If set to an empty string, the default value from Cookie.default_samesite will be used.
     */
    private string $tokenCookieSameSite = Cookie::SAMESITE_STRICT;

    /**
     * @var IdentityStore
     */
    private $cascadeInTo;

    /**
     * Get the name of the cookie used to track this device
     */
    public function getDeviceCookieName(): string
    {
        return $this->deviceCookieName;
    }

    /**
     * Set the name of the cookie used to track this device
     */
    public function setDeviceCookieName(string $deviceCookieName): static
    {
        $this->deviceCookieName = $deviceCookieName;
        return $this;
    }

    /**
     * Get the name of the cookie used to store an login token
     */
    public function getTokenCookieName(): string
    {
        return $this->tokenCookieName;
    }

    /**
     * Set the name of the cookie used to store an login token
     */
    public function setTokenCookieName(string $tokenCookieName): static
    {
        $this->tokenCookieName = $tokenCookieName;
        return $this;
    }

    /**
     * Get whether the cookie used to store an login token is "secure" or not
     */
    public function getTokenCookieSecure(): bool
    {
        if ($this->getTokenCookieSameSite() === Cookie::SAMESITE_NONE) {
            return true;
        }
        return $this->tokenCookieSecure;
    }

    /**
     * Set whether the cookie used to store an login token is "secure" or not
     */
    public function setTokenCookieSecure(bool $tokenCookieSecure): static
    {
        $this->tokenCookieSecure = $tokenCookieSecure;
        return $this;
    }

    /**
     * Get the "SameSite" attribute of authentication token cookies.
     * Empty string means the value from Cookie.default_samesite will be used.
     */
    public function getTokenCookieSameSite(): string
    {
        return $this->tokenCookieSameSite;
    }

    /**
     * Set the "SameSite" attribute of authentication token cookies.
     * Setting to an empty string means the value from Cookie.default_samesite will be used.
     */
    public function setTokenCookieSameSite(string $tokenCookieSameSite): static
    {
        $this->tokenCookieSameSite = $tokenCookieSameSite;
        return $this;
    }

    /**
     * Once a member is found by authenticateRequest() pass it to this identity store
     *
     * @return IdentityStore
     */
    public function getCascadeInTo()
    {
        return $this->cascadeInTo;
    }

    /**
     * Set the name of the cookie used to store an login token
     *
     * @param IdentityStore $cascadeInTo
     * @return $this
     */
    public function setCascadeInTo(IdentityStore $cascadeInTo)
    {
        $this->cascadeInTo = $cascadeInTo;
        return $this;
    }

    /**
     * @param HTTPRequest $request
     * @return Member
     */
    public function authenticateRequest(HTTPRequest $request)
    {
        $uidAndToken = Cookie::get($this->getTokenCookieName());
        $deviceID = Cookie::get($this->getDeviceCookieName());

        if ($deviceID === null || strpos($uidAndToken ?? '', ':') === false) {
            return null;
        }

        list($uid, $token) = explode(':', $uidAndToken ?? '', 2);

        if (!$uid || !$token) {
            return null;
        }

        // check if autologin token matches
        $member = Member::get()->byID($uid);
        if (!$member) {
            return null;
        }

        $hash = $member->encryptWithUserSettings($token);

        $rememberLoginHash = RememberLoginHash::get()
            ->filter([
                'MemberID' => $member->ID,
                'DeviceID' => $deviceID,
                'Hash' => $hash,
            ])->first();
        if (!$rememberLoginHash) {
            return null;
        }

        // Check for expired token
        $expiryDate = new \DateTime($rememberLoginHash->ExpiryDate);
        $now = DBDatetime::now();
        $now = new \DateTime($now->Rfc2822());
        if ($now > $expiryDate) {
            return null;
        }

        if ($this->cascadeInTo) {
            $this->cascadeInTo->logIn($member, false, $request);
        }

        // Session renewal hook
        $rememberLoginHash->extend('onAfterRenewSession');

        // Audit logging hook
        $member->extend('memberAutoLoggedIn');

        return $member;
    }

    /**
     * @param Member $member
     * @param bool $persistent
     * @param HTTPRequest $request
     */
    public function logIn(Member $member, $persistent = false, ?HTTPRequest $request = null)
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
            $secure = $this->getTokenCookieSecure();
            $sameSite = $this->getTokenCookieSameSite();
            Cookie::set(
                $this->getTokenCookieName(),
                $member->ID . ':' . $rememberLoginHash->getToken(),
                $tokenExpiryDays,
                null,
                null,
                $secure,
                true,
                $sameSite
            );
            Cookie::set(
                $this->getDeviceCookieName(),
                $rememberLoginHash->DeviceID,
                $deviceExpiryDays,
                null,
                null,
                $secure,
                true,
                $sameSite
            );
        } else {
            // Clear a cookie for non-persistent log-ins
            $this->clearCookies();
        }
    }

    /**
     * @param HTTPRequest $request
     */
    public function logOut(?HTTPRequest $request = null)
    {
        $member = Security::getCurrentUser();
        if ($member) {
            RememberLoginHash::clear($member, Cookie::get($this->getDeviceCookieName()));
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
        $secure = $this->getTokenCookieSecure();
        $sameSite = $this->getTokenCookieSameSite();
        Cookie::set($this->getTokenCookieName(), false, 0, secure: $secure, sameSite: $sameSite);
        Cookie::set($this->getDeviceCookieName(), false, 0, secure: $secure, sameSite: $sameSite);
        Cookie::force_expiry($this->getTokenCookieName(), secure: $secure, sameSite: $sameSite);
        Cookie::force_expiry($this->getDeviceCookieName(), secure: $secure, sameSite: $sameSite);
    }
}
