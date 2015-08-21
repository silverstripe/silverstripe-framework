<?php
/**
 * A set of static methods for manipulating cookies.
 *
 * @package framework
 * @subpackage misc
 */
class Cookie {

	/**
	 * @config
	 * @var boolean
	 */
	private static $report_errors = true;

	/**
	 * Fetch the current instance of the cookie backend
	 *
	 * @return Cookie_Backend The cookie backend
	 */
	public static function get_inst() {
		return Injector::inst()->get('Cookie_Backend');
	}

	/**
	 * Set a cookie variable
	 *
	 * @param string $name The variable name
	 * @param mixed $value The variable value.
	 * @param int $expiry The expiry time, in days. Defaults to 90.
	 * @param string $path See http://php.net/set_session
	 * @param string $domain See http://php.net/set_session
	 * @param boolean $secure See http://php.net/set_session
	 * @param boolean $httpOnly See http://php.net/set_session
	 */
	public static function set($name, $value, $expiry = 90, $path = null, $domain = null, $secure = false,
		$httpOnly = true
	) {
		return self::get_inst()->set($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * Get the cookie value by name
	 *
	 * @param string $name The name of the cookie to get
	 * @param boolean $includeUnsent Include cookies we've yet to send when fetching values
	 *
	 * @return string|null The cookie value or null if unset
	 */
	public static function get($name, $includeUnsent = true) {
		return self::get_inst()->get($name, $includeUnsent);
	}

	/**
	 * Get all the cookies
	 *
	 * @param boolean $includeUnsent Include cookies we've yet to send
	 * @return array All the cookies
	 */
	public static function get_all($includeUnsent = true) {
		return self::get_inst()->getAll($includeUnsent);
	}

	/**
	 * @deprecated
	 */
	public static function forceExpiry($name, $path = null, $domain = null) {
		Deprecation::notice('4.0', 'Use Cookie::force_expiry instead.');

		return self::force_expiry($name, $path, $domain);
	}

	/**
	 * @param string
	 * @param string
	 * @param string
	 */
	public static function force_expiry($name, $path = null, $domain = null, $secure = false, $httpOnly = true) {
		return self::get_inst()->forceExpiry($name, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * @deprecated
	 */
	public static function set_report_errors($reportErrors) {
		Deprecation::notice('4.0', 'Use "Cookie.report_errors" config setting instead');
		Config::inst()->update('Cookie', 'report_errors', $reportErrors);
	}

	/**
	 * @deprecated
	 */
	public static function report_errors() {
		Deprecation::notice('4.0', 'Use "Cookie.report_errors" config setting instead');
		return Config::inst()->get('Cookie', 'report_errors');
	}
}
