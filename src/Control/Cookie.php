<?php

namespace SilverStripe\Control;

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
     * @param int $expiry
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
}
