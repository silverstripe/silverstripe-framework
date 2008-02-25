<?php

/**
 * @package sapphire
 * @subpackage security
 */

/**
 * Provides an interface to HTTP basic authentication.
 * @package sapphire
 * @subpackage security
 */
class BasicAuth extends Object {
	/**
	 * Require basic authentication.  Will request a username and password if none is given.
	 * @param memberValidationFunction A boolean method to call on the member to validate them.
	 */
	static function requireLogin($realm, $permissionCode) {
		if(self::$disabled) return true;
		if(!Security::database_is_ready()) return true;
		
		
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$member = MemberAuthenticator::authenticate(array(
				'Email' => $_SERVER['PHP_AUTH_USER'], 
				'Password' => $_SERVER['PHP_AUTH_PW'],
			), null);
			
			if($member) {
				$authenticated = true;
			}
		}
		
		// If we've failed the authentication mechanism, then show the login form
		if(!isset($authenticated)) {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
			header('HTTP/1.0 401 Unauthorized');

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				echo _t('BasicAuth.ERRORNOTREC', "That username / password isn't recognised");
			} else {
				echo _t('BasicAuth.ENTERINFO', "Please enter a username and password.");
			}
			
			die();
		}
		
		if(!Permission::checkMember($member->ID, $permissionCode)) {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
			header('HTTP/1.0 401 Unauthorized');

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				echo _t('BasicAuth.ERRORNOTADMIN', "That user is not an administrator.");
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