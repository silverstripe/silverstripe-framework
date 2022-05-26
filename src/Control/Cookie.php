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

    /**
     * @config
     *
     * @var bool
     */
    private static $report_errors = true;

    /**
     * Must be "Strict", "Lax", or "None"
     * @config
     */
    private static string $default_samesite = 'Lax';

    /**
     * Fetch the current instance of the cookie backend.
     *
     * @return Cookie_Backend
     */
    public static function get_inst()
    {
        return Injector::inst()->get('SilverStripe\\Control\\Cookie_Backend');
    }

    /**
     * Set a cookie variable.
     *
     * Expiry time is set in days, and defaults to 90.
     *
     * @param string $name
     * @param mixed $value
     * @param float $expiry
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     *
     * See http://php.net/set_session
     */
    public static function set(
        $name,
        $value,
        $expiry = 90,
        $path = null,
        $domain = null,
        $secure = false,
        $httpOnly = true
    ) {
        return self::get_inst()->set($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Get the cookie value by name. Returns null if not set.
     *
     * @param string $name
     * @param bool $includeUnsent
     *
     * @return null|string
     */
    public static function get($name, $includeUnsent = true)
    {
        return self::get_inst()->get($name, $includeUnsent);
    }

    /**
     * Get all the cookies.
     *
     * @param bool $includeUnsent
     *
     * @return array
     */
    public static function get_all($includeUnsent = true)
    {
        return self::get_inst()->getAll($includeUnsent);
    }

    /**
     * @param string $name
     * @param null|string $path
     * @param null|string $domain
     * @param bool $secure
     * @param bool $httpOnly
     */
    public static function force_expiry($name, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        return self::get_inst()->forceExpiry($name, $path, $domain, $secure, $httpOnly);
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
            'Strict',
            'Lax',
            'None',
        ];
        if (!in_array($sameSite, $validValues)) {
            throw new LogicException('Cookie samesite must be "Strict", "Lax", or "None"');
        }
        if ($sameSite === 'None' && !Director::is_https(self::getRequest())) {
            Injector::inst()->get(LoggerInterface::class)->warning('Cookie samesite cannot be "None" for non-https requests.');
        }
    }

    /**
     * Get the current request, if any.
     */
    private static function getRequest(): ?HTTPRequest
    {
        $request = null;
        if (Controller::has_curr()) {
            $request = Controller::curr()->getRequest();
        }
        // NullHTTPRequest always has a scheme of http - set to null so we can fallback on default_base_url
        return ($request instanceof NullHTTPRequest) ? null : $request;
    }
}
