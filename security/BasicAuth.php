<?php
/**
 * Provides an interface to HTTP basic authentication.
 * 
 * This utility class can be used to secure any request with basic authentication.  To do so,
 * {@link BasicAuth::requireLogin()} from your Controller's init() method or action handler method.
 * 
 * It also has a function to protect your entire site.  See {@link BasicAuth::protect_entire_site()}
 * for more information.
 * 
 * @package sapphire
 * @subpackage security
 */
class BasicAuth {
	/**
	 * Flag set by {@link self::protect_entire_site()}
	 */
	private static $entire_site_protected = false;

	/**
	 * Require basic authentication.  Will request a username and password if none is given.
	 * 
	 * Used by {@link Controller::init()}.
	 * 
	 * @param string $realm
	 * @param string|array $permissionCode
	 * @param boolean $tryUsingSessionLogin If true, then the method with authenticate against the
	 * session log-in if those credentials are disabled.
	 * @return Member $member 
	 */
	static function requireLogin($realm, $permissionCode, $tryUsingSessionLogin = true) {
		if(!Security::database_is_ready() || Director::is_cli()) return true;
		
		$member = null;
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$member = MemberAuthenticator::authenticate(array(
				'Email' => $_SERVER['PHP_AUTH_USER'], 
				'Password' => $_SERVER['PHP_AUTH_PW'],
			), null);
		}
		
		if(!$member && $tryUsingSessionLogin) $member = Member::currentUser();
		
		// If we've failed the authentication mechanism, then show the login form
		if(!$member) {
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
		
	/**
	 * Enable protection of the entire site with basic authentication.
	 * 
	 * This log-in uses the Member database for authentication, but doesn't interfere with the
	 * regular log-in form. This can be useful for test sites, where you want to hide the site
	 * away from prying eyes, but still be able to test the regular log-in features of the site.
	 * 
	 * If you are including conf/ConfigureFromEnv.php in your _config.php file, you can also enable
	 * this feature by adding this line to your _ss_environment.php:
	 * 
	 * define('SS_USE_BASIC_AUTH', true);
	 * 
	 * @param $protect Set this to false to disable protection.
	 */
	static function protect_entire_site($protect = true) {
		return self::$entire_site_protected = $protect;
	}
	
	/**
	 * @deprecated Use BasicAuth::protect_entire_site() instead.
	 */
	static function enable() {
		user_error("BasicAuth::enable() is deprecated.  Use BasicAuth::protect_entire_site() instead.", E_USER_NOTICE);
		return self::protect_entire_site();
	}

	/**
	 * @deprecated Use BasicAuth::protect_entire_site(false) instead.
	 */
	static function disable() {
		user_error("BasicAuth::disable() is deprecated.  Use BasicAuth::protect_entire_site(false) instead.", E_USER_NOTICE);
		return self::protect_entire_site(false);
	}

	/**
	 * Call {@link BasicAuth::requireLogin()} if {@link BasicAuth::protect_entire_site()} has been called.
	 * This is a helper function used by Controller.
	 */
	static function protect_site_if_necessary() {
		if(self::$entire_site_protected) {
			// The test-site protection should ignore the session log-in; otherwise it's difficult
			// to test the log-in features of your site
			self::requireLogin("SilverStripe test website. Use your CMS login.", "ADMIN", false);
		}
	}

}
