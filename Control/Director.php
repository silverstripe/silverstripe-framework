<?php

namespace SilverStripe\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataModel;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Director is responsible for processing URLs, and providing environment information.
 *
 * The most important part of director is {@link Director::direct()}, which is passed a URL and will
 * execute the appropriate controller.
 *
 * Director also has a number of static methods that provide information about the environment, such as
 * {@link Director::$environment_type}.
 *
 * @see Director::direct()
 * @see Director::$rules
 * @see Director::$environment_type
 */
class Director implements TemplateGlobalProvider {
	use Configurable;

	/**
	 * Specifies this url is relative to the base.
	 *
	 * @var string
	 */
	const BASE = 'BASE';

	/**
	 * Specifies this url is relative to the site root.
	 *
	 * @var string
	 */
	const ROOT = 'ROOT';

	/**
	 * specifies this url is relative to the current request.
	 *
	 * @var string
	 */
	const REQUEST = 'REQUEST';

	/**
	 * @var array
	 */
	static private $urlParams;

	/**
	 * @var array
	 */
	static private $rules = array();

	/**
	 * @var SiteTree
	 */
	private static $current_page;

	/**
	 * @config
	 *
	 * @var string
	 */
	private static $alternate_base_folder;

	/**
	 * @config
	 *
	 * @var array
	 */
	private static $dev_servers = array();

	/**
	 * @config
	 *
	 * @var array
	 */
	private static $test_servers = array();

	/**
	 * Setting this explicitly specifies the protocol ("http" or "https") used, overriding the normal
	 * behaviour of Director::is_https introspecting it from the request. False values imply default
	 * introspection.
	 *
	 * @config
	 *
	 * @var string
	 */
	private static $alternate_protocol;

	/**
	 * @config
	 *
	 * @var string
	 */
	private static $alternate_base_url;

	/**
	 * @config
	 *
	 * @var string
	 */
	private static $environment_type;

	/**
	 * Process the given URL, creating the appropriate controller and executing it.
	 *
	 * Request processing is handled as follows:
	 * - Director::direct() creates a new HTTPResponse object and passes this to
	 *   Director::handleRequest().
	 * - Director::handleRequest($request) checks each of the Director rules and identifies a controller
	 *   to handle this request.
	 * - Controller::handleRequest($request) is then called.  This will find a rule to handle the URL,
	 *   and call the rule handling method.
	 * - RequestHandler::handleRequest($request) is recursively called whenever a rule handling method
	 *   returns a RequestHandler object.
	 *
	 * In addition to request processing, Director will manage the session, and perform the output of
	 * the actual response to the browser.
	 *
	 * @uses handleRequest() rule-lookup logic is handled by this.
	 * @uses Controller::handleRequest() This handles the page logic for a Director::direct() call.
	 * @param string $url
	 * @param DataModel $model
	 * @throws HTTPResponse_Exception
	 */
	public static function direct($url, DataModel $model) {
		// Validate $_FILES array before merging it with $_POST
		foreach($_FILES as $k => $v) {
			if (is_array($v['tmp_name'])) {
				$v = ArrayLib::array_values_recursive($v['tmp_name']);
				foreach($v as $tmpFile) {
					if ($tmpFile && !is_uploaded_file($tmpFile)) {
						user_error("File upload '$k' doesn't appear to be a valid upload", E_USER_ERROR);
					}
				}
			} else {
				if ($v['tmp_name'] && !is_uploaded_file($v['tmp_name'])) {
					user_error("File upload '$k' doesn't appear to be a valid upload", E_USER_ERROR);
				}
			}
		}

		$req = new HTTPRequest(
			(isset($_SERVER['X-HTTP-Method-Override']))
				? $_SERVER['X-HTTP-Method-Override']
				: $_SERVER['REQUEST_METHOD'],
			$url,
			$_GET,
			ArrayLib::array_merge_recursive((array) $_POST, (array) $_FILES),
			@file_get_contents('php://input')
		);

		$headers = self::extract_request_headers($_SERVER);
		foreach ($headers as $header => $value) {
			$req->addHeader($header, $value);
		}

		// Initiate an empty session - doesn't initialize an actual PHP session until saved (see below)
		$session = Session::create(isset($_SESSION) ? $_SESSION : array());

		// Only resume a session if its not started already, and a session identifier exists
		if (!isset($_SESSION) && Session::request_contains_session_id()) {
			$session->inst_start();
		}

		$output = RequestProcessor::singleton()->preRequest($req, $session, $model);

		if ($output === false) {
			// @TODO Need to NOT proceed with the request in an elegant manner
			throw new HTTPResponse_Exception(_t('Director.INVALID_REQUEST', 'Invalid request'), 400);
		}

		$result = Director::handleRequest($req, $session, $model);

		// Save session data. Note that inst_save() will start/resume the session if required.
		$session->inst_save();

		// Return code for a redirection request
		if (is_string($result) && substr($result, 0, 9) == 'redirect:') {
			$url = substr($result, 9);

			if (Director::is_cli()) {
				// on cli, follow SilverStripe redirects automatically
				Director::direct(
					str_replace(Director::absoluteBaseURL(), '', $url),
					DataModel::inst()
				);
				return;
			} else {
				$response = new HTTPResponse();
				$response->redirect($url);
				$res = RequestProcessor::singleton()->postRequest($req, $response, $model);

				if ($res !== false) {
					$response->output();
				}
			}
		// Handle a controller
		} elseif ($result) {
			if ($result instanceof HTTPResponse) {
				$response = $result;

			} else {
				$response = new HTTPResponse();
				$response->setBody($result);
			}

			$res = RequestProcessor::singleton()->postRequest($req, $response, $model);
			if ($res !== false) {
				$response->output();
			} else {
				// @TODO Proper response here.
				throw new HTTPResponse_Exception("Invalid response");
			}


			//$controllerObj->getSession()->inst_save();
		}
	}

