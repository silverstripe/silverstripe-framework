<?php

namespace SilverStripe\Control;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Middleware\HTTPMiddlewareAware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Director is responsible for processing URLs, and providing environment information.
 *
 * The most important part of director is {@link Director::handleRequest()}, which is passed an HTTPRequest and will
 * execute the appropriate controller.
 *
 * @see Director::handleRequest()
 * @see Director::$rules
 * @skipUpgrade
 */
class Director implements TemplateGlobalProvider
{
    use Configurable;
    use Extensible;
    use Injectable;
    use HTTPMiddlewareAware;

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
     * @config
     * @var array
     */
    private static $rules = array();

    /**
     * Set current page
     *
     * @internal
     * @var SiteTree
     */
    private static $current_page;

    /**
     * @config
     * @var string
     */
    private static $alternate_base_folder;

    /**
     * Force the base_url to a specific value.
     * If assigned, default_base_url and the value in the $_SERVER
     * global is ignored.
     * Supports back-ticked vars; E.g. '`SS_BASE_URL`'
     *
     * @config
     * @var string
     */
    private static $alternate_base_url;

    /**
     * Base url to populate if cannot be determined otherwise.
     * Supports back-ticked vars; E.g. '`SS_BASE_URL`'
     *
     * @config
     * @var string
     */
    private static $default_base_url = '`SS_BASE_URL`';

    public function __construct()
    {
    }

    /**
     * Test a URL request, returning a response object. This method is a wrapper around
     * Director::handleRequest() to assist with functional testing. It will execute the URL given, and
     * return the result as an HTTPResponse object.
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
    public static function test(
        $url,
        $postVars = [],
        $session = array(),
        $httpMethod = null,
        $body = null,
        $headers = array(),
        $cookies = array(),
        &$request = null
    ) {
        return static::mockRequest(
            function (HTTPRequest $request) {
                return Director::singleton()->handleRequest($request);
            },
            $url,
            $postVars,
            $session,
            $httpMethod,
            $body,
            $headers,
            $cookies,
            $request
        );
    }

    /**
     * Mock a request, passing this to the given callback, before resetting.
     *
     * @param callable $callback Action to pass the HTTPRequst object
     * @param string $url The URL to build
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
     * @return mixed Result of callback
     */
    public static function mockRequest(
        $callback,
        $url,
        $postVars = [],
        $session = [],
        $httpMethod = null,
        $body = null,
        $headers = [],
        $cookies = [],
        &$request = null
    ) {
        // Build list of cleanup promises
        $finally = [];

        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $kernel->nest();
        $finally[] = function () use ($kernel) {
            $kernel->activate();
        };

        // backup existing vars, and create new vars
        $existingVars = Environment::getVariables();
        $finally[] = function () use ($existingVars) {
            Environment::setVariables($existingVars);
        };
        $newVars = $existingVars;

        // These are needed so that calling Director::test() does not muck with whoever is calling it.
        // Really, it's some inappropriate coupling and should be resolved by making less use of statics.
        if (class_exists(Versioned::class)) {
            $oldReadingMode = Versioned::get_reading_mode();
            $finally[] = function () use ($oldReadingMode) {
                Versioned::set_reading_mode($oldReadingMode);
            };
        }

        // Default httpMethod
        $newVars['_SERVER']['REQUEST_METHOD'] = $httpMethod ?: ($postVars ? "POST" : "GET");
        $newVars['_POST'] = (array)$postVars;

        // Setup session
        if ($session instanceof Session) {
            // Note: If passing $session as object, ensure that changes are written back
            // This is important for classes such as FunctionalTest which emulate cross-request persistence
            $newVars['_SESSION'] = $session->getAll();
            $finally[] = function () use ($session) {
                if (isset($_SESSION)) {
                    foreach ($_SESSION as $key => $value) {
                        $session->set($key, $value);
                    }
                }
            };
        } else {
            $newVars['_SESSION'] = $session ?: [];
        }

        // Setup cookies
        $cookieJar = $cookies instanceof Cookie_Backend
            ? $cookies
            : Injector::inst()->createWithArgs(Cookie_Backend::class, array($cookies ?: []));
        $newVars['_COOKIE'] = $cookieJar->getAll(false);
        Cookie::config()->update('report_errors', false);
        Injector::inst()->registerService($cookieJar, Cookie_Backend::class);

        // Backup requirements
        $existingRequirementsBackend = Requirements::backend();
        Requirements::set_backend(Requirements_Backend::create());
        $finally[] = function () use ($existingRequirementsBackend) {
            Requirements::set_backend($existingRequirementsBackend);
        };

        // Strip any hash
        $url = strtok($url, '#');

        // Handle absolute URLs
        if (parse_url($url, PHP_URL_HOST)) {
            $bits = parse_url($url);

            // If a port is mentioned in the absolute URL, be sure to add that into the HTTP host
            $newVars['_SERVER']['HTTP_HOST'] = isset($bits['port'])
                ? $bits['host'].':'.$bits['port']
                : $bits['host'];
        }

        // Ensure URL is properly made relative.
        // Example: url passed is "/ss31/my-page" (prefixed with BASE_URL), this should be changed to "my-page"
        $url = self::makeRelative($url);
        if (strpos($url, '?') !== false) {
            list($url, $getVarsEncoded) = explode('?', $url, 2);
            parse_str($getVarsEncoded, $newVars['_GET']);
        } else {
            $newVars['_GET'] = [];
        }
        $newVars['_SERVER']['REQUEST_URI'] = Director::baseURL() . ltrim($url, '/');
        $newVars['_REQUEST'] = array_merge($newVars['_GET'], $newVars['_POST']);

        // Normalise vars
        $newVars = HTTPRequestBuilder::cleanEnvironment($newVars);

        // Create new request
        $request = HTTPRequestBuilder::createFromVariables($newVars, $body);
        if ($headers) {
            foreach ($headers as $k => $v) {
                $request->addHeader($k, $v);
            }
        }

        // Apply new vars to environment
        Environment::setVariables($newVars);

        try {
            // Normal request handling
            return call_user_func($callback, $request);
        } finally {
            // Restore state in reverse order to assignment
            foreach (array_reverse($finally) as $callback) {
                call_user_func($callback);
            }
        }
    }

