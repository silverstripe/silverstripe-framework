<?php
/**
 * A set of static methods for manipulating cookies.
 *
 * @package framework
 * @subpackage misc
 */
class Cookie {
	
	/**
	 * @var boolean
	 */
	static $report_errors = true;
	
	/**
	 * Set a cookie variable
	 * 
	 * @param string $name The variable name
	 * @param string $value The variable value.
	 * @param int $expiry The expiry time, in days. Defaults to 90.
	 * @param string $path See http://php.net/set_session
	 * @param string $domain See http://php.net/set_session
	 * @param boolean $secure See http://php.net/set_session
	 * @param boolean $httpOnly See http://php.net/set_session
	 */
	static function set($name, $value, $expiry = 90, $path = null, $domain = null, $secure = false, $httpOnly = false) {
		if(!headers_sent($file, $line)) {
			$expiry = $expiry > 0 ? time()+(86400*$expiry) : $expiry;
			$path = ($path) ? $path : Director::baseURL();
			setcookie($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
		} else {
			if(self::$report_errors) {
				user_error("Cookie '$name' can't be set. The site started outputting was content at line $line in $file", E_USER_WARNING);
			}
		}
	}
	
	/**
	 * Get a cookie variable
	 */
	static function get($name) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;		
	}	
	
	static function forceExpiry($name, $path = null, $domain = null) {
		if(!headers_sent($file, $line)) {
			self::set($name, null, -20, $path, $domain);
		}
	}
	
	static function set_report_errors($reportErrors) {
		self::$report_errors = $reportErrors;
	}
	
	static function report_errors() {
		return self::$report_errors;
	}
}