	/**
	 * Test a URL request, returning a response object. This method is the counterpart of
	 * Director::direct() that is used in functional testing. It will execute the URL given, and
	 * return the result as an HTTPResponse object.
	 *
	 * @uses Controller::handleRequest() Handles the page logic for a Director::direct() call.
	 *
	 * @param string $url The URL to visit.
	 * @param array $postVars The $_POST & $_FILES variables.
	 * @param array|Session $session The {@link Session} object representing the current session.
	 * By passing the same object to multiple  calls of Director::test(), you can simulate a persisted
	 * session.
	 * @param string $httpMethod The HTTP method, such as GET or POST.  It will default to POST if
	 * postVars is set, GET otherwise. Overwritten by $postVars['_method'] if present.
	 * @param string $body The HTTP body.
	 * @param array $headers HTTP headers with key-value pairs.
	 * @param array|Cookie_Backend $cookies to populate $_COOKIE.
	 * @param HTTPRequest $request The {@see SS_HTTP_Request} object generated as a part of this request.
	 *
	 * @return HTTPResponse
	 *
	 * @throws HTTPResponse_Exception
	 */
	public static function test($url, $postVars = null, $session = array(), $httpMethod = null, $body = null,
			$headers = array(), $cookies = array(), &$request = null) {

		Config::nest();
		Injector::nest();

		// These are needed so that calling Director::test() does not muck with whoever is calling it.
		// Really, it's some inappropriate coupling and should be resolved by making less use of statics.
		$oldReadingMode = Versioned::get_reading_mode();
		$getVars = array();

		if (!$httpMethod) $httpMethod = ($postVars || is_array($postVars)) ? "POST" : "GET";

		if (!$session) $session = Injector::inst()->create('SilverStripe\\Control\\Session', array());
		$cookieJar = $cookies instanceof Cookie_Backend
			? $cookies
			: Injector::inst()->createWithArgs('SilverStripe\\Control\\Cookie_Backend', array($cookies ?: array()));

		// Back up the current values of the superglobals
		$existingRequestVars = isset($_REQUEST) ? $_REQUEST : array();
		$existingGetVars = isset($_GET) ? $_GET : array();
		$existingPostVars = isset($_POST) ? $_POST : array();
		$existingSessionVars = isset($_SESSION) ? $_SESSION : array();
		$existingCookies = isset($_COOKIE) ? $_COOKIE : array();
		$existingServer	= isset($_SERVER) ? $_SERVER : array();

		$existingRequirementsBackend = Requirements::backend();

		Cookie::config()->update('report_errors', false);
		Requirements::set_backend(Requirements_Backend::create());

		// Set callback to invoke prior to return
		$onCleanup = function() use(
			$existingRequestVars, $existingGetVars, $existingPostVars, $existingSessionVars,
			$existingCookies, $existingServer, $existingRequirementsBackend, $oldReadingMode
		) {
			// Restore the super globals
			$_REQUEST = $existingRequestVars;
			$_GET = $existingGetVars;
			$_POST = $existingPostVars;
			$_SESSION = $existingSessionVars;
			$_COOKIE = $existingCookies;
			$_SERVER = $existingServer;

			Requirements::set_backend($existingRequirementsBackend);

			// These are needed so that calling Director::test() does not muck with whoever is calling it.
			// Really, it's some inappropriate coupling and should be resolved by making less use of statics
			Versioned::set_reading_mode($oldReadingMode);

			Injector::unnest(); // Restore old CookieJar, etc
			Config::unnest();
		};

		if (strpos($url, '#') !== false) {
			$url = substr($url, 0, strpos($url, '#'));
		}

		// Handle absolute URLs
		if (parse_url($url, PHP_URL_HOST)) {
			$bits = parse_url($url);
			// If a port is mentioned in the absolute URL, be sure to add that into the HTTP host
			if (isset($bits['port'])) {
				$_SERVER['HTTP_HOST'] = $bits['host'].':'.$bits['port'];
			} else {
				$_SERVER['HTTP_HOST'] = $bits['host'];
			}
		}

		// Ensure URL is properly made relative.
		// Example: url passed is "/ss31/my-page" (prefixed with BASE_URL), this should be changed to "my-page"
		$url = self::makeRelative($url);

		$urlWithQuerystring = $url;
		if (strpos($url, '?') !== false) {
			list($url, $getVarsEncoded) = explode('?', $url, 2);
			parse_str($getVarsEncoded, $getVars);
		}

		// Replace the super globals with appropriate test values
		$_REQUEST = ArrayLib::array_merge_recursive((array) $getVars, (array) $postVars);
		$_GET = (array) $getVars;
		$_POST = (array) $postVars;
		$_SESSION = $session ? $session->inst_getAll() : array();
		$_COOKIE = $cookieJar->getAll(false);
		Injector::inst()->registerService($cookieJar, 'SilverStripe\\Control\\Cookie_Backend');
		$_SERVER['REQUEST_URI'] = Director::baseURL() . $urlWithQuerystring;

		$request = new HTTPRequest($httpMethod, $url, $getVars, $postVars, $body);
		if ($headers) {
			foreach($headers as $k => $v) {
				$request->addHeader($k, $v);
			}
		}

		// Pre-request filtering
		// @see issue #2517
		$model = DataModel::inst();
		$output = Injector::inst()->get('SilverStripe\\Control\\RequestProcessor')->preRequest($request, $session, $model);
		if ($output === false) {
			$onCleanup();
			throw new HTTPResponse_Exception(_t('Director.INVALID_REQUEST', 'Invalid request'), 400);
		}

		// TODO: Pass in the DataModel
		$result = Director::handleRequest($request, $session, $model);

		// Ensure that the result is an HTTPResponse object
		if (is_string($result)) {
			if (substr($result, 0, 9) == 'redirect:') {
				$response = new HTTPResponse();
				$response->redirect(substr($result, 9));
				$result = $response;
			} else {
				$result = new HTTPResponse($result);
			}
		}

		$output = Injector::inst()->get('SilverStripe\\Control\\RequestProcessor')->postRequest($request, $result, $model);
		if ($output === false) {
			$onCleanup();
			throw new HTTPResponse_Exception("Invalid response");
		}

		// Return valid response
		$onCleanup();
		return $result;
	}