    /**
     * Process the given URL, creating the appropriate controller and executing it.
     *
     * Request processing is handled as follows:
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
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function handleRequest(HTTPRequest $request)
    {
        Injector::inst()->registerService($request, HTTPRequest::class);

        $rules = Director::config()->uninherited('rules');

        $this->extend('updateRules', $rules);

        // Default handler - mo URL rules matched, so return a 404 error.
        $handler = function () {
            return new HTTPResponse('No URL rule was matched', 404);
        };

        foreach ($rules as $pattern => $controllerOptions) {
            // Match pattern
            $arguments = $request->match($pattern, true);
            if ($arguments == false) {
                continue;
            }

            // Normalise route rule
            if (is_string($controllerOptions)) {
                if (substr($controllerOptions, 0, 2) == '->') {
                    $controllerOptions = array('Redirect' => substr($controllerOptions, 2));
                } else {
                    $controllerOptions = array('Controller' => $controllerOptions);
                }
            }
            $request->setRouteParams($controllerOptions);

            // controllerOptions provide some default arguments
            $arguments = array_merge($controllerOptions, $arguments);

            // Pop additional tokens from the tokenizer if necessary
            if (isset($controllerOptions['_PopTokeniser'])) {
                $request->shift($controllerOptions['_PopTokeniser']);
            }

            // Handler for redirection
            if (isset($arguments['Redirect'])) {
                $handler = function () use ($arguments) {
                    // Redirection
                    $response = new HTTPResponse();
                    $response->redirect(static::absoluteURL($arguments['Redirect']));
                    return $response;
                };
                break;
            }

            /** @var RequestHandler $controllerObj */
            $controllerObj = Injector::inst()->create($arguments['Controller']);

