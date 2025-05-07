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
    protected array $existing = [];

    /**
     * Hold the current cookies (ie: a mix of those that were sent to us and we
     * have set without the ones we've cleared)
     *
     * @var array The state of cookies once we've sent the response
     */
    protected array $current = [];

    /**
     * Hold any NEW cookies that were set by the application and will be sent
     * in the next response
     *
     * @var array New cookies set by the application
     */
    protected array $new = [];

    /**
     * @inheritDoc
     */
    public function __construct(array $cookies = [])
    {
        $this->current = $this->existing = func_num_args()
            ? ($cookies ?: []) // Convert empty values to blank arrays
            : $_COOKIE;
    }

    /**
     * @inheritDoc
     */
    public function set(
        string $name,
        string|false $value,
        int|float $expiry = 90,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = ''
    ): void {
        if ($sameSite === '') {
            $sameSite = Cookie::getDefaultSameSite();
        }
        Cookie::validateSameSite($sameSite);

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
        $this->outputCookie($name, $value, $expiry, $path, $domain, $secure, $httpOnly, $sameSite);
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
     * @inheritDoc
     */
    public function get(string $name, bool $includeUnsent = true): ?string
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
     * @inheritDoc
     */
    public function getAll(bool $includeUnsent = true): array
    {
        return $includeUnsent ? $this->current : $this->existing;
    }

    /**
     * @inheritDoc
     */
    public function forceExpiry(
        string $name,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = ''
    ): void {
        $this->set($name, false, -1, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * The function that actually sets the cookie using PHP
     *
     * @see http://uk3.php.net/manual/en/function.setcookie.php
     *
     * @param string $name The name of the cookie
     * @param string|false $value The value for the cookie to hold. Empty string or false will clear the cookie.
     * @param int $expiry A Unix timestamp indicating when the cookie expires; 0 means it will expire at the end of the session
     * @param ?string $path The path to save the cookie on (falls back to site base)
     * @param ?string $domain The domain to make the cookie available on
     * @param bool $secure Can the cookie only be sent over SSL?
     * @param bool $httpOnly Prevent the cookie being accessible by JS
     * @param string $sameSite The "SameSite" value for the cookie. Must be one of "None", "Lax", or "Strict".
     * If $sameSite is left empty, the default will be used.
     * @return bool If the cookie was set or not; doesn't mean it's accepted by the browser
     */
    protected function outputCookie(
        string $name,
        string|false $value,
        int $expiry,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = ''
    ): bool {
        if ($sameSite === '') {
            $sameSite = Cookie::getDefaultSameSite();
        }
        Cookie::validateSameSite($sameSite);
        // if headers aren't sent, we can set the cookie
        if (!headers_sent($file, $line)) {
            return setcookie($name, $value, [
                'expires' => $expiry,
                'path' => $path ?? '',
                'domain' => $domain ?? '',
                'secure' => $this->cookieIsSecure($sameSite, (bool) $secure),
                'httponly' => $httpOnly,
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
}
