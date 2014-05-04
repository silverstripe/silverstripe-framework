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
	 * @var Cookie_Backend the backend object for setting / getting cookies
	 */
	private static $inst = null;

	/**
	 * Fetch the current instance of the cookie backend
	 *
	 * @return Cookie_Backend The cookie backend
	 */
	public static function get_inst() {
		//if we don't have a backend, create one
		if(is_null(self::$inst)) {
			self::set_inst(Injector::inst()->create('CookieJar', $_COOKIE));
		}
		return self::$inst;
	}

	/**
	 * Clear the cookie backend, this is useful for testing and if you need to
	 * change the backend partway through execution
	 */
	public static function clear_inst() {
		self::$inst = null;
	}

	/**
	 * Set the cookie backend
	 *
	 * @param Cookie_Backend The cookie backend to use
	 */
	public static function set_inst(Cookie_Backend $inst) {
		self::$inst = $inst;
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
		$httpOnly = false
	) {
		return self::get_inst()->set($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * Get a cookie variable.
	 *
	 * @param string
	 * @return mixed
	 */
	public static function get($name) {
		return self::get_inst()->get($name);
	}

	/**
	 * @param string
	 * @param string
	 * @param string
	 */
	public static function forceExpiry($name, $path = null, $domain = null) {
		Deprecation::notice('3.1', 'Use Cookie::force_expiry instead.');

		return self::force_expiry($name, $path, $domain);
	}

	/**
	 * @param string
	 * @param string
	 * @param string
	 */
	public static function force_expiry($name, $path = null, $domain = null, $secure = false, $httpOnly = false) {
		return self::get_inst()->forceExpiry($name, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * @deprecated 3.2 Use the "Cookie.report_errors" config setting instead
	 * @param bool
	 */
	protected function set_report_errors($reportErrors) {
		Deprecation::notice('3.2', 'Use the "Cookie.report_errors" config setting instead');
		Config::inst()->update('Cookie', 'report_errors', $reportErrors);
	}

	/**
	 * @deprecated 3.2 Use the "Cookie.report_errors" config setting instead
	 * @return bool
	 */
	protected function report_errors() {
		Deprecation::notice('3.2', 'Use the "Cookie.report_errors" config setting instead');
		return Config::inst()->get('Cookie', 'report_errors');
	}
}
