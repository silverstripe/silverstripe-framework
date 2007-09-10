<?php

/**
 * Provides an interface to HTTP basic authentication.
 */
class BasicAuth extends Object {
	/**
	 * Require basic authentication.  Will request a username and password if none is given.
	 * @param memberValidationFunction A boolean method to call on the member to validate them.
	 */
	static function requireLogin($realm, $permissionCode) {
		if(self::$disabled) return true;
		
		
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$member = Security::authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
			if($member) {
				$authenticated = true;
			}
		}
		
		// If we've failed the authentication mechanism, then show the login form
		if(!isset($authenticated)) {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
			header('HTTP/1.0 401 Unauthorized');

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				echo "That username / password isn't recognised";
			} else {
				echo "Please enter a username and password.";
			}
			
			die();
		}
		
		if(!Permission::checkMember($member->ID, $permissionCode)) {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
			header('HTTP/1.0 401 Unauthorized');

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				echo "That user is not an administrator.";
			}
			
			die();
		}
		
		return $member;
	}
	
	static protected $disabled;
	static function disable() {
		self::$disabled = true;
	}
}