	/**
	 * Handle an HTTP request, defined with a HTTPRequest object.
	 *
	 * @skipUpgrade
	 * @param HTTPRequest $request
	 * @param Session $session
	 * @param DataModel $model
	 * @return HTTPResponse|string
	 */
	protected static function handleRequest(HTTPRequest $request, Session $session, DataModel $model) {
		$rules = Director::config()->get('rules');

		if (isset($_REQUEST['debug'])) {
			Debug::show($rules);
		}

		foreach($rules as $pattern => $controllerOptions) {
			if(is_string($controllerOptions)) {
				if (substr($controllerOptions, 0, 2) == '->') {
					$controllerOptions = array('Redirect' => substr($controllerOptions, 2));
				} else {
					$controllerOptions = array('Controller' => $controllerOptions);
				}
			}

			if (($arguments = $request->match($pattern, true)) !== false) {
				$request->setRouteParams($controllerOptions);
				// controllerOptions provide some default arguments
				$arguments = array_merge($controllerOptions, $arguments);

				// Pop additional tokens from the tokenizer if necessary
				if (isset($controllerOptions['_PopTokeniser'])) {
					$request->shift($controllerOptions['_PopTokeniser']);
				}

				// Handle redirection
				if (isset($arguments['Redirect'])) {
					return "redirect:" . Director::absoluteURL($arguments['Redirect'], true);

				} else {
					// Find the controller name
					$controller = $arguments['Controller'];
					Director::$urlParams = $arguments;
					$controllerObj = Injector::inst()->create($controller);
					$controllerObj->setSession($session);

					try {
						$result = $controllerObj->handleRequest($request, $model);
					} catch(HTTPResponse_Exception $responseException) {
						$result = $responseException->getResponse();
					}
					if (!is_object($result) || $result instanceof HTTPResponse) {
						return $result;
					}

					user_error("Bad result from url " . $request->getURL() . " handled by " .
						get_class($controllerObj)." controller: ".get_class($result), E_USER_WARNING);
				}
			}
		}

		// No URL rules matched, so return a 404 error.
		return new HTTPResponse('No URL rule was matched', 404);
	}

	/**
	 * Set url parameters (should only be called internally by RequestHandler->handleRequest()).
	 *
	 * @param array $params
	 */
	public static function setUrlParams($params) {
		Director::$urlParams = $params;
	}

