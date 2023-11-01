<?php

namespace SilverStripe\Control;

use SilverStripe\ORM\FieldType\DBDatetime;
use LogicException;

/**
 * A default backend for the setting and getting of cookies
 *
 * This backend allows one to better test Cookie setting and separate cookie
 * handling from the core
 *
 */
class CookieJar implements Cookie_Backend
{
    /**
     * Hold the cookies that were existing at time of instantiation (ie: The ones
     * sent to PHP by the browser)
     *
     * @var array Existing cookies sent by the browser
     */
    protected $existing = [];

    /**
     * Hold the current cookies (ie: a mix of those that were sent to us and we
     * have set without the ones we've cleared)
     *
     * @var array The state of cookies once we've sent the response
     */
    protected $current = [];

    /**
     * Hold any NEW cookies that were set by the application and will be sent
     * in the next response
     *
     * @var array New cookies set by the application
     */
    protected $new = [];

    /**
     * When creating the backend we want to store the existing cookies in our
     * "existing" array. This allows us to distinguish between cookies we received
     * or we set ourselves (and didn't get from the browser)
     *
     * @param array $cookies The existing cookies to load into the cookie jar.
     * Omit this to default to $_COOKIE
     */
    public function __construct($cookies = [])
    {
        $this->current = $this->existing = func_num_args()
            ? ($cookies ?: []) // Convert empty values to blank arrays
            : $_COOKIE;
    }

    /**
     * Set a cookie
     *
     * @param string $name The name of the cookie
     * @param string $value The value for the cookie to hold
     * @param float $expiry The number of days until expiry; 0 indicates a cookie valid for the current session
     * @param string $path The path to save the cookie on (falls back to site base)
     * @param string $domain The domain to make the cookie available on
     * @param boolean $secure Can the cookie only be sent over SSL?
     * @param boolean $httpOnly Prevent the cookie being accessible by JS
     */
    public function set($name, $value, $expiry = 90, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        //are we setting or clearing a cookie? false values are reserved for clearing cookies (see PHP manual)
        $clear = false;
        if ($value === false || $value === '' || $expiry < 0) {
            $clear = true;
            $value = false;
        }

        //expiry === 0 is a special case where we set a cookie for the current user session
        if ($expiry !== 0) {
            //don't do the maths if we are clearing
            $expiry = $clear ? -1 : DBDatetime::now()->getTimestamp() + (86400 * $expiry);
        }
        //set the path up
        $path = $path ? $path : Director::baseURL();
        //send the cookie
        $this->outputCookie($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
        //keep our variables in check
        if ($clear) {
            unset($this->new[$name], $this->current[$name]);
        } else {
            $this->new[$name] = $this->current[$name] = $value;
        }
    }

    /**
     * Get the cookie value by name
     *
     * Cookie names are normalised to work around PHP's behaviour of replacing incoming variable name . with _
     *
     * @param string $name The name of the cookie to get
     * @param boolean $includeUnsent Include cookies we've yet to send when fetching values
     *
     * @return string|null The cookie value or null if unset
     */
    public function get($name, $includeUnsent = true)
    {
        $cookies = $includeUnsent ? $this->current : $this->existing;
        if (isset($cookies[$name])) {
            return $cookies[$name];
        }

        //Normalise cookie names by replacing '.' with '_'
        $safeName = str_replace('.', '_', $name ?? '');
        if (isset($cookies[$safeName])) {
            return $cookies[$safeName];
        }
        return null;
    }

    /**
     * Get all the cookies
     *
     * @param boolean $includeUnsent Include cookies we've yet to send
     * @return array All the cookies
     */
    public function getAll($includeUnsent = true)
    {
        return $includeUnsent ? $this->current : $this->existing;
    }

    /**
     * Force the expiry of a cookie by name
     *
     * @param string $name The name of the cookie to expire
     * @param string $path The path to save the cookie on (falls back to site base)
     * @param string $domain The domain to make the cookie available on
     * @param boolean $secure Can the cookie only be sent over SSL?
     * @param boolean $httpOnly Prevent the cookie being accessible by JS
     */
    public function forceExpiry($name, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        $this->set($name, false, -1, $path, $domain, $secure, $httpOnly);
    }

    /**
     * The function that actually sets the cookie using PHP
     *
     * @see http://uk3.php.net/manual/en/function.setcookie.php
     *
     * @param string $name The name of the cookie
     * @param string|array $value The value for the cookie to hold
     * @param int $expiry A Unix timestamp indicating when the cookie expires; 0 means it will expire at the end of the session
     * @param string $path The path to save the cookie on (falls back to site base)
     * @param string $domain The domain to make the cookie available on
     * @param boolean $secure Can the cookie only be sent over SSL?
     * @param boolean $httpOnly Prevent the cookie being accessible by JS
     * @return boolean If the cookie was set or not; doesn't mean it's accepted by the browser
     */
    protected function outputCookie(
        $name,
        $value,
        $expiry = 90,
        $path = null,
        $domain = null,
        $secure = false,
        $httpOnly = true
    ) {
        $sameSite = $this->getSameSite($name);
        Cookie::validateSameSite($sameSite);
        // if headers aren't sent, we can set the cookie
        if (!headers_sent($file, $line)) {
            return setcookie($name ?? '', $value ?? '', [
                'expires' => $expiry ?? 0,
                'path' => $path ?? '',
                'domain' => $domain ?? '',
                'secure' => $this->cookieIsSecure($sameSite, (bool) $secure),
                'httponly' => $httpOnly ?? false,
                'samesite' => $sameSite,
            ]);
        }

        if (Cookie::config()->uninherited('report_errors')) {
            throw new LogicException(
                "Cookie '$name' can't be set. The site started outputting content at line $line in $file"
            );
        }
        return false;
    }

    /**
     * Cookies must be secure if samesite is "None"
     */
    private function cookieIsSecure(string $sameSite, bool $secure): bool
    {
        return $sameSite === 'None' ? true : $secure;
    }

    /**
     * Get the correct samesite value - Session cookies use a different configuration variable.
     */
    private function getSameSite(string $name): string
    {
        if ($name === session_name()) {
            return Session::config()->get('cookie_samesite') ?? Cookie::SAMESITE_LAX;
        }
        return Cookie::config()->get('default_samesite') ?? Cookie::SAMESITE_LAX;
    }
}
