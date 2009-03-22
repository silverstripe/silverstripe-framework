<?php
/**
 * Provides an interface to HTTP basic authentication.
 * @package sapphire
 * @subpackage security
 */
class BasicAuth extends Object {
	
	/**
	 * Site-wide basic auth is disabled by default but can be enabled as needed in _config.php by calling BasicAuth::enable()
	 * @var boolean
	 */
	static protected $enabled = false;
	static protected $autologin = false;

	/**
	 * Require basic authentication.  Will request a username and password if none is given.
	 * 
	 * Used by {@link Controller::init()}.
	 * 
	 * @param string $realm
	 * @param string|array $permissionCode
	 * @return Member $member 
	 */
	static function requireLogin($realm, $permissionCode) {
		if(!self::$enabled) return true;
		if(!Security::database_is_ready() || Director::is_cli()) return true;
		
		
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$member = MemberAuthenticator::authenticate(array(
				'Email' => $_SERVER['PHP_AUTH_USER'], 
				'Password' => $_SERVER['PHP_AUTH_PW'],
			), null);
			
			if($member) {
				$authenticated = true;
				if(self::$autologin) {
					$member->logIn();
				}
			}
		}
		
		// If we've failed the authentication mechanism, then show the login form
		if(!isset($authenticated)) {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
			header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				echo _t('BasicAuth.ERRORNOTREC', "That username / password isn't recognised");
			} else {
				echo _t('BasicAuth.ENTERINFO', "Please enter a username and password.");
			}
			
			die();
		}
		
		if(!Permission::checkMember($member->ID, $permissionCode)) {
			header("WWW-Authenticate: Basic realm=\"$realm\"");
			header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				echo _t('BasicAuth.ERRORNOTADMIN', "That user is not an administrator.");
			}
			
			die();
		}
		
		return $member;
	}
	
	static function enable($auto = false) {
		self::$enabled = true;
		self::$autologin = $auto;
	}
	static function disable() {
		self::$enabled = false;
	}
}
