<?php

/**
 * Director is responsible for processing the URL
 * Director is the first step in the "execution pipeline".  It parses the URL, matching it to 
 * one of a number of patterns, and determines the controller, action and any argument to be
 * used.  It then runs the controller, which will finally run the viewer and/or perform processing
 * steps.
 */
class Director {
	static private $urlSegment;
	static private $urlParams;
	
	static private $rules = array();
	
	function __construct() {
		if(isset($_GET['debug_profile'])) Profiler::mark("Director", "construct");
		Session::addToArray('history', substr($_SERVER['REQUEST_URI'], strlen(Director::baseURL())));
		if(isset($_GET['debug_profile'])) Profiler::unmark("Director", "construct");
	}
	
	/**
	 * Return a URL from this user's navigation history.
	 * @param pagesBack The number of pages back to go.  The default, 1, returns the previous
	 * page.
	 */
	static function history($pagesBack = 1) {
		return Session::get('history.' . sizeof(Session::get('history')) - $pagesBack - 1);
	}
	
	
	/**
	 * Add new rules
	 */
	static function addRules($priority, $rules) {
		Director::$rules[$priority] = isset(Director::$rules[$priority]) ? array_merge($rules, (array)Director::$rules[$priority]) : $rules;
	}

	/**
	 * Process the given URL, creating the appropriate controller and executing it
	 */ 
	function direct($url) {
		if(isset($_GET['debug_profile'])) Profiler::mark("Director","direct");
		$controllerObj = Director::getControllerForURL($url);
		
		// Load the session into the controller
		$controllerObj->setSession(new Session($_SESSION));

		if(is_string($controllerObj) && substr($controllerObj,0,9) == 'redirect:') {
			Director::redirect(substr($controllerObj, 9));
			
		} else if($controllerObj) {
			$response = $controllerObj->run(array_merge((array)$_GET, (array)$_POST, (array)$_FILES));
			
			// Save the updated session back
			$_SESSION = $controllerObj->getSession()->inst_getAll();
			$response->output();
			
		}
		if(isset($_GET['debug_profile'])) Profiler::unmark("Director","direct");
	}
	
	/**
	 * Test a URL request, returning a response object.
	 * @param $url The URL to visit
	 * @param $post The $_POST & $_FILES variables
	 * @param $session The {@link Session} object representing the current session.
	 */
	function test($url, $post = null, $session = null) {
        $getVars = array();
		if(strpos($url,'?') !== false) {
			list($url, $getVarsEncoded) = explode('?', $url, 2);
            parse_str($getVarsEncoded, $getVars);
		}
		
		$controllerObj = Director::getControllerForURL($url);
		
		// Load the session into the controller
		$controllerObj->setSession($session ? $session : new Session(null));

		if(is_string($controllerObj) && substr($controllerObj,0,9) == 'redirect:') {
			user_error("Redirection not implemented in Director::test", E_USER_ERROR);
			
		} else if($controllerObj) {
			$response = $controllerObj->run( array_merge($getVars, (array)$post) );
			return $response;
		}
	}
		
		
	static function getControllerForURL($url) {
		if(isset($_GET['debug_profile'])) Profiler::mark("Director","getControllerForURL");
		$url = preg_replace( array( '/\/+/','/^\//', '/\/$/'),array('/','',''),$url);
		$urlParts = split('/+', $url);
		
		krsort(Director::$rules);
		
		if(isset($_REQUEST['debug'])) Debug::show(Director::$rules);
		
		foreach(Director::$rules as $priority => $rules) { 
			foreach($rules as $pattern => $controller) {
				$patternParts = explode('/', $pattern);
				$matched = true;
				$arguments = array();
				foreach($patternParts as $i => $part) {
					$part = trim($part);
					if(isset($part[0]) && $part[0] == '$') {
						$arguments[substr($part,1)] = isset($urlParts[$i]) ? $urlParts[$i] : null;
						if($part == '$Controller' && !class_exists($arguments['Controller'])) {
							$matched = false;
							break;
						}

					} else if(!isset($urlParts[$i]) || $urlParts[$i] != $part) {
						$matched = false;
						break;
					}
				}
				if($matched) {

					if(substr($controller,0,2) == '->') {
						if($_REQUEST['debug'] == 1) Debug::message("Redirecting to $controller");
						
						if(isset($_GET['debug_profile'])) Profiler::unmark("Director","getControllerForURL");
						return "redirect:" . Director::absoluteURL(substr($controller,2), true);
							
					} else {
						if(isset($arguments['Controller']) && $controller == "*") {
							$controller = $arguments['Controller'];
						}

						if(isset($_REQUEST['debug'])) Debug::message("Using controller $controller");
						if(isset($arguments['Action'])) {
							$arguments['Action'] = str_replace('-','',$arguments['Action']);
						}
						if(isset($arguments['Action']) && ClassInfo::exists($controller.'_'.$arguments['Action']))
							$controller = $controller.'_'.$arguments['Action'];	
				
						Director::$urlParams = $arguments;
						$controllerObj = new $controller();
						
						$controllerObj->setURLParams($arguments);
						
						if(isset($arguments['URLSegment'])) self::$urlSegment = $arguments['URLSegment'] . "/";
								
						if(isset($_GET['debug_profile'])) Profiler::unmark("Director","getControllerForURL");
						return $controllerObj;
					}
				}
			}
		}
	}

	
	static function urlParam($name) {
		return Director::$urlParams[$name];
	}
	static function urlParams() {
		return Director::$urlParams;
	}
	
