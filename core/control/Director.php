<?php
/**
 * Director is responsible for processing URLs, and providing environment information.
 * 
 * The most important part of director is {@link Director::direct()}, which is passed a URL and will execute the appropriate
 * controller.
 * 
 * Director also has a number of static methods that provide information about the environment, such as {@link Director::set_environment_type()}.
 *
 * @package sapphire
 * @subpackage control
 * @see Director::direct(),Director::addRules(),Director::set_environment_type()
 */
class Director {
	
	static private $urlSegment;
	
	static private $urlParams;

	static private $rules = array();
	
	static $siteMode;
	
	static $alternateBaseFolder;

	static $alternateBaseURL;
	
	static $dev_servers = array(
		'localhost',
		'127.0.0.1'
	);
	
	static $test_servers = array();
	
	static protected $environment_type;

	/** 
	 * Sets the site mode (if it is the public site or the cms), 
	 * and runs registered modules. 
 	 */ 
	static protected $callbacks;

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
		return Session::get('history.' . intval(sizeof(Session::get('history')) - $pagesBack - 1));
	}


	/**
	 * Add URL matching rules to the Director.
	 * 
	 * The director is responsible for turning URLs into Controller objects.  It does thi
	 * 
	 * @param $priority The priority of the rules; higher values will get your rule checked first.  
	 * We recommend priority 100 for your site's rules.  The built-in rules are priority 10, standard modules are priority 50.
	 */
	static function addRules($priority, $rules) {
		Director::$rules[$priority] = isset(Director::$rules[$priority]) ? array_merge($rules, (array)Director::$rules[$priority]) : $rules;
	}

	/**
	 * Process the given URL, creating the appropriate controller and executing it.
	 * 
	 * Request processing is handled as folows:
	 *  - Director::direct() creates a new HTTPResponse object and passes this to Director::handleRequest().
	 *  - Director::handleRequest($request) checks each of the Director rules and identifies a controller to handle this 
	 *    request.
	 *  - Controller::handleRequest($request) is then called.  This will find a rule to handle the URL, and call the rule
	 *    handling method.
	 *  - RequestHandler::handleRequest($request) is recursively called whenever a rule handling method returns a
	 *    RequestHandler object.
	 *
	 * In addition to request processing, Director will manage the session, and perform the output of the actual response
	 * to the browser.
	 * 
	 * @param $url String, the URL the user is visiting, without the querystring.
	 * @uses handleRequest() rule-lookup logic is handled by this.
	 * @uses Controller::run() Controller::run() handles the page logic for a Director::direct() call.
	 */
	static function direct($url) {
		// Validate $_FILES array before merging it with $_POST
		foreach($_FILES as $k => $v) {
			if(is_array($v['tmp_name'])) {
				foreach($v['tmp_name'] as $tmpFile) {
					if($tmpFile && !is_uploaded_file($tmpFile)) {
						user_error("File upload '$k' doesn't appear to be a valid upload", E_USER_ERROR);
					}
				}
			} else {
				if($v['tmp_name'] && !is_uploaded_file($v['tmp_name'])) {
					user_error("File upload '$k' doesn't appear to be a valid upload", E_USER_ERROR);
				}
			}
		}
		
		$req = new HTTPRequest(
			(isset($_SERVER['X-HTTP-Method-Override'])) ? $_SERVER['X-HTTP-Method-Override'] : $_SERVER['REQUEST_METHOD'],
			$url, 
			$_GET, 
			array_merge((array)$_POST, (array)$_FILES),
			@file_get_contents('php://input')
		);
		
		// @todo find better way to extract HTTP headers
		if(isset($_SERVER['HTTP_ACCEPT'])) $req->addHeader("Accept", $_SERVER['HTTP_ACCEPT']);
		if(isset($_SERVER['CONTENT_TYPE'])) $req->addHeader("Content-Type", $_SERVER['CONTENT_TYPE']);
		if(isset($_SERVER['HTTP_REFERER'])) $req->addHeader("Referer", $_SERVER['HTTP_REFERER']);

		// Load the session into the controller
		$session = new Session($_SESSION);
		$result = Director::handleRequest($req, $session);
		$session->inst_save();

		// Return code for a redirection request
		if(is_string($result) && substr($result,0,9) == 'redirect:') {
			$response = new HTTPResponse();
			$response->redirect(substr($result, 9));
			$response->output();

		// Handle a controller
		} else if($result) {
			if($result instanceof HTTPResponse) {
				$response = $result;
				
			} else {
				$response = new HTTPResponse();
				$response->setBody($result);
			}
			
			// ?debug_memory=1 will output the number of bytes of memory used for this request
			if(isset($_REQUEST['debug_memory']) && $_REQUEST['debug_memory']) {
				echo number_format(memory_get_peak_usage(),0);
			} else {
				$response->output();
			}

			//$controllerObj->getSession()->inst_save();
		}
	}
	
	/**
	 * Test a URL request, returning a response object.
	 * 
	 * This method is the counterpart of Director::direct() that is used in functional testing.  It will execute the URL given,
	 * 
	 * @param string $url The URL to visit
	 * @param array $postVars The $_POST & $_FILES variables
	 * @param Session $session The {@link Session} object representing the current session.  By passing the same object to multiple
	 * calls of Director::test(), you can simulate a peristed session.
	 * @param string $httpMethod The HTTP method, such as GET or POST.  It will default to POST if postVars is set, GET otherwise.
	 *  Overwritten by $postVars['_method'] if present.
	 * @param string $body The HTTP body
	 * @param array $headers HTTP headers with key-value pairs
	 * @return HTTPResponse
	 * 
	 * @uses getControllerForURL() The rule-lookup logic is handled by this.
	 * @uses Controller::run() Controller::run() handles the page logic for a Director::direct() call.
	 */
	static function test($url, $postVars = null, $session = null, $httpMethod = null, $body = null, $headers = null) {
		// These are needed so that calling Director::test() doesnt muck with whoever is calling it.
		// Really, it's some inapproriate coupling and should be resolved by making less use of statics
		$oldStage = Versioned::current_stage();
		$getVars = array();
		
		if(!$httpMethod) $httpMethod = ($postVars || is_array($postVars)) ? "POST" : "GET";
		
		$urlWithQuerystring = $url;
		if(strpos($url, '?') !== false) {
			list($url, $getVarsEncoded) = explode('?', $url, 2);
			parse_str($getVarsEncoded, $getVars);
		}
		
		if(!$session) $session = new Session(null);

		// Back up the current values of the superglobals
		$existingRequestVars = $_REQUEST; 
		$existingGetVars = $_GET; 
		$existingPostVars = $_POST; 
		$existingSessionVars = $_SESSION; 
		$existingCookies = $_COOKIE;
		$existingServer = $_SERVER;
		$existingCookieReportErrors = Cookie::report_errors();
		$existingRequirementsBackend = Requirements::backend();

		Cookie::set_report_errors(false);
		Requirements::set_backend(new Requirements_Backend());

		// Replace the superglobals with appropriate test values
		$_REQUEST = array_merge((array)$getVars, (array)$postVars); 
		$_GET = (array)$getVars; 
		$_POST = (array)$postVars; 
		$_SESSION = $session ? $session->inst_getAll() : array();
		$_COOKIE = array();
		$_SERVER['REQUEST_URI'] = Director::baseURL() . $urlWithQuerystring;

		$req = new HTTPRequest($httpMethod, $url, $getVars, $postVars, $body);
		if($headers) foreach($headers as $k => $v) $req->addHeader($k, $v);
		$result = Director::handleRequest($req, $session);
		
		// Restore the superglobals
		$_REQUEST = $existingRequestVars; 
		$_GET = $existingGetVars; 
		$_POST = $existingPostVars; 
		$_SESSION = $existingSessionVars;   
		$_COOKIE = $existingCookies;
		$_SERVER = $existingServer;

		Cookie::set_report_errors($existingCookieReportErrors); 
		Requirements::set_backend($existingRequirementsBackend);

		// These are needed so that calling Director::test() doesnt muck with whoever is calling it.
		// Really, it's some inapproriate coupling and should be resolved by making less use of statics
		Versioned::reading_stage($oldStage);
		
		return $result;
	}
		
	/**
	 * Handle an HTTP request, defined with a HTTPRequest object.
	 *
	 * @return HTTPResponse|string
	 */
	protected static function handleRequest(HTTPRequest $request, Session $session) {
		krsort(Director::$rules);

		if(isset($_REQUEST['debug'])) Debug::show(Director::$rules);
		foreach(Director::$rules as $priority => $rules) {
			foreach($rules as $pattern => $controllerOptions) {
				if(is_string($controllerOptions)) {
					if(substr($controllerOptions,0,2) == '->') $controllerOptions = array('Redirect' => substr($controllerOptions,2));
					else $controllerOptions = array('Controller' => $controllerOptions);
				}
				
				if(($arguments = $request->match($pattern, true)) !== false) {
					// controllerOptions provide some default arguments
					$arguments = array_merge($controllerOptions, $arguments);
					
					// Find the controller name
					if(isset($arguments['Controller'])) $controller = $arguments['Controller'];
					
					// Pop additional tokens from the tokeniser if necessary
					if(isset($controllerOptions['_PopTokeniser'])) {
						$request->shift($controllerOptions['_PopTokeniser']);
					}

					// Handle redirections
					if(isset($arguments['Redirect'])) {
						return "redirect:" . Director::absoluteURL($arguments['Redirect'], true);

					} else {
						/*
						if(isset($arguments['Action'])) {
							$arguments['Action'] = str_replace('-','',$arguments['Action']);
						}
						
						if(isset($arguments['Action']) && ClassInfo::exists($controller.'_'.$arguments['Action']))
							$controller = $controller.'_'.$arguments['Action'];
						*/

						if(isset($arguments['URLSegment'])) self::$urlSegment = $arguments['URLSegment'] . "/";
						
						Director::$urlParams = $arguments;
						
						$controllerObj = new $controller();
						$controllerObj->setSession($session);

						return $controllerObj->handleRequest($request);
					}
				}
			}
		}
	}

	/**
	 * Returns the urlParam with the given name
	 */
	static function urlParam($name) {
		if(isset(Director::$urlParams[$name])) return Director::$urlParams[$name];
	}
	
	/**
	 * Returns an array of urlParams
	 */
	static function urlParams() {
		return Director::$urlParams;
	}

	/**
	 * Returns the dataobject of the current page.
	 * This will only return a value if you are looking at a SiteTree page
	 */
	static function currentPage() {
		if(isset(Director::$urlParams['URLSegment'])) {
			$SQL_urlSegment = Convert::raw2sql(Director::$urlParams['URLSegment']);
			return SiteTree::get_by_url($SQL_urlSegment);
		} else {
			return Controller::curr();
		}
	}

	/**
	 * Turns the given URL into an absolute URL.
	 * @todo Document how relativeToSiteBase works
	 */
	static function absoluteURL($url, $relativeToSiteBase = false) {
		if(strpos($url,'/') === false && !$relativeToSiteBase) $url = dirname($_SERVER['REQUEST_URI'] . 'x') . '/' . $url;

	 	if(substr($url,0,4) != "http") {
	 		if($url[0] != "/") $url = Director::baseURL()  . $url;
			// Sometimes baseURL() can return a full URL instead of just a path
			if(substr($url,0,4) != "http") $url = self::protocolAndHost() . $url;
		}

		return $url;
	}

	/**
	 * Returns the part of the URL, 'http://www.mysite.com'.
	 * 
	 * @return boolean|string The domain from the PHP environment. Returns FALSE is this environment variable isn't set.
	 */
	static function protocolAndHost() {
		if(self::$alternateBaseURL) {
			if(preg_match('/^(http[^:]*:\/\/[^\/]+)(\/|$)/', self::$alternateBaseURL, $matches)) {
				return $matches[1];
			}
		}

		$s = (isset($_SERVER['SSL']) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) ? 's' : '';
		
		if(isset($_SERVER['HTTP_HOST'])) {
			return "http$s://" . $_SERVER['HTTP_HOST'];
		} else {
			global $_FILE_TO_URL_MAPPING;
			if(Director::is_cli() && isset($_FILE_TO_URL_MAPPING)) $errorSuggestion = '  You probably want to define '.
				'an entry in $_FILE_TO_URL_MAPPING that covers "' . Director::baseFolder() . '"';
			else if(Director::is_cli()) $errorSuggestion = '  You probably want to define $_FILE_TO_URL_MAPPING in '.
				'your _ss_environment.php as instructed on the "sake" page of the doc.silverstripe.com wiki';
			else $errorSuggestion = "";
			
			user_error("Director::protocolAndHost() lacks sufficient information - HTTP_HOST not set.$errorSuggestion", E_USER_WARNING);
			return false;
			
		}
	}


	/**
	 * Redirect to another page.
	 *  - $url can be an absolute URL
	 *  - or it can be a URL relative to the "site base"
	 *  - if it is just a word without an slashes, then it redirects to another action on the current controller.
	 */
	static function redirect($url, $code=302) {
		Controller::curr()->redirect($url, $code);
	}

	/**
	 * Tests whether a redirection has been requested.
	 * @return string If redirect() has been called, it will return the URL redirected to.  Otherwise, it will return null;
	 */
	static function redirected_to() {
		return Controller::curr()->redirectedTo();
	}
	
	/**
	 * Sets the HTTP status code
	 */
	static function set_status_code($code) {
		return Controller::curr()->getResponse()->setStatusCode($code);
	}
	
	/**
	 * Returns the current HTTP status code
	 */
	static function get_status_code() {
		return Controller::curr()->getResponse()->getStatusCode();
	}

	/**
	 * @deprecated 2.3 Use Controller->redirectBack()
	 */
	static function redirectBack() {
		Controller::curr()->redirectBack();
	}

	/**
	 * Returns a URL to composed of the given segments - usually controller, action, parameter
	 * @deprecated 2.3 Use Controller::join_links()
	 */
	static function link() {
		$parts = func_get_args();
		return Director::baseURL() . implode("/",$parts) . (sizeof($parts) > 2 ? "" : "/");
	}

	/**
	 * Returns the root URL for the site.
	 * It will be automatically calculated unless it is overridden with {@link setBaseURL()}.
	 */
	static function baseURL() {
		if(self::$alternateBaseURL) return self::$alternateBaseURL;
		else {
			$base = dirname(dirname($_SERVER['SCRIPT_NAME']));
			if($base == '/' || $base == '/.' || $base == '\\') $baseURL = '/';
			else $baseURL = $base . '/';
			
			if(defined('BASE_SCRIPT_URL')) return $baseURL . BASE_SCRIPT_URL;
			else return $baseURL;
		}
	}
	
	/**
	 * Sets the root URL for the website.
	 * If the site isn't accessible from the URL you provide, weird things will happen.
	 */
	static function setBaseURL($baseURL) {
		self::$alternateBaseURL = $baseURL;
	}

	/**
	 * Returns the root filesystem folder for the site.
	 * It will be automatically calculated unless it is overridden with {@link setBaseFolder()}.
	 */
	static function baseFolder() {
		if(self::$alternateBaseFolder) return self::$alternateBaseFolder;
		else return dirname(dirname($_SERVER['SCRIPT_FILENAME']));
	}

	/**
	 * Sets the root folder for the website.
	 * If the site isn't accessible from the folder you provide, weird things will happen.
	 */
	static function setBaseFolder($baseFolder) {
		self::$alternateBaseFolder = $baseFolder;
	}

	/**
	 * Turns an absolute URL or folder into one that's relative to the root of the site.
	 * This is useful when turning a URL into a filesystem reference, or vice versa.
	 * 
	 * @todo Implement checking across http/https protocols
	 * 
	 * @param string $url Accepts both a URL or a filesystem path
	 * @return string Either a relative URL if the checks succeeded, or the original (possibly absolute) URL.
	 */
	static function makeRelative($url) {
		// Allow for the accidental inclusion of a // in the URL
		$url = ereg_replace('([^:])//','\\1/',$url);
		$url = trim($url);

		// Only bother comparing the URL to the absolute version if $url looks like a URL.
		if(preg_match('/^https?[^:]*:\/\//',$url)) {
			$base1 = self::absoluteBaseURL();
			if(substr($url,0,strlen($base1)) == $base1) return substr($url,strlen($base1));
		}
		
		// test for base folder, e.g. /var/www
		$base2 = self::baseFolder();
		if(substr($url,0,strlen($base2)) == $base2) return substr($url,strlen($base2));

		// Test for relative base url, e.g. mywebsite/ if the full URL is http://localhost/mywebsite/
		$base3 = self::baseURL();
		if(substr($url,0,strlen($base3)) == $base3) return substr($url,strlen($base3));
		
		// Nothing matched, fall back to returning the original URL
		return $url;
	}
	
	/**
	 * Returns true if a given path is absolute. Works under both *nix and windows
	 * systems
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function is_absolute($path) {
		if($path[0] == '/' || $path[0] == '\\') return true;
		return preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) == 1;
	}
	
	/**
	 * Checks if a given URL is absolute (e.g. starts with 'http://' etc.).
	 * 
	 * @param string $url
	 * @return boolean
	 */
	public static function is_absolute_url($url) {
		$url = trim($url);
		// remove all query strings to avoid parse_url choking on URLs like 'test.com?url=http://test.com'
		$url = preg_replace('/(.*)\?.*/', '$1', $url);
		$parsed = parse_url($url);
		return (isset($parsed['scheme']));
	}
	
	/**
	 * Checks if a given URL is relative by checking
	 * {@link is_absolute_url()}.
	 * 
	 * @param string $url
	 * @return boolean
	 */
	public static function is_relative_url($url) {
		return (!Director::is_absolute_url($url));
	}
	
	/**
	 * Checks if the given URL is belonging to this "site",
	 * as defined by {@link makeRelative()} and {@link absoluteBaseUrl()}.
	 * Useful to check before redirecting based on a URL from user submissions
	 * through $_GET or $_POST, and avoid phishing attacks by redirecting
	 * to an attackers server.
	 * 
	 * @param string $url
	 * @return boolean
	 */
	public static function is_site_url($url) {
		$relativeUrl = Director::makeRelative($url);
		return (bool)self::is_relative_url($relativeUrl);
	}
	
	/**
	 * Given a filesystem reference relative to the site root, return the full file-system path.
	 * 
	 * @param string $file
	 * @return string
	 */
	public static function getAbsFile($file) {
		return self::is_absolute($file) ? $file : Director::baseFolder() . '/' . $file;
	}
	
	/**
	 * Returns true if the given file exists.
	 * @param $file Filename specified relative to the site root
	 */
	static function fileExists($file) {
		// replace any appended query-strings, e.g. /path/to/foo.php?bar=1 to /path/to/foo.php
		$file = preg_replace('/([^\?]*)?.*/','$1',$file);
		return file_exists(Director::getAbsFile($file));
	}

	/**
	 * Returns the Absolute URL of the site root.
	 */
	 static function absoluteBaseURL() {
	 	return Director::absoluteURL(Director::baseURL());
	 }
	 
	/**
	 * Returns the Absolute URL of the site root, embedding the current basic-auth credentials into the URL.
	 */
	 static function absoluteBaseURLWithAuth() {
		$s = "";
		$login = "";
		
	 	if(isset($_SERVER['PHP_AUTH_USER'])) $login = "$_SERVER[PHP_AUTH_USER]:$_SERVER[PHP_AUTH_PW]@";
	 	if(isset($_SERVER['SSL']) && $_SERVER['SSL'] != 'Off') $s = "s";
		
	 	return "http$s://" . $login .  $_SERVER['HTTP_HOST'] . Director::baseURL();
	 }

	/**
	 * Force the site to run on SSL.  To use, call from _config.php.
	 * 
	 * For example:
	 * <code>
	 * if(Director::isLive()) Director::forceSSL();
	 * </code>
	 */
	static function forceSSL() {
		if((!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') && !Director::isDev()) {
			$destURL = str_replace('http:', 'https:', Director::absoluteURL($_SERVER['REQUEST_URI']));

			header("Location: $destURL", true, 301);
			die("<h1>Your browser is not accepting header redirects</h1><p>Please <a href=\"$destURL\">click here</a>");
		}
	}

	/**
	 * Force a redirect to a domain starting with "www."
	 */
	static function forceWWW() {
		if(!Director::isDev() && !Director::isTest() && strpos($_SERVER['SERVER_NAME'], 'www') !== 0) {
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
				$destURL = str_replace('https://', 'https://www.', Director::absoluteURL($_SERVER['REQUEST_URI']));
			} else {
				$destURL = str_replace('http://', 'http://www.', Director::absoluteURL($_SERVER['REQUEST_URI']));
			}

			header("Location: $destURL", true, 301);
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
	
	/**
	 * Returns true if this script is being run from the command line rather than the webserver.
	 * 
	 * @return boolean
	 */
	public static function is_cli() {
		return (!isset($_SERVER['HTTP_HOST']) && preg_match('/install\.php/', $_SERVER['SCRIPT_NAME'])) 
			|| preg_match('/cli-script\.php/', $_SERVER['SCRIPT_NAME']);
	}

	////////////////////////////////////////////////////////////////////////////////////////////
	// Site mode methods
	////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Sets the site mode (if it is the public site or the cms), and runs registered modules.
	 * 
	 * @param string $mode 'site' or 'cms' 
	 */
	static function set_site_mode($mode) {
		Director::$siteMode = $mode;
		
		if(isset(self::$callbacks[$mode])) {
			foreach(self::$callbacks[$mode] as $extension) {
				call_user_func($extension);
			}
		}
	}
	
	/**
	 * @return string 'site' or 'cms'
	 */
	static function get_site_mode() {
		return Director::$siteMode;
	}

	/**
	 * Allows a module to register with the director to be run once
	 * the controller is instantiated.  The optional 'mode' parameter
	 * can be either 'site' or 'cms', as those are the two values currently
	 * set by controllers.  The callback function will be run at the
	 * initialization of the relevant controller.
	 * 
	 * @param $function string PHP-function array based on http://php.net/call_user_func
	 * @param $mode string
	 */
	static function add_callback($function, $mode = 'site') {
		self::$callbacks[$mode][] = $function;
	}

	////////////////////////////////////////////////////////////////////////////////////////////
	// Environment type methods
	////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Set the environment type of the current site.
	 *
	 * Typically, a SilverStripe site have a number of environments: 
	 *  - development environments, such a copy on your local machine.
	 *  - test sites, such as the one you show the client before going live.
	 *  - the live site itself.
	 * 
	 * The behaviour of these environments often varies slightly.  For example, development sites may have errors dumped to the screen,
	 * and order confirmation emails might be sent to the developer instead of the client.
	 * 
	 * To help with this, Sapphire support the notion of an environment type.  The environment type can be dev, test, or live.
	 * 
	 * You can set it explicitly with Director::set_environment_tpye().  Or you can use {@link Director::set_dev_servers()} and {@link Director::set_test_servers()}
	 * to set it implicitly, based on the value of $_SERVER['HTTP_HOST'].  If the HTTP_HOST value is one of the servers listed, then
	 * the environment type will be test or dev.  Otherwise, the environment type will be live.
	 *
	 * Dev mode can also be forced by putting ?isDev=1 in your URL, which will ask you to log in and then push the site into dev
	 * mode for the remainder of the session. Putting ?isDev=0 onto the URL can turn it back.
	 * 
	 * Test mode can also be forced by putting ?isTest=1 in your URL, which will ask you to log in and then push the site into test
	 * mode for the remainder of the session. Putting ?isTest=0 onto the URL can turn it back.
	 * 
	 * Generally speaking, these methods will be called from your _config.php file.
	 * 
	 * Once the environment type is set, it can be checked with {@link Director::isDev()}, {@link Director::isTest()}, and
	 * {@link Director::isLive()}.
	 * 
	 * @param $et string The environment type: dev, test, or live.
	 */
	static function set_environment_type($et) {
		if($et != 'dev' && $et != 'test' && $et != 'live') {
			Debug::backtrace();
			user_error("Director::set_environment_type passed '$et'.  It should be passed dev, test, or live", E_USER_WARNING);
		} else {
			self::$environment_type = $et;
		}
	}
	
	/**
	 * Can also be checked with {@link Director::isDev()}, {@link Director::isTest()}, and {@link Director::isLive()}.
	 * 
	 * @return string 'dev', 'test' or 'live'
	 */
	static function get_environment_type() {
		if(Director::isLive()) {
			return 'live';
		} elseif(Director::isTest()) {
			return 'test';
		} elseif(Director::isDev()) {
			return 'dev';
		} else {
			return false;
		}
	}

	/**
	 * Specify HTTP_HOST values that are development environments.
	 * For information about environment types, see {@link Director::set_environment_type()}.
	 * @param $servers array An array of HTTP_HOST values that should be treated as development environments.
	 */
	static function set_dev_servers($servers) {
		Director::$dev_servers = $servers;
	}
	
	/**
	 * Specify HTTP_HOST values that are test environments.
	 * For information about environment types, see {@link Director::set_environment_type()}.
	 * @param $servers array An array of HTTP_HOST values that should be treated as test environments.
	 */
	static function set_test_servers($servers) {
		Director::$test_servers = $servers;
	}

	/*
	 * This function will return true if the site is in a live environment.
	 * For information about environment types, see {@link Director::set_environment_type()}.
	 */
	static function isLive() {
		return !(Director::isDev() || Director::isTest());
	}
	
	/**
	 * This function will return true if the site is in a development environment.
	 * For information about environment types, see {@link Director::set_environment_type()}.
	 */
	static function isDev() {
		// This variable is used to supress repetitions of the isDev security message below.
		static $firstTimeCheckingGetVar = true;
		
		// Use ?isDev=1 to get development access on the live server
		if(isset($_GET['isDev'])) {
			if(Security::database_is_ready()) {
				BasicAuth::requireLogin("SilverStripe developer access.  Use your CMS login", "ADMIN");
				$_SESSION['isDev'] = $_GET['isDev'];
			} else {
				if($firstTimeCheckingGetVar && DB::connection_attempted()) {
	 				echo "<p style=\"padding: 3px; margin: 3px; background-color: orange; 
						color: white; font-weight: bold\">Sorry, you can't use ?isDev=1 until your
						Member and Group tables database are available.  Perhaps your database
						connection is failing?</p>";
					$firstTimeCheckingGetVar = false;
				}
			}
		}

		if(isset($_SESSION['isDev']) && $_SESSION['isDev']) return true;

		if(self::$environment_type) return self::$environment_type == 'dev';
		
		// Check if we are running on one of the development servers
		if(isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], Director::$dev_servers))  {
			return true;
		}
		
		return false;
	}
	
	/**
	 * This function will return true if the site is in a test environment.
	 * For information about environment types, see {@link Director::set_environment_type()}.
	 */
	static function isTest() {
		// Use ?isTest=1 to get test access on the live server, or explicitly set your environment
		if(isset($_GET['isTest'])) {
			if(Security::database_is_ready()) {
				BasicAuth::requireLogin("SilverStripe developer access.  Use your CMS login", "ADMIN");
				$_SESSION['isTest'] = $_GET['isTest'];
			} else {
				return true;
			}
		}
		if(self::isDev()) return false;
		
		if(self::$environment_type) {
			return self::$environment_type == 'test';
		}
		
		// Check if we are running on one of the test servers
		if(isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], Director::$test_servers))  {
			return true;
		}
		
		return false;
	}

}
?>