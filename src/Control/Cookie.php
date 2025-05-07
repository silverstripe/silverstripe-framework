<?php

namespace SilverStripe\Control;

use LogicException;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

/**
 * A set of static methods for manipulating cookies.
 */
class Cookie
{
    use Configurable;

    public const SAMESITE_STRICT = 'Strict';

    public const SAMESITE_LAX = 'Lax';

    public const SAMESITE_NONE = 'None';

    private static bool $report_errors = true;

    /**
     * Must be "Strict", "Lax", or "None"
     */
    private static string $default_samesite = Cookie::SAMESITE_LAX;

    /**
     * Fetch the current instance of the cookie backend.
     *
     * @return Cookie_Backend
     */
    public static function get_inst(): Cookie_Backend
    {
        return Injector::inst()->get(Cookie_Backend::class);
    }

    /**
     * Set a cookie variable.
     *
     * Expiry time is set in days, and defaults to 90.
     *
     * See http://php.net/set_session
     */
    public static function set(
        string $name,
        string|false $value,
        int|float $expiry = 90,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = ''
    ) {
        if ($sameSite === '') {
            $sameSite = static::config()->get('default_samesite') ?? Cookie::SAMESITE_LAX;
        }
        static::validateSameSite($sameSite);
        return Cookie::get_inst()->set($name, $value, $expiry, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * Get the cookie value by name. Returns null if not set.
     */
    public static function get(string $name, bool $includeUnsent = true): ?string
    {
        return Cookie::get_inst()->get($name, $includeUnsent);
    }

    /**
     * Get all the cookies.
     */
    public static function get_all(bool $includeUnsent = true): array
    {
        return Cookie::get_inst()->getAll($includeUnsent);
    }

    /**
     * Force the expiry of a cookie by name
     */
    public static function force_expiry(
        string $name,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = ''
    ): void {
        Cookie::get_inst()->forceExpiry($name, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * Get the default value for the "samesite" cookie attribute.
     */
    public static function getDefaultSameSite(): string
    {
        return static::config()->get('default_samesite') ?? Cookie::SAMESITE_LAX;
    }

    /**
     * Validate if the samesite value for a cookie is valid for the current request.
     *
     * Logs a warning if the samesite value is "None" for a non-https request.
     * @throws LogicException if the value is not "Strict", "Lax", or "None".
     */
    public static function validateSameSite(string $sameSite): void
    {
        $validValues = [
            Cookie::SAMESITE_STRICT,
            Cookie::SAMESITE_LAX,
            Cookie::SAMESITE_NONE,
        ];
        if (!in_array($sameSite, $validValues)) {
            throw new LogicException('Cookie samesite must be "Strict", "Lax", or "None"');
        }
        if ($sameSite === Cookie::SAMESITE_NONE && !Director::is_https(Cookie::getRequest())) {
            Injector::inst()->get(LoggerInterface::class)->warning('Cookie samesite cannot be "None" for non-https requests.');
        }
    }

    /**
     * Get the current request, if any.
     */
    private static function getRequest(): ?HTTPRequest
    {
        $request = Controller::curr()?->getRequest();
        // NullHTTPRequest always has a scheme of http - set to null so we can fallback on default_base_url
        return ($request instanceof NullHTTPRequest) ? null : $request;
    }
}