	static function currentPage() {
		if(isset(Director::$urlParams['URLSegment'])) {
			$SQL_urlSegment = Convert::raw2sql(Director::$urlParams['URLSegment']);
			return DataObject::get_one("SiteTree", "URLSegment = '$SQL_urlSegment'");
		} else {
			return Controller::currentController();
		}
	}
	
	
	static function absoluteURL($url, $relativeToSiteBase = false) {
		if(strpos($url,'/') === false && !$relativeToSiteBase) $url = dirname($_SERVER['REQUEST_URI'] . 'x') . '/' . $url;
		
	 	if(substr($url,0,4) != "http") {
	 		if($url[0] != "/") $url = Director::baseURL()  . $url;
			$url = self::protocolAndHost() . $url;
		}

		return $url;
	}

	static function protocolAndHost() {
		$s = (isset($_SERVER['SSL']) || isset($_SERVER['HTTPS'])) ? 's' : '';
		return "http$s://" . $_SERVER['HTTP_HOST'];
	}
		
	
	/**
	 * Redirect to another page.
	 *  - $url can be an absolute URL
	 *  - or it can be a URL relative to the "site base"
	 *  - if it is just a word without an slashes, then it redirects to another action on the current controller.
	 */
	static function redirect($url) {
		Controller::curr()->redirect($url);
	}

	/**
	 * Tests whether a redirection has been requested.
	 * @return string If redirect() has been called, it will return the URL redirected to.  Otherwise, it will return null;
	 */
	static function redirected_to() {
		Controller::curr()->redirectedTo();
	}

	/**
	 * Uses either the HTTP_REFERER or a manually set request-variable called _REDIRECT_BACK_URL.
     * This variable is needed in scenarios where not HTTP-Referer is sent (e.g when calling a page
     * by location.href in IE).
     */
	static function redirectBack() {
		$url = (isset($_REQUEST['_REDIRECT_BACK_URL'])) ? $_REQUEST['_REDIRECT_BACK_URL'] : $_SERVER['HTTP_REFERER']; 
		Director::redirect($url);
	}
	
	static function currentURLSegment() {
		return Director::$urlSegment;
	}
	
	/**
	 * Returns a URL to composed of the given segments - usually controller, action, parameter
	 */
	static function link() {
		$parts = func_get_args();
		return Director::baseURL() . implode("/",$parts) . (sizeof($parts) > 2 ? "" : "/");
	}
	
	/**
	 * Returns a URL for the given controller
	 */
	static $alternateBaseURL;
	static function baseURL() {
		if(self::$alternateBaseURL) return self::$alternateBaseURL;
		else {
			$base = dirname(dirname($_SERVER['SCRIPT_NAME']));
			if($base == '/' || $base == '\\') return '/';
			else return $base . '/';
		}
	}
	static function setBaseURL($baseURL) {
		self::$alternateBaseURL = $baseURL;
	}
	
	static $alternateBaseFolder;
	static function baseFolder() {
		if(self::$alternateBaseFolder) return self::$alternateBaseFolder;
		else return dirname(dirname($_SERVER['SCRIPT_FILENAME']));
	}
	static function setBaseFolder($baseFolder) {
		self::$alternateBaseFolder = $baseFolder;
	}
	
	static function makeRelative($url) {
		$base1 = self::absoluteBaseURL();
		$base2 = self::baseFolder();
		
		// Allow for the accidental inclusion of a // in the URL
		$url = ereg_replace('([^:])//','\\1/',$url);
		
		if(substr($url,0,strlen($base1)) == $base1) return substr($url,strlen($base1));
		if(substr($url,0,strlen($base2)) == $base2) return substr($url,strlen($base2));
		return $url;
	}

	static function getAbsURL($url) {
		return Director::baseURL() . '/' . $url;
	}
	static function getAbsFile($file) {
		if($file[0] == '/') return $file;
		return Director::baseFolder() . '/' . $file;
	}
	static function fileExists($file) {
		return file_exists(Director::getAbsFile($file));
	}
	
