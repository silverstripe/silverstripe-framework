<?php

/**
 * @package sapphire
 * @subpackage misc
 */

/**
 * A set of static methods for manipulating cookies.
 * @package sapphire
 * @subpackage misc
 */
class Cookie extends Object {
	/**
	 * Set a cookie variable
	 * @param name The variable name
	 * @param value The variable value.  May be an array or object if you wish.
	 * @param expiryDays The expiry time, in days.  Defaults to 90.
	 */
	static function set($name, $value, $expiryDays = 90) {
		if(!headers_sent($file, $line)) {
			setcookie($name, $value, time()+(86400*$expiryDays), Director::baseURL());
			$_COOKIE[$name] = $value;
		} else {
			 // if(Director::isDevMode()) user_error("Cookie '$name' can't be set. The site started outputting was content at line $line in $file", E_USER_WARNING);
		}
	}
	
	/**
	 * Get a cookie variable
	 */
	static function get($name) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;		
	}	
	
	static function forceExpiry( $name ) {
		setcookie( $name, null, time() - 86400 );
	}
}

?>