	/**
	 * Return the {@link SiteTree} object that is currently being viewed. If there is no SiteTree
	 * object to return, then this will return the current controller.
	 *
	 * @return SiteTree|Controller
	 */
	public static function get_current_page() {
		return self::$current_page ? self::$current_page : Controller::curr();
	}

	/**
	 * Set the currently active {@link SiteTree} object that is being used to respond to the request.
	 *
	 * @param SiteTree $page
	 */
	public static function set_current_page($page) {
		self::$current_page = $page;
	}

	/**
	 * Turns the given URL into an absolute URL. By default non-site root relative urls will be
	 * evaluated relative to the current base_url.
	 *
	 * @param string $url URL To transform to absolute.
	 * @param string $relativeParent Method to use for evaluating relative urls.
	 * Either one of BASE (baseurl), ROOT (site root), or REQUEST (requested page).
	 * Defaults to BASE, which is the same behaviour as template url resolution.
	 * Ignored if the url is absolute or site root.
	 *
	 * @return string
	 */
	public static function absoluteURL($url, $relativeParent = self::BASE) {
		if (is_bool($relativeParent)) {
			// Deprecate old boolean second parameter
			Deprecation::notice('5.0', 'Director::absoluteURL takes an explicit parent for relative url');
			$relativeParent = $relativeParent ? self::BASE : self::REQUEST;
		}

		// Check if there is already a protocol given
		if (preg_match('/^http(s?):\/\//', $url)) {
			return $url;
		}

		// Absolute urls without protocol are added
		// E.g. //google.com -> http://google.com
		if (strpos($url, '//') === 0) {
			return self::protocol() . substr($url, 2);
		}

		// Determine method for mapping the parent to this relative url
		if ($relativeParent === self::ROOT || self::is_root_relative_url($url)) {
			// Root relative urls always should be evaluated relative to the root
			$parent = self::protocolAndHost();

		} elseif ($relativeParent === self::REQUEST) {
			// Request relative urls rely on the REQUEST_URI param (old default behaviour)
			if (!isset($_SERVER['REQUEST_URI'])) {
				return false;
			}
			$parent = dirname($_SERVER['REQUEST_URI'] . 'x');

		} else {
			// Default to respecting site base_url
			$parent = self::absoluteBaseURL();
		}

		// Map empty urls to relative slash and join to base
		if (empty($url) || $url === '.' || $url === './') {
			$url = '/';
		}
		return Controller::join_links($parent, $url);

	}

	/**
	 * Returns the domain part of the URL 'http://www.mysite.com'. Returns FALSE is this environment
	 * variable isn't set.
	 *
	 * @return bool|string
	 */
	public static function protocolAndHost() {
		$alternate = Config::inst()->get('SilverStripe\\Control\\Director', 'alternate_base_url');
		if ($alternate) {
			if (preg_match('/^(http[^:]*:\/\/[^\/]+)(\/|$)/', $alternate, $matches)) {
				return $matches[1];
			}
		}

		if (isset($_SERVER['HTTP_HOST'])) {
			return Director::protocol() . $_SERVER['HTTP_HOST'];
		} else {
			global $_FILE_TO_URL_MAPPING;
			if (Director::is_cli() && isset($_FILE_TO_URL_MAPPING)) {
				$errorSuggestion = '  You probably want to define ' .
				'an entry in $_FILE_TO_URL_MAPPING that covers "' . Director::baseFolder() . '"';
			} elseif (Director::is_cli()) {
				$errorSuggestion = '  You probably want to define $_FILE_TO_URL_MAPPING in ' .
				'your _ss_environment.php as instructed on the "sake" page of the doc.silverstripe.com wiki';
			} else {
				$errorSuggestion = "";
			}

			user_error("Director::protocolAndHost() lacks sufficient information - HTTP_HOST not set."
				. $errorSuggestion, E_USER_WARNING);
			return false;

		}
	}

	/**
	 * Return the current protocol that the site is running under.
	 *
	 * @return string
	 */
	public static function protocol() {
		return (self::is_https()) ? 'https://' : 'http://';
	}

	/**
	 * Return whether the site is running as under HTTPS.
	 *
	 * @return bool
	 */
	public static function is_https() {
		// See https://en.wikipedia.org/wiki/List_of_HTTP_header_fields
		// See https://support.microsoft.com/en-us/kb/307347
		$headerOverride = false;
		if (TRUSTED_PROXY) {
			$headers = (defined('SS_TRUSTED_PROXY_PROTOCOL_HEADER')) ? array(SS_TRUSTED_PROXY_PROTOCOL_HEADER) : null;
			if (!$headers) {
				// Backwards compatible defaults
				$headers = array('HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_PROTOCOL', 'HTTP_FRONT_END_HTTPS');
			}
			foreach($headers as $header) {
				$headerCompareVal = ($header === 'HTTP_FRONT_END_HTTPS' ? 'on' : 'https');
				if (!empty($_SERVER[$header]) && strtolower($_SERVER[$header]) == $headerCompareVal) {
					$headerOverride = true;
					break;
				}
			}
		}

		if ($protocol = Config::inst()->get('SilverStripe\\Control\\Director', 'alternate_protocol')) {
			return ($protocol == 'https');
		} elseif ($headerOverride) {
			return true;
		} elseif ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')) {
			return true;
		} elseif (isset($_SERVER['SSL'])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the root URL for the site. It will be automatically calculated unless it is overridden
	 * with {@link setBaseURL()}.
	 *
	 * @return string
	 */
	public static function baseURL() {
		$alternate = Config::inst()->get('SilverStripe\\Control\\Director', 'alternate_base_url');

		if ($alternate) {
			return $alternate;
		} else {
			$base = BASE_URL;

			if ($base == '/' || $base == '/.' || $base == '\\') {
				$baseURL = '/';
			} else {
				$baseURL = $base . '/';
			}

			if (defined('BASE_SCRIPT_URL')) {
				return $baseURL . BASE_SCRIPT_URL;
			}

			return $baseURL;
		}
	}

	/**
	 * Sets the root URL for the website. If the site isn't accessible from the URL you provide,
	 * weird things will happen.
	 *
	 * @deprecated 4.0 Use the "Director.alternate_base_url" config setting instead.
	 *
	 * @param string $baseURL
	 */
	public static function setBaseURL($baseURL) {
		Deprecation::notice('4.0', 'Use the "Director.alternate_base_url" config setting instead');
		Config::inst()->update('SilverStripe\\Control\\Director', 'alternate_base_url', $baseURL);
	}

	/**
	 * Returns the root filesystem folder for the site. It will be automatically calculated unless
	 * it is overridden with {@link setBaseFolder()}.
	 *
	 * @return string
	 */
	public static function baseFolder() {
		$alternate = Config::inst()->get('SilverStripe\\Control\\Director', 'alternate_base_folder');
		return ($alternate) ? $alternate : BASE_PATH;
	}

	/**
	 * Sets the root folder for the website. If the site isn't accessible from the folder you provide,
	 * weird things will happen.
	 *
	 * @deprecated 4.0 Use the "Director.alternate_base_folder" config setting instead.
	 *
	 * @param string $baseFolder
	 */
	public static function setBaseFolder($baseFolder) {
		Deprecation::notice('4.0', 'Use the "Director.alternate_base_folder" config setting instead');
		Config::inst()->update('SilverStripe\\Control\\Director', 'alternate_base_folder', $baseFolder);
	}

	/**
	 * Turns an absolute URL or folder into one that's relative to the root of the site. This is useful
	 * when turning a URL into a filesystem reference, or vice versa.
	 *
	 * @param string $url Accepts both a URL or a filesystem path.
	 *
	 * @return string
	 */
	public static function makeRelative($url) {
		// Allow for the accidental inclusion whitespace and // in the URL
		$url = trim(preg_replace('#([^:])//#', '\\1/', $url));

		$base1 = self::absoluteBaseURL();
		$baseDomain = substr($base1, strlen(self::protocol()));

		// Only bother comparing the URL to the absolute version if $url looks like a URL.
		if (preg_match('/^https?[^:]*:\/\//', $url, $matches)) {
			$urlProtocol = $matches[0];
			$urlWithoutProtocol = substr($url, strlen($urlProtocol));

			// If we are already looking at baseURL, return '' (substr will return false)
			if ($url == $base1) {
				return '';
			} elseif (substr($url, 0, strlen($base1)) == $base1) {
				return substr($url, strlen($base1));
			} elseif (substr($base1, -1) == "/" && $url == substr($base1, 0, -1)) {
				// Convert http://www.mydomain.com/mysitedir to ''
				return "";
			}

			if (substr($urlWithoutProtocol, 0, strlen($baseDomain)) == $baseDomain) {
				return substr($urlWithoutProtocol, strlen($baseDomain));
			}
		}

		// test for base folder, e.g. /var/www
		$base2 = self::baseFolder();
		if (substr($url, 0, strlen($base2)) == $base2) return substr($url, strlen($base2));

		// Test for relative base url, e.g. mywebsite/ if the full URL is http://localhost/mywebsite/
		$base3 = self::baseURL();
		if (substr($url, 0, strlen($base3)) == $base3) {
			return substr($url, strlen($base3));
		}

		// Test for relative base url, e.g mywebsite/ if the full url is localhost/myswebsite
		if (substr($url, 0, strlen($baseDomain)) == $baseDomain) {
			return substr($url, strlen($baseDomain));
		}

		// Nothing matched, fall back to returning the original URL
		return $url;
	}

	/**
	 * Returns true if a given path is absolute. Works under both *nix and windows systems.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public static function is_absolute($path) {
		if (empty($path)) return false;
		if ($path[0] == '/' || $path[0] == '\\') return true;
		return preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) == 1;
	}

	/**
	 * Determine if the url is root relative (i.e. starts with /, but not with //) SilverStripe
	 * considers root relative urls as a subset of relative urls.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_root_relative_url($url) {
		return strpos($url, '/') === 0 && strpos($url, '//') !== 0;
	}

	/**
	 * Checks if a given URL is absolute (e.g. starts with 'http://' etc.). URLs beginning with "//"
	 * are treated as absolute, as browsers take this to mean the same protocol as currently being used.
	 *
	 * Useful to check before redirecting based on a URL from user submissions through $_GET or $_POST,
	 * and avoid phishing attacks by redirecting to an attackers server.
	 *
	 * Note: Can't solely rely on PHP's parse_url() , since it is not intended to work with relative URLs
	 * or for security purposes. filter_var($url, FILTER_VALIDATE_URL) has similar problems.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_absolute_url($url) {
		// Strip off the query and fragment parts of the URL before checking
		if (($queryPosition = strpos($url, '?')) !== false) {
			$url = substr($url, 0, $queryPosition-1);
		}
		if (($hashPosition = strpos($url, '#')) !== false) {
			$url = substr($url, 0, $hashPosition-1);
		}
		$colonPosition = strpos($url, ':');
		$slashPosition = strpos($url, '/');
		return (
			// Base check for existence of a host on a compliant URL
			parse_url($url, PHP_URL_HOST)
			// Check for more than one leading slash without a protocol.
			// While not a RFC compliant absolute URL, it is completed to a valid URL by some browsers,
			// and hence a potential security risk. Single leading slashes are not an issue though.
			|| preg_match('%^\s*/{2,}%', $url)
			|| (
				// If a colon is found, check if it's part of a valid scheme definition
				// (meaning its not preceded by a slash).
				$colonPosition !== FALSE
				&& ($slashPosition === FALSE || $colonPosition < $slashPosition)
			)
		);
	}

	/**
	 * Checks if a given URL is relative (or root relative) by checking {@link is_absolute_url()}.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_relative_url($url) {
		return !static::is_absolute_url($url);
	}

	/**
	 * Checks if the given URL is belonging to this "site" (not an external link). That's the case if
	 * the URL is relative, as defined by {@link is_relative_url()}, or if the host matches
	 * {@link protocolAndHost()}.
	 *
	 * Useful to check before redirecting based on a URL from user submissions through $_GET or $_POST,
	 * and avoid phishing attacks by redirecting to an attackers server.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function is_site_url($url) {
		$urlHost = parse_url($url, PHP_URL_HOST);
		$actualHost = parse_url(self::protocolAndHost(), PHP_URL_HOST);
		if ($urlHost && $actualHost && $urlHost == $actualHost) {
			return true;
		} else {
			return self::is_relative_url($url);
		}
	}

	/**
	 * Takes a $_SERVER data array and extracts HTTP request headers.
	 *
	 * @param array $server
	 *
	 * @return array
	 */
	public static function extract_request_headers(array $server) {
		$headers = array();

		foreach($server as $key => $value) {
			if (substr($key, 0, 5) == 'HTTP_') {
				$key = substr($key, 5);
				$key = strtolower(str_replace('_', ' ', $key));
				$key = str_replace(' ', '-', ucwords($key));
				$headers[$key] = $value;
			}
		}

		if (isset($server['CONTENT_TYPE'])) $headers['Content-Type'] = $server['CONTENT_TYPE'];
		if (isset($server['CONTENT_LENGTH'])) $headers['Content-Length'] = $server['CONTENT_LENGTH'];

		return $headers;
	}

	/**
	 * Given a filesystem reference relative to the site root, return the full file-system path.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public static function getAbsFile($file) {
		return self::is_absolute($file) ? $file : Director::baseFolder() . '/' . $file;
	}

	/**
	 * Returns true if the given file exists. Filename should be relative to the site root.
	 *
	 * @param $file
	 *
	 * @return bool
	 */
	public static function fileExists($file) {
		// replace any appended query-strings, e.g. /path/to/foo.php?bar=1 to /path/to/foo.php
		$file = preg_replace('/([^\?]*)?.*/', '$1', $file);
		return file_exists(Director::getAbsFile($file));
	}

	/**
	 * Returns the Absolute URL of the site root.
	 *
	 * @return string
	 */
	public static function absoluteBaseURL() {
		return self::absoluteURL(
			self::baseURL(),
			self::ROOT
		);
	}

	/**
	 * Returns the Absolute URL of the site root, embedding the current basic-auth credentials into
	 * the URL.
	 *
	 * @return string
	 */
	public static function absoluteBaseURLWithAuth() {
		$login = "";

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$login = "$_SERVER[PHP_AUTH_USER]:$_SERVER[PHP_AUTH_PW]@";
		}

		return Director::protocol() . $login .  $_SERVER['HTTP_HOST'] . Director::baseURL();
	}

	/**
	 * Skip any further processing and immediately respond with a redirect to the passed URL.
	 *
	 * @param string $destURL
	 */
	protected static function force_redirect($destURL) {
		$response = new HTTPResponse();
		$response->redirect($destURL, 301);

		HTTP::add_cache_headers($response);

		// TODO: Use an exception - ATM we can be called from _config.php, before Director#handleRequest's try block
		$response->output();
		die;
	}

	/**
	 * Force the site to run on SSL.
	 *
	 * To use, call from _config.php. For example:
	 * <code>
	 * if (Director::isLive()) Director::forceSSL();
	 * </code>
	 *
	 * If you don't want your entire site to be on SSL, you can pass an array of PCRE regular expression
	 * patterns for matching relative URLs. For example:
	 * <code>
	 * if (Director::isLive()) Director::forceSSL(array('/^admin/', '/^Security/'));
	 * </code>
	 *
	 * If you want certain parts of your site protected under a different domain, you can specify
	 * the domain as an argument:
	 * <code>
	 * if (Director::isLive()) Director::forceSSL(array('/^admin/', '/^Security/'), 'secure.mysite.com');
	 * </code>
	 *
	 * Note that the session data will be lost when moving from HTTP to HTTPS. It is your responsibility
	 * to ensure that this won't cause usability problems.
	 *
	 * CAUTION: This does not respect the site environment mode. You should check this
	 * as per the above examples using Director::isLive() or Director::isTest() for example.
	 *
	 * @param array $patterns Array of regex patterns to match URLs that should be HTTPS.
	 * @param string $secureDomain Secure domain to redirect to. Defaults to the current domain.
	 *
	 * @return bool|string String of URL when unit tests running, boolean FALSE if patterns don't match request URI.
	 */
	public static function forceSSL($patterns = null, $secureDomain = null) {
		// Calling from the command-line?
		if (!isset($_SERVER['REQUEST_URI'])) {
			return false;
		}

		$matched = false;

		if ($patterns) {
			$relativeURL = self::makeRelative(Director::absoluteURL($_SERVER['REQUEST_URI']));

			// protect portions of the site based on the pattern
			foreach($patterns as $pattern) {
				if (preg_match($pattern, $relativeURL)) {
					$matched = true;
					break;
				}
			}
		} else {
			// protect the entire site
			$matched = true;
		}

		if ($matched && !self::is_https()) {

			// if an domain is specified, redirect to that instead of the current domain
			if ($secureDomain) {
				$url = 'https://' . $secureDomain . $_SERVER['REQUEST_URI'];
			} else {
				$url = $_SERVER['REQUEST_URI'];
			}

			$destURL = str_replace('http:', 'https:', Director::absoluteURL($url));

			// This coupling to SapphireTest is necessary to test the destination URL and to not interfere with tests
			if (class_exists('SilverStripe\\Dev\\SapphireTest', false) && SapphireTest::is_running_test()) {
				return $destURL;
			} else {
				self::force_redirect($destURL);
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Force a redirect to a domain starting with "www."
	 */
	public static function forceWWW() {
		if (!Director::isDev() && !Director::isTest() && strpos($_SERVER['HTTP_HOST'], 'www') !== 0) {
			$destURL = str_replace(Director::protocol(), Director::protocol() . 'www.',
				Director::absoluteURL($_SERVER['REQUEST_URI']));

			self::force_redirect($destURL);
		}
	}

	/**
	 * Checks if the current HTTP-Request is an "Ajax-Request" by checking for a custom header set by
	 * jQuery or whether a manually set request-parameter 'ajax' is present.
	 *
	 * @return bool
	 */
	public static function is_ajax() {
		if (Controller::has_curr()) {
			return Controller::curr()->getRequest()->isAjax();
		} else {
			return (
				isset($_REQUEST['ajax']) ||
				(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest")
			);
		}
	}

	/**
	 * Returns true if this script is being run from the command line rather than the web server.
	 *
	 * @return bool
	 */
	public static function is_cli() {
		return (php_sapi_name() == "cli");
	}

	/**
	 * Set the environment type of the current site.
	 *
	 * Typically, a SilverStripe site have a number of environments:
	 *  - Development environments, such a copy on your local machine.
	 *  - Test sites, such as the one you show the client before going live.
	 *  - The live site itself.
	 *
	 * The behaviour of these environments often varies slightly.  For example, development sites may
	 * have errors dumped to the screen, and order confirmation emails might be sent to the developer
	 * instead of the client.
	 *
	 * To help with this, SilverStripe supports the notion of an environment type.  The environment
	 * type can be dev, test, or live.
	 *
	 * You can set it explicitly with {@link Director::set_environment_type()}. Or you can use
	 * {@link Director::$dev_servers} and {@link Director::$test_servers} to set it implicitly, based
	 * on the value of $_SERVER['HTTP_HOST'].  If the HTTP_HOST value is one of the servers listed,
	 * then the environment type will be test or dev.  Otherwise, the environment type will be live.
	 *
	 * Dev mode can also be forced by putting ?isDev=1 in your URL, which will ask you to log in and
	 * then push the site into dev mode for the remainder of the session. Putting ?isDev=0 onto the URL
	 * can turn it back.
	 *
	 * Test mode can also be forced by putting ?isTest=1 in your URL, which will ask you to log in and
	 * then push the site into test mode for the remainder of the session. Putting ?isTest=0 onto the URL
	 * can turn it back.
	 *
	 * Generally speaking, these methods will be called from your _config.php file.
	 *
	 * Once the environment type is set, it can be checked with {@link Director::isDev()},
	 * {@link Director::isTest()}, and {@link Director::isLive()}.
	 *
	 * @deprecated 4.0 Use the "Director.environment_type" config setting instead
	 *
	 * @param $et string
	 */
	public static function set_environment_type($et) {
		if ($et != 'dev' && $et != 'test' && $et != 'live') {
			user_error("Director::set_environment_type passed '$et'.  It should be passed dev, test, or live",
				E_USER_WARNING);
		} else {
			Deprecation::notice('4.0', 'Use the "Director.environment_type" config setting instead');
			Config::inst()->update('SilverStripe\\Control\\Director', 'environment_type', $et);
		}
	}

	/**
	 * Can also be checked with {@link Director::isDev()}, {@link Director::isTest()}, and
	 * {@link Director::isLive()}.
	 *
	 * @return bool|string
	 */
	public static function get_environment_type() {
		if (Director::isLive()) {
			return 'live';
		} elseif (Director::isTest()) {
			return 'test';
		} elseif (Director::isDev()) {
			return 'dev';
		} else {
			return false;
		}
	}

	/**
	 * This function will return true if the site is in a live environment. For information about
	 * environment types, see {@link Director::set_environment_type()}.
	 *
	 * @return bool
	 */
	public static function isLive() {
		return !(Director::isDev() || Director::isTest());
	}

	/**
	 * This function will return true if the site is in a development environment. For information about
	 * environment types, see {@link Director::set_environment_type()}.
	 *
	 * @return bool
	 */
	public static function isDev() {
		// Check session
		if ($env = self::session_environment()) return $env === 'dev';

		// Check config
		if (Config::inst()->get('SilverStripe\\Control\\Director', 'environment_type') === 'dev') return true;

		// Check if we are running on one of the test servers
		$devServers = (array)Config::inst()->get('SilverStripe\\Control\\Director', 'dev_servers');
		if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $devServers))  {
			return true;
		}

		return false;
	}

	/**
	 * This function will return true if the site is in a test environment. For information about
	 * environment types, see {@link Director::set_environment_type()}.
	 *
	 * @return bool
	 */
	public static function isTest() {
		// In case of isDev and isTest both being set, dev has higher priority
		if (self::isDev()) return false;

		// Check saved session
		if ($env = self::session_environment()) return $env === 'test';

		// Check config
		if (Config::inst()->get('SilverStripe\\Control\\Director', 'environment_type') === 'test') return true;

		// Check if we are running on one of the test servers
		$testServers = (array)Config::inst()->get('SilverStripe\\Control\\Director', 'test_servers');
		if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $testServers))  {
			return true;
		}

		return false;
	}

	/**
	 * Check or update any temporary environment specified in the session.
	 *
	 * @return null|string
	 */
	protected static function session_environment() {
		// Set session from querystring
		if (isset($_GET['isDev'])) {
			if (isset($_SESSION)) {
				unset($_SESSION['isTest']); // In case we are changing from test mode
				$_SESSION['isDev'] = $_GET['isDev'];
			}
			return 'dev';
		} elseif (isset($_GET['isTest'])) {
			if (isset($_SESSION)) {
				unset($_SESSION['isDev']); // In case we are changing from dev mode
				$_SESSION['isTest'] = $_GET['isTest'];
			}
			return 'test';
		}
		// Check session
		if (isset($_SESSION['isDev']) && $_SESSION['isDev']) {
			return 'dev';
		} elseif (isset($_SESSION['isTest']) && $_SESSION['isTest']) {
			return 'test';
		} else {
			return null;
		}
	}

	/**
	 * Returns an array of strings of the method names of methods on the call that should be exposed
	 * as global variables in the templates.
	 *
	 * @return array
	 */
	public static function get_template_global_variables() {
		return array(
			'absoluteBaseURL',
			'baseURL',
			'is_ajax',
			'isAjax' => 'is_ajax',
			'BaseHref' => 'absoluteBaseURL',    //@deprecated 3.0
		);
	}
}
