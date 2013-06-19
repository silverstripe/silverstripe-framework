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
	 * @var string cookie class
	 */
	static $cookie_class = 'Cookie';

	private static $inst = null;

	public static function get_inst() {
		if(is_null(self::$inst)) {
			self::$inst = new self::$cookie_class();
		}
		return self::$inst;
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
		return self::get_inst()->inst_set($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
	}

	/**
	 * Get a cookie variable.
	 *
	 * @param string
	 * @return mixed
	 */
	public static function get($name) {
		return self::get_inst()->inst_get($name);
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
	public static function force_expiry($name, $path = null, $domain = null) {
		return self::get_inst()->inst_force_expiry($name, $path, $domain);
	}

	/**
	 * @deprecated 3.2 Use "Cookie.report_errors" config setting instead
	 * @param bool
	 */
	public static function set_report_errors($reportErrors) {
		Deprecation::notice('3.2', 'Use "Cookie.report_errors" config setting instead');
		self::get_inst()->inst_set_report_errors($reportErrors);
	}

	/**
	 * @deprecated 3.2 Use "Cookie.report_errors" config setting instead
	 * @return bool
	 */
	public static function report_errors() {
		Deprecation::notice('3.2', 'Use "Cookie.report_errors" config setting instead');
		return self::get_inst()->inst_report_errors();
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
	protected function inst_set($name, $value, $expiry = 90, $path = null, 
		$domain = null, $secure = false, $httpOnly = false
	) {
		if(!headers_sent($file, $line)) {
			$expiry = $expiry > 0 ? time()+(86400*$expiry) : $expiry;
			$path = ($path) ? $path : Director::baseURL();
			setcookie($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
			$_COOKIE[$name] = $value;
		} else {
			if(Config::inst()->get('Cookie', 'report_errors')) {
				user_error("Cookie '$name' can't be set. The site started outputting content at line $line in $file",
					E_USER_WARNING);
			}
		}
	}

	/**
	 * @param string
	 * @return mixed
	 */
	protected function inst_get($name) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}

	/**
	 * @param string
	 */
	protected function inst_force_expiry($name, $path = null, $domain = null) {
		if(!headers_sent($file, $line)) {
			self::set($name, null, -20, $path, $domain);
		}
	}

	/**
	 * @deprecated 3.2 Use the "Cookie.report_errors" config setting instead
	 * @param bool
	 */
	protected function inst_set_report_errors($reportErrors) {
		Deprecation::notice('3.2', 'Use the "Cookie.report_errors" config setting instead');
		Config::inst()->update('Cookie', 'report_errors', $reportErrors);
	}

	/**
	 * @deprecated 3.2 Use the "Cookie.report_errors" config setting instead
	 * @return bool
	 */
	protected function inst_report_errors() {
		Deprecation::notice('3.2', 'Use the "Cookie.report_errors" config setting instead');
		return Config::inst()->get('Cookie', 'report_errors');
	}
}