	/**
	 * Returns the Absolute URL for the given controller
	 */
	 static function absoluteBaseURL() {
	 	return Director::absoluteURL(Director::baseURL());
	 }
	 static function absoluteBaseURLWithAuth() {
	 	if($_SERVER['PHP_AUTH_USER'])
			$login = "$_SERVER[PHP_AUTH_USER]:$_SERVER[PHP_AUTH_PW]@";
	 	
	 	if($_SERVER['SSL']) $s = "s";
	 	return "http$s://" . $login .  $_SERVER['HTTP_HOST'] . Director::baseURL();
	 }
	 
	/**
	 * Force the site to run on SSL.  To use, call from _config.php
	 */
	static function forceSSL() {
		if(!isset($_SERVER['HTTPS']) && !Director::isDev()){
			$destURL = str_replace('http:','https:',Director::absoluteURL($_SERVER['REQUEST_URI']));
		
			header("Location: $destURL");
			die("<h1>Your browser is not accepting header redirects</h1><p>Please <a href=\"$destURL\">click here</a>");
		}
	}
	
	/**
	 * Force a redirect to www.domain
	 */
	static function forceWWW() {
		if(!Director::isDev() && !Director::isTest() && strpos( $_SERVER['SERVER_NAME'], 'www') !== 0 ){
			if( $_SERVER['HTTPS'] )
				$destURL = str_replace('https://','https://www.',Director::absoluteURL($_SERVER['REQUEST_URI']));
			else
				$destURL = str_replace('http://','http://www.',Director::absoluteURL($_SERVER['REQUEST_URI']));
				
			header("Location: $destURL");
			die("<h1>Your browser is not accepting header redirects</h1><p>Please <a href=\"$destURL\">click here</a>");
		}
	}
	
	/**
	 * Checks if the current HTTP-Request is an "Ajax-Request"
	 * by checking for a custom header set by prototype.js or
	 * wether a manually set request-parameter 'ajax' is present.
	 * 
	 * @return boolean
	 */
	static function is_ajax() {
		if(Controller::has_curr()) {
			return Controller::curr()->isAjax();
		} else {
			return (
				isset($_REQUEST['ajax']) ||
				(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest")
			);
		}
	}
			 
	 
	 
	static $siteMode;
	static function set_site_mode($mode) {
		Director::$siteMode = $mode;
	}
	static function get_site_mode() {
		return Director::$siteMode;
	}
	
	static $dev_servers = array(
		'localhost',
		'127.0.0.1'
	);
	static function set_dev_servers($servers) {
		Director::$dev_servers = $servers;
	}
	
	static $test_servers = array();
	static function set_test_servers($servers) {
		Director::$test_servers = $servers;
	}
	
	static protected $environment_type;
	
	/**
	 * Force the environment type to be dev, test or live.
	 * This will affect the results of isLive, isDev, and isTest
	 */
	static function set_environment_type($et) {
		if($et != 'dev' && $et != 'test' && $et != 'live') {
			user_error("Director::set_environment_type passed '$et'.  It should be passed dev, test, or live");
		} else {
			self::$environment_type = $et;
		}
	}
	
	static function isLive() {
		return !(Director::isDev() || Director::isTest());
	}
	
	static function isDev() {
		if(self::$environment_type) return self::$environment_type == 'dev';
		
		// Use ?isDev=1 to get development access on the live server
		if(isset($_GET['isDev'])) {
			if(ClassInfo::ready()) {
				BasicAuth::requireLogin("SilverStripe developer access.  Use your  CMS login", "ADMIN");
				$_SESSION['isDev'] = $_GET['isDev'];
			} else {
				return true;
			}
		}

		if(isset($_SESSION['isDev']) && $_SESSION['isDev']) return true;
		
		// Check if we are running on one of the development servers
		if(in_array($_SERVER['SERVER_ADDR'], Director::$dev_servers))  {
			return true;
		}
		
		// Check if we are running on one of the test servers
		if(in_array($_SERVER['SERVER_ADDR'], Director::$test_servers))  {
			return true;
		}
		
		return false;
	}
	
	static function isTest() {
		if(self::$environment_type) {
			return self::$environment_type == 'test';
		}
		
		// Check if we are running on one of the test servers
		if(in_array($_SERVER['SERVER_ADDR'], Director::$test_servers))  {
			return true;
		}
		
		return false;
	}

	/**
	 * @todo These functions are deprecated, let's use isLive isDev and isTest instead.
	 */
	function isDevMode() { return self::isDev(); }
	function isTestMode() { return self::isTest(); }
	function isLiveMode() { return self::isLive(); }

}

?>
