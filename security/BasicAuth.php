<?php
/**
 * Provides an interface to HTTP basic authentication.
 * 
 * This utility class can be used to secure any request with basic authentication.  To do so,
 * {@link BasicAuth::requireLogin()} from your Controller's init() method or action handler method.
 * 
 * It also has a function to protect your entire site.  See {@link BasicAuth::protect_entire_site()}
 * for more information. You can control this setting on controller-level by using {@link Controller->basicAuthEnabled}.
 * 
 * @package framework
 * @subpackage security
 */
class BasicAuth {
	/**
	 * @config
	 * @var Boolean Flag set by {@link self::protect_entire_site()}
	 */
	private static $entire_site_protected = false;
	
	/**
	 * @config
	 * @var String|array Holds a {@link Permission} code that is required
	 * when calling {@link protect_site_if_necessary()}. Set this value through 
	 * {@link protect_entire_site()}.
	 */
	private static $entire_site_protected_code = 'ADMIN';
	
	/**
	 * @config
	 * @var String Message that shows in the authentication box.
	 * Set this value through {@link protect_entire_site()}.
	 */
	private static $entire_site_protected_message = "SilverStripe test website. Use your CMS login.";

	/**
	 * Require basic authentication.  Will request a username and password if none is given.
	 * 
	 * Used by {@link Controller::init()}.
	 * 
	 * @throws SS_HTTPResponse_Exception
	 * 
	 * @param string $realm
	 * @param string|array $permissionCode Optional
	 * @param boolean $tryUsingSessionLogin If true, then the method with authenticate against the
	 *  session log-in if those credentials are disabled.
	 * @return Member $member 
	 */
	public static function requireLogin($realm, $permissionCode = null, $tryUsingSessionLogin = true) {
		$isRunningTests = (class_exists('SapphireTest', false) && SapphireTest::is_running_test());
		if(!Security::database_is_ready() || (Director::is_cli() && !$isRunningTests)) return true;
		
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
			$response = new SS_HTTPResponse(null, 401);
			$response->addHeader('WWW-Authenticate', "Basic realm=\"$realm\"");

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				$response->setBody(_t('BasicAuth.ERRORNOTREC', "That username / password isn't recognised"));
			} else {
				$response->setBody(_t('BasicAuth.ENTERINFO', "Please enter a username and password."));
			}
			
			// Exception is caught by RequestHandler->handleRequest() and will halt further execution
			$e = new SS_HTTPResponse_Exception(null, 401);
			$e->setResponse($response);
			throw $e;
		}
		
		if($permissionCode && !Permission::checkMember($member->ID, $permissionCode)) {
			$response = new SS_HTTPResponse(null, 401);
			$response->addHeader('WWW-Authenticate', "Basic realm=\"$realm\"");

			if(isset($_SERVER['PHP_AUTH_USER'])) {
				$response->setBody(_t('BasicAuth.ERRORNOTADMIN', "That user is not an administrator."));
			}
			
			// Exception is caught by RequestHandler->handleRequest() and will halt further execution
			$e = new SS_HTTPResponse_Exception(null, 401);
			$e->setResponse($response);
			throw $e;
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
	 * @param boolean $protect Set this to false to disable protection.
	 * @param String $code {@link Permission} code that is required from the user.
	 *  Defaults to "ADMIN". Set to NULL to just require a valid login, regardless
	 *  of the permission codes a user has.
	 */
	public static function protect_entire_site($protect = true, $code = 'ADMIN', $message = null) {
		Config::inst()->update('BasicAuth', 'entire_site_protected', $protect);
		Config::inst()->update('BasicAuth', 'entire_site_protected_code', $code);
		Config::inst()->update('BasicAuth', 'entire_site_protected_message', $message);
	}
	
	/**
	 * Call {@link BasicAuth::requireLogin()} if {@link BasicAuth::protect_entire_site()} has been called.
	 * This is a helper function used by {@link Controller::init()}.
	 * 
	 * If you want to enabled protection (rather than enforcing it),
	 * please use {@link protect_entire_site()}.
	 */
	public static function protect_site_if_necessary() {
		$config = Config::inst()->forClass('BasicAuth');
		if($config->entire_site_protected) {
			self::requireLogin($config->entire_site_protected_message, $config->entire_site_protected_code, false);
		}
	}

}
