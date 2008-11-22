<?php
/**
 * A set of static methods for manipulating cookies.
 * @package sapphire
 * @subpackage misc
 */
class Cookie extends Object {
	static $report_errors = true;
	
	/**
	 * Set a cookie variable
	 * @param name The variable name
	 * @param value The variable value.  May be an array or object if you wish.
	 * @param expiryDays The expiry time, in days.  Defaults to 90.
	 */
	static function set($name, $value, $expiryDays = 90) {
		if(!headers_sent($file, $line)) {
			$expiry = $expiryDays > 0 ? time()+(86400*$expiryDays) : 0;
			setcookie($name, $value, $expiry, Director::baseURL());
		} else {
			if(self::$report_errors) user_error("Cookie '$name' can't be set. The site started outputting was content at line $line in $file", E_USER_WARNING);
		}
		$_COOKIE[$name] = $value;
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