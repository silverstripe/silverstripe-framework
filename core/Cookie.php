<?php
/**
 * A set of static methods for manipulating cookies.
 * @package sapphire
 * @subpackage misc
 */
class Cookie {
	static $report_errors = true;
	
	/**
	 * Set a cookie variable
	 * 
	 * @param string $name The variable name
	 * @param string $value The variable value.  May be an array or object if you wish.
	 * @param int $expiryDays The expiry time, in days.  Defaults to 90.
	 * @param string $path See http://php.net/set_session
	 * @param string $domain See http://php.net/set_session
	 * @param boolean $secure See http://php.net/set_session
	 * @param boolean $httpOnly See http://php.net/set_session (PHP 5.2+ only)
	 */
	static function set($name, $value, $expiryDays = 90, $path = null, $domain = null, $secure = false, $httpOnly = false) {
		if(!headers_sent($file, $line)) {
			$expiry = $expiryDays > 0 ? time()+(86400*$expiryDays) : 0;
			$path = ($path) ? $path : Director::baseURL();

			// Versions of PHP prior to 5.2 do not support the $httpOnly value
			if(version_compare(phpversion(), 5.2, '<')) {
				setcookie($name, $value, $expiry, $path, $domain, $secure);
			} else {
				setcookie($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
			}
		} else {
			if(self::$report_errors) user_error("Cookie '$name' can't be set. The site started outputting was content at line $line in $file", E_USER_WARNING);
		}
	}
	
	/**
	 * Get a cookie variable
	 */
	static function get($name) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;		
	}	
	
	static function forceExpiry( $name ) {
		if(!headers_sent($file, $line)) {
			setcookie( $name, null, time() - 86400 );
		}
	}
	
	static function set_report_errors($reportErrors) {
		self::$report_errors = $reportErrors;
	}
	static function report_errors() {
		return self::$report_errors;
	}
}

?>