            // Handler for calling a controller
            $handler = function (HTTPRequest $request) use ($controllerObj) {
                try {
                    return $controllerObj->handleRequest($request);
                } catch (HTTPResponse_Exception $responseException) {
                    return $responseException->getResponse();
                }
            };
            break;
        }

        // Call the handler with the configured middlewares
        $response = $this->callMiddleware($request, $handler);

        // Note that if a different request was previously registered, this will now be lost
        // In these cases it's better to use Kernel::nest() prior to kicking off a nested request
        Injector::inst()->unregisterNamedObject(HTTPRequest::class);

        return $response;
    }

    /**
     * Return the {@link SiteTree} object that is currently being viewed. If there is no SiteTree
     * object to return, then this will return the current controller.
     *
     * @return SiteTree|Controller
     */
    public static function get_current_page()
    {
        return self::$current_page ? self::$current_page : Controller::curr();
    }

    /**
     * Set the currently active {@link SiteTree} object that is being used to respond to the request.
     *
     * @param SiteTree $page
     */
    public static function set_current_page($page)
    {
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
    public static function absoluteURL($url, $relativeParent = self::BASE)
    {
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
     * A helper to determine the current hostname used to access the site.
     * The following are used to determine the host (in order)
     *  - Director.alternate_base_url (if it contains a domain name)
     *  - Trusted proxy headers
     *  - HTTP Host header
     *  - SS_BASE_URL env var
     *  - SERVER_NAME
     *  - gethostname()
     *
     * @param HTTPRequest $request
     * @return string
     */
    public static function host(HTTPRequest $request = null)
    {
        // Check if overridden by alternate_base_url
        if ($baseURL = self::config()->get('alternate_base_url')) {
            $baseURL = Injector::inst()->convertServiceProperty($baseURL);
            $host = parse_url($baseURL, PHP_URL_HOST);
            if ($host) {
                return $host;
            }
        }

        $request = static::currentRequest($request);
        if ($request && ($host = $request->getHeader('Host'))) {
            return $host;
        }

        // Check given header
        if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        // Check base url
        if ($baseURL = self::config()->uninherited('default_base_url')) {
            $baseURL = Injector::inst()->convertServiceProperty($baseURL);
            $host = parse_url($baseURL, PHP_URL_HOST);
            if ($host) {
                return $host;
            }
        }

        // Fail over to server_name (least reliable)
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : gethostname();
    }

    /**
     * Returns the domain part of the URL 'http://www.mysite.com'. Returns FALSE is this environment
     * variable isn't set.
     *
     * @param HTTPRequest $request
     * @return bool|string
     */
    public static function protocolAndHost(HTTPRequest $request = null)
    {
        return static::protocol($request) . static::host($request);
    }

    /**
     * Return the current protocol that the site is running under.
     *
     * @param HTTPRequest $request
     * @return string
     */
    public static function protocol(HTTPRequest $request = null)
    {
        return (self::is_https($request)) ? 'https://' : 'http://';
    }

    /**
     * Return whether the site is running as under HTTPS.
     *
     * @param HTTPRequest $request
     * @return bool
     */
    public static function is_https(HTTPRequest $request = null)
    {
        // Check override from alternate_base_url
        if ($baseURL = self::config()->uninherited('alternate_base_url')) {
            $baseURL = Injector::inst()->convertServiceProperty($baseURL);
            $protocol = parse_url($baseURL, PHP_URL_SCHEME);
            if ($protocol) {
                return $protocol === 'https';
            }
        }

        // Check the current request
        $request = static::currentRequest($request);
        if ($request && ($scheme = $request->getScheme())) {
            return $scheme === 'https';
        }

        // Check default_base_url
        if ($baseURL = self::config()->uninherited('default_base_url')) {
            $baseURL = Injector::inst()->convertServiceProperty($baseURL);
            $protocol = parse_url($baseURL, PHP_URL_SCHEME);
            if ($protocol) {
                return $protocol === 'https';
            }
        }

        return false;
    }

    /**
     * Return the root-relative url for the baseurl
     *
     * @return string Root-relative url with trailing slash.
     */
    public static function baseURL()
    {
        // Check override base_url
        $alternate = self::config()->get('alternate_base_url');
        if ($alternate) {
            $alternate = Injector::inst()->convertServiceProperty($alternate);
            return rtrim(parse_url($alternate, PHP_URL_PATH), '/') . '/';
        }

        // Get env base url
        $baseURL = rtrim(BASE_URL, '/') . '/';

        // Check if BASE_SCRIPT_URL is defined
        // e.g. `index.php/`
        if (defined('BASE_SCRIPT_URL')) {
            return $baseURL . BASE_SCRIPT_URL;
        }

        return $baseURL;
    }

    /**
     * Returns the root filesystem folder for the site. It will be automatically calculated unless
     * it is overridden with {@link setBaseFolder()}.
     *
     * @return string
     */
    public static function baseFolder()
    {
        $alternate = Director::config()->uninherited('alternate_base_folder');
        return ($alternate) ? $alternate : BASE_PATH;
    }

    /**
     * Turns an absolute URL or folder into one that's relative to the root of the site. This is useful
     * when turning a URL into a filesystem reference, or vice versa.
     *
     * @param string $url Accepts both a URL or a filesystem path.
     *
     * @return string
     */
    public static function makeRelative($url)
    {
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
        if (substr($url, 0, strlen($base2)) == $base2) {
            return substr($url, strlen($base2));
        }

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
    public static function is_absolute($path)
    {
        if (empty($path)) {
            return false;
        }
        if ($path[0] == '/' || $path[0] == '\\') {
            return true;
        }
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
    public static function is_root_relative_url($url)
    {
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
    public static function is_absolute_url($url)
    {
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
                $colonPosition !== false
                && ($slashPosition === false || $colonPosition < $slashPosition)
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
    public static function is_relative_url($url)
    {
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
    public static function is_site_url($url)
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $actualHost = parse_url(self::protocolAndHost(), PHP_URL_HOST);
        if ($urlHost && $actualHost && $urlHost == $actualHost) {
            return true;
        } else {
            return self::is_relative_url($url);
        }
    }

    /**
     * Given a filesystem reference relative to the site root, return the full file-system path.
     *
     * @param string $file
     *
     * @return string
     */
    public static function getAbsFile($file)
    {
        return self::is_absolute($file) ? $file : Director::baseFolder() . '/' . $file;
    }

    /**
     * Returns true if the given file exists. Filename should be relative to the site root.
     *
     * @param $file
     *
     * @return bool
     */
    public static function fileExists($file)
    {
        // replace any appended query-strings, e.g. /path/to/foo.php?bar=1 to /path/to/foo.php
        $file = preg_replace('/([^\?]*)?.*/', '$1', $file);
        return file_exists(Director::getAbsFile($file));
    }

    /**
     * Returns the Absolute URL of the site root.
     *
     * @return string
     */
    public static function absoluteBaseURL()
    {
        return self::absoluteURL(
            self::baseURL(),
            self::ROOT
        );
    }

    /**
     * Returns the Absolute URL of the site root, embedding the current basic-auth credentials into
     * the URL.
     *
     * @param HTTPRequest|null $request
     * @return string
     */
    public static function absoluteBaseURLWithAuth(HTTPRequest $request = null)
    {
        $login = "";

        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $login = "$_SERVER[PHP_AUTH_USER]:$_SERVER[PHP_AUTH_PW]@";
        }

        return Director::protocol($request) . $login .  static::host($request) . Director::baseURL();
    }

    /**
     * Skip any further processing and immediately respond with a redirect to the passed URL.
     *
     * @param string $destURL
     * @throws HTTPResponse_Exception
     */
    protected static function force_redirect($destURL)
    {
        // Redirect to installer
        $response = new HTTPResponse();
        $response->redirect($destURL, 301);
        HTTP::add_cache_headers($response);
        throw new HTTPResponse_Exception($response);
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
     * @return bool true if already on SSL, false if doesn't match patterns (or cannot redirect)
     * @throws HTTPResponse_Exception Throws exception with redirect, if successful
     */
    public static function forceSSL($patterns = null, $secureDomain = null)
    {
        // Already on SSL
        if (static::is_https()) {
            return true;
        }

        // Can't redirect without a url
        if (!isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        if ($patterns) {
            $matched = false;
            $relativeURL = self::makeRelative(Director::absoluteURL($_SERVER['REQUEST_URI']));

            // protect portions of the site based on the pattern
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $relativeURL)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        // if an domain is specified, redirect to that instead of the current domain
        if (!$secureDomain) {
            $secureDomain = static::host();
        }
        $url = 'https://' . $secureDomain . $_SERVER['REQUEST_URI'];

        // Force redirect
        self::force_redirect($url);
        return true;
    }

    /**
     * Force a redirect to a domain starting with "www."
     */
    public static function forceWWW()
    {
        if (!Director::isDev() && !Director::isTest() && strpos(static::host(), 'www') !== 0) {
            $destURL = str_replace(
                Director::protocol(),
                Director::protocol() . 'www.',
                Director::absoluteURL($_SERVER['REQUEST_URI'])
            );

            self::force_redirect($destURL);
        }
    }

    /**
     * Checks if the current HTTP-Request is an "Ajax-Request" by checking for a custom header set by
     * jQuery or whether a manually set request-parameter 'ajax' is present.
     *
     * @param HTTPRequest $request
     * @return bool
     */
    public static function is_ajax(HTTPRequest $request = null)
    {
        $request = self::currentRequest($request);
        if ($request) {
            return $request->isAjax();
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
    public static function is_cli()
    {
        return in_array(php_sapi_name(), ['cli', 'phpdbg']);
    }

    /**
     * Can also be checked with {@link Director::isDev()}, {@link Director::isTest()}, and
     * {@link Director::isLive()}.
     *
     * @return bool
     */
    public static function get_environment_type()
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        return $kernel->getEnvironment();
    }

    /**
     * This function will return true if the site is in a live environment. For information about
     * environment types, see {@link Director::set_environment_type()}.
     *
     * @return bool
     */
    public static function isLive()
    {
        return self::get_environment_type() === 'live';
    }

    /**
     * This function will return true if the site is in a development environment. For information about
     * environment types, see {@link Director::set_environment_type()}.
     *
     * @return bool
     */
    public static function isDev()
    {
        return self::get_environment_type() === 'dev';
    }

    /**
     * This function will return true if the site is in a test environment. For information about
     * environment types, see {@link Director::set_environment_type()}.
     *
     * @return bool
     */
    public static function isTest()
    {
        return self::get_environment_type() === 'test';
    }

    /**
     * Returns an array of strings of the method names of methods on the call that should be exposed
     * as global variables in the templates.
     *
     * @return array
     */
    public static function get_template_global_variables()
    {
        return array(
            'absoluteBaseURL',
            'baseURL',
            'is_ajax',
            'isAjax' => 'is_ajax',
            'BaseHref' => 'absoluteBaseURL',    //@deprecated 3.0
        );
    }

    /**
     * Helper to validate or check the current request object
     *
     * @param HTTPRequest $request
     * @return HTTPRequest Request object if one is both current and valid
     */
    protected static function currentRequest(HTTPRequest $request = null)
    {
        if ($request) {
            return $request;
        }

        // Skip if called during bootstrapping
        if (!InjectorLoader::inst()->hasManifest()) {
            return null;
        }

        // Ensure we only use a registered HTTPRequest and don't
        // incidentally construct a singleton
        if (Injector::inst()->has(HTTPRequest::class)) {
            return Injector::inst()->get(HTTPRequest::class);
        }

        // End case
        return null;
    }
}
