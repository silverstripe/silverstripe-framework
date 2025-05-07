<?php

namespace SilverStripe\Control;

/**
 * The Cookie_Backend interface for use with `Cookie::$inst`.
 *
 * See Cookie_DefaultBackend and Cookie
 */
interface Cookie_Backend
{

    /**
     * When creating the backend we want to store the existing cookies in our
     * "existing" array. This allows us to distinguish between cookies we received
     * or we set ourselves (and didn't get from the browser)
     *
     * @param array $cookies The existing cookies to load into the cookie jar
     */
    public function __construct(array $cookies = []);

    /**
     * Set a cookie
     *
     * @param string $name The name of the cookie
     * @param string|false $value The value for the cookie to hold. Empty string or false will clear the cookie
     * @param int|float $expiry The number of days until expiry; 0 means it will expire at the end of the session
     * @param string|null $path The path to save the cookie on (falls back to site base)
     * @param string|null $domain The domain to make the cookie available on
     * @param boolean $secure Can the cookie only be sent over SSL?
     * @param boolean $httpOnly Prevent the cookie being accessible by JS
     * @param string $sameSite The "SameSite" value for the cookie. Must be one of "None", "Lax", or "Strict".
     * If $sameSite is left empty, the default will be used.
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
    ): void;

    /**
     * Get the cookie value by name
     *
     * @param string $name The name of the cookie to get
     * @param boolean $includeUnsent Include cookies we've yet to send when fetching values
     *
     * @return string|null The cookie value or null if unset
     */
    public function get(string $name, bool $includeUnsent = true): ?string;

    /**
     * Get all the cookies
     *
     * @param boolean $includeUnsent Include cookies we've yet to send
     * @return array All the cookies
     */
    public function getAll(bool $includeUnsent = true): array;

    /**
     * Force the expiry of a cookie by name
     *
     * @param string $name The name of the cookie to expire
     * @param string|null $path The path to save the cookie on (falls back to site base)
     * @param string|null $domain The domain to make the cookie available on
     * @param boolean $secure Can the cookie only be sent over SSL?
     * @param boolean $httpOnly Prevent the cookie being accessible by JS
     * @param string $sameSite The "SameSite" value for the cookie. Must be one of "None", "Lax", or "Strict".
     * If $sameSite is left empty, the default will be used.
     */
    public function forceExpiry(
        string $name,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = ''
    ): void;
}
