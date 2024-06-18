<?php

namespace SilverStripe\Control;

use BadMethodCallException;
use SilverStripe\Core\Config\Configurable;

/**
 * Handles all manipulation of the session.
 *
 * An instance of a `Session` object can be retrieved via an `HTTPRequest` by calling the `getSession()` method.
 *
 * In order to support things like testing, the session is associated with a particular Controller.  In normal usage,
 * this is loaded from and saved to the regular PHP session, but for things like static-page-generation and
 * unit-testing, you can create multiple Controllers, each with their own session.
 *
 * <b>Saving Data</b>
 *
 * Once you've retrieved a session instance, you can write a value to a users session using the function {@link Session::set()}.
 *
 * <code>
 *  $request->getSession()->set('MyValue', 6);
 * </code>
 *
 * Saves the value of "6" to the MyValue session data. You can also save arrays or serialized objects in session (but
 * note there may be size restrictions as to how much you can save)
 *
 * <code>
 *
 *  $session = $request->getSession();
 *
 *  // save a variable
 *  $var = 1;
 *  $session->set('MyVar', $var);
 *
 *  // saves an array
 *  $session->set('MyArrayOfValues', array('1', '2', '3'));
 *
 *  // saves an object (you'll have to unserialize it back)
 *  $object = new Object();
 *
 *  $session->set('MyObject', serialize($object));
 * </code>
 *
 * <b>Accessing Data</b>
 *
 * Once you have saved a value to the Session you can access it by using the {@link Session::get()} function.
 * Note that session data isn't persisted in PHP's own session store (via $_SESSION)
 * until {@link Session::save()} is called, which happens automatically at the end of a standard request
 * through {@link SilverStripe\Control\Middleware\SessionMiddleware}.
 *
 * The values in the comments are the values stored from the previous example.
 *
 * <code>
 * public function bar() {
 *  $session = $this->getRequest()->getSession();
 *  $value = $session->get('MyValue'); // $value = 6
 *  $var   = $session->get('MyVar'); // $var = 1
 *  $array = $session->get('MyArrayOfValues'); // $array = array(1,2,3)
 *  $object = $session->get('MyObject', unserialize($object)); // $object = Object()
 * }
 * </code>
 *
 * You can also get all the values in the session at once. This is useful for debugging.
 *
 * <code>
 * $session->getAll(); // returns an array of all the session values.
 * </code>
 *
 * <b>Clearing Data</b>
 *
 * Once you have accessed a value from the Session it doesn't automatically wipe the value from the Session, you have
 * to specifically remove it. To clear a value you can either delete 1 session value by the name that you saved it
 *
 * <code>
 * $session->clear('MyValue'); // MyValue is no longer 6.
 * </code>
 *
 * Or you can clear every single value in the session at once. Note SilverStripe stores some of its own session data
 * including form and page comment information. None of this is vital but `clearAll()` will clear everything.
 *
 * <code>
 *  $session->clearAll();
 * </code>
 *
 * @see Cookie
 * @see HTTPRequest
 */
class Session
{
    use Configurable;

    /**
     * Set session timeout in seconds.
     *
     * @var int
     * @config
     */
    private static $timeout = 0;

    /**
     * @config
     * @var array
     */
    private static $session_ips = [];

    /**
     * @config
     * @var string
     */
    private static $cookie_domain;

    /**
     * @config
     * @var string
     */
    private static $cookie_path;

    /**
     * @config
     * @var string
     */
    private static $session_store_path;

    /**
     * @config
     * @var boolean
     */
    private static $cookie_secure = false;

    /**
     * @config
     * @var string
     */
    private static $cookie_name_secure = 'SECSESSID';

    /**
     * Must be "Strict", "Lax", or "None".
     * @config
     */
    private static string $cookie_samesite = Cookie::SAMESITE_LAX;

    /**
     * Name of session cache limiter to use.
     * Defaults to '' to disable cache limiter entirely.
     *
     * @see https://secure.php.net/manual/en/function.session-cache-limiter.php
     * @var string|null
     */
    private static $sessionCacheLimiter = '';

    /**
     * Invalidate the session if user agent header changes between request. Defaults to true. Disabling this checks is
     * not recommended.
     * @var bool
     * @config
     */
    private static $strict_user_agent_check = true;

    /**
     * Session data.
     * Will be null if session has not been started
     *
     * @var array|null
     */
    protected $data = null;

    /**
     * @var bool
     */
    protected $started = false;

    /**
     * List of keys changed. This is a nested array which represents the
     * keys modified in $this->data. The value of each item is either "true"
     * or a nested array.
     *
     * If a value is in changedData but not in data, it must be removed
     * from the destination during save().
     *
     * Only highest level changes are stored. E.g. changes to `Base.Sub`
     * and then `Base` only records `Base` as the change.
     *
     * E.g.
     * [
     *   'Base' => true,
     *   'Key' => [
     *      'Nested' => true,
     *   ],
     * ]
     *
     * @var array
     */
    protected $changedData = [];

    /**
     * Get user agent for this request
     *
     * @param HTTPRequest $request
     * @return string
     */
    protected function userAgent(HTTPRequest $request)
    {
        return $request->getHeader('User-Agent');
    }

    /**
     * Start PHP session, then create a new Session object with the given start data.
     *
     * @param array|null|Session $data Can be an array of data (such as $_SESSION) or another Session object to clone.
     * If null, this session is treated as unstarted.
     */
    public function __construct($data)
    {
        if ($data instanceof Session) {
            $data = $data->getAll();
        }

        $this->data = $data;
        $this->started = isset($data);
    }

    /**
     * Init this session instance before usage,
     * if a session identifier is part of the passed in request.
     * Otherwise, a session might be started in {@link save()}
     * if session data needs to be written with a new session identifier.
     *
     * @param HTTPRequest $request
     */
    public function init(HTTPRequest $request)
    {
        if (!$this->isStarted() && $this->requestContainsSessionId($request)) {
            $this->start($request);
        }

        // Funny business detected!
        if (static::config()->get('strict_user_agent_check') && isset($this->data['HTTP_USER_AGENT'])) {
            if ($this->data['HTTP_USER_AGENT'] !== $this->userAgent($request)) {
                $this->clearAll();
                $this->restart($request);
            }
        }
    }

    /**
     * Destroy existing session and restart
     *
     * @param HTTPRequest $request
     */
    public function restart(HTTPRequest $request)
    {
        $this->destroy(true, $request);
        $this->start($request);
    }

    /**
     * Determine if this session has started
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    public function requestContainsSessionId(HTTPRequest $request)
    {
        $secure = Director::is_https($request) && $this->config()->get('cookie_secure');
        $name = $secure ? $this->config()->get('cookie_name_secure') : session_name();
        return (bool)Cookie::get($name);
    }

    /**
     * Begin session, regardless if a session identifier is present in the request,
     * or whether any session data needs to be written.
     * See {@link init()} if you want to "lazy start" a session.
     *
     * @param HTTPRequest $request The request for which to start a session
     */
    public function start(HTTPRequest $request)
    {
        if ($this->isStarted()) {
            throw new BadMethodCallException("Session has already started");
        }

        $session_path = $this->config()->get('session_store_path');

        // If the session cookie is already set, then the session can be read even if headers_sent() = true
        // This helps with edge-case such as debugging.
        $data = [];
        if (!session_id() && (!headers_sent() || $this->requestContainsSessionId($request))) {
            if (!headers_sent()) {
                $cookieParams = $this->buildCookieParams($request);
                session_set_cookie_params($cookieParams);

                $limiter = $this->config()->get('sessionCacheLimiter');
                if (isset($limiter)) {
                    session_cache_limiter($limiter);
                }

                // Allow storing the session in a non standard location
                if ($session_path) {
                    session_save_path($session_path);
                }

                // If we want a secure cookie for HTTPS, use a separate session name. This lets us have a
                // separate (less secure) session for non-HTTPS requests
                // if headers_sent() is true then it's best to throw the resulting error rather than risk
                // a security hole.
                if ($cookieParams['secure']) {
                    session_name($this->config()->get('cookie_name_secure'));
                }

                session_start();

                // Session start emits a cookie, but only if there's no existing session. If there is a session timeout
                // tied to this request, make sure the session is held for the entire timeout by refreshing the cookie age.
                if ($cookieParams['lifetime'] && $this->requestContainsSessionId($request)) {
                    Cookie::set(
                        session_name(),
                        session_id(),
                        $cookieParams['lifetime'] / 86400,
                        $cookieParams['path'],
                        $cookieParams['domain'],
                        $cookieParams['secure'],
                        true
                    );
                }
            } else {
                // If headers are sent then we can't have a session_cache_limiter otherwise we'll get a warning
                session_cache_limiter(null);
            }

            if (isset($_SESSION)) {
                // Initialise data from session store if present
                $data = $_SESSION;

                // Merge in existing in-memory data, taking priority over session store data
                $this->recursivelyApply((array)$this->data, $data);
            }
        }

        // Save any modified session data back to the session store if present, otherwise initialise it to an array.
        $this->data = $data;

        $this->started = true;
    }

    /**
     * Build the parameters used for setting the session cookie.
     */
    private function buildCookieParams(HTTPRequest $request): array
    {
        $timeout = $this->config()->get('timeout');
        $path = $this->config()->get('cookie_path');
        $domain = $this->config()->get('cookie_domain');
        if (!$path) {
            $path = Director::baseURL();
        }

        // Director::baseURL can return absolute domain names - this extracts the relevant parts
        // for the session otherwise we can get broken session cookies
        if (Director::is_absolute_url($path)) {
            $urlParts = parse_url($path ?? '');
            $path = $urlParts['path'];
            if (!$domain) {
                $domain = $urlParts['host'];
            }
        }

        $sameSite = static::config()->get('cookie_samesite') ?? Cookie::SAMESITE_LAX;
        Cookie::validateSameSite($sameSite);
        $secure = $this->isCookieSecure($sameSite, Director::is_https($request));

        return [
            'lifetime' => $timeout ?: 0,
            'path' => $path,
            'domain' => $domain ?: null,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ];
    }

    /**
     * Determines what the value for the `secure` cookie attribute should be.
     */
    private function isCookieSecure(string $sameSite, bool $isHttps): bool
    {
        if ($sameSite === 'None') {
            return true;
        }
        return $isHttps && $this->config()->get('cookie_secure');
    }

    /**
     * Destroy this session
     *
     * @param bool $removeCookie
     * @param HTTPRequest $request The request for which to destroy a session
     */
    public function destroy($removeCookie = true, HTTPRequest $request = null)
    {
        if (session_id()) {
            if ($removeCookie) {
                if (!$request) {
                    $request = Controller::curr()->getRequest();
                }
                $path = $this->config()->get('cookie_path') ?: Director::baseURL();
                $domain = $this->config()->get('cookie_domain');
                $secure = Director::is_https($request) && $this->config()->get('cookie_secure');
                Cookie::force_expiry(session_name(), $path, $domain, $secure, true);
            }
            session_destroy();
        }
        // Clean up the superglobal - session_destroy does not do it.
        // http://nz1.php.net/manual/en/function.session-destroy.php
        unset($_SESSION);
        $this->data = null;
        $this->started = false;
    }

    /**
     * Set session value
     *
     * @param string $name
     * @param mixed $val
     * @return $this
     */
    public function set($name, $val)
    {
        $var = &$this->nestedValueRef($name, $this->data);

        // Mark changed
        if ($var !== $val) {
            $var = $val;
            $this->markChanged($name);
        }
        return $this;
    }

    /**
     * Mark key as changed
     *
     * @internal
     * @param string $name
     */
    protected function markChanged($name)
    {
        $diffVar = &$this->changedData;
        foreach (explode('.', $name ?? '') as $namePart) {
            if (!isset($diffVar[$namePart])) {
                $diffVar[$namePart] = [];
            }
            $diffVar = &$diffVar[$namePart];

            // Already diffed
            if ($diffVar === true) {
                return;
            }
        }
        // Mark changed
        $diffVar = true;
    }

    /**
     * Merge value with array
     *
     * @param string $name
     * @param mixed $val
     */
    public function addToArray($name, $val)
    {
        $names = explode('.', $name ?? '');

        // We still want to do this even if we have strict path checking for legacy code
        $var = &$this->data;
        $diffVar = &$this->changedData;

        foreach ($names as $n) {
            $var = &$var[$n];
            $diffVar = &$diffVar[$n];
        }

        $var[] = $val;
        $diffVar[sizeof($var) - 1] = $val;
    }

    /**
     * Get session value
     *
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->nestedValue($name, $this->data);
    }

    /**
     * Clear session value
     *
     * @param string $name
     * @return $this
     */
    public function clear($name)
    {
        // Get var by path
        $var = $this->nestedValue($name, $this->data);

        // Unset var
        if ($var !== null) {
            // Unset parent key
            $parentParts = explode('.', $name ?? '');
            $basePart = array_pop($parentParts);
            if ($parentParts) {
                $parent = &$this->nestedValueRef(implode('.', $parentParts), $this->data);
                unset($parent[$basePart]);
            } else {
                unset($this->data[$name]);
            }
            $this->markChanged($name);
        }
        return $this;
    }

    /**
     * Clear all values
     */
    public function clearAll()
    {
        if ($this->data && is_array($this->data)) {
            foreach (array_keys($this->data ?? []) as $key) {
                $this->clear($key);
            }
        }
    }

    /**
     * Get all values
     *
     * @return array|null
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * Set user agent key
     *
     * @param HTTPRequest $request
     */
    public function finalize(HTTPRequest $request)
    {
        $this->set('HTTP_USER_AGENT', $this->userAgent($request));
    }

    /**
     * Save data to session
     * Only save the changes, so that anyone manipulating $_SESSION directly doesn't get burned.
     *
     * @param HTTPRequest $request
     */
    public function save(HTTPRequest $request)
    {
        if ($this->changedData) {
            $this->finalize($request);

            if (!$this->isStarted()) {
                $this->start($request);
            }

            // Apply all changes recursively, implicitly writing them to the actual PHP session store.
            $this->recursivelyApplyChanges($this->changedData, $this->data, $_SESSION);
        }
    }

    /**
     * Recursively apply the changes represented in $data to $dest.
     * Used to update $_SESSION
     *
     * @param array $data
     * @param array $dest
     */
    protected function recursivelyApply($data, &$dest)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                if (!isset($dest[$k]) || !is_array($dest[$k])) {
                    $dest[$k] = [];
                }
                $this->recursivelyApply($v, $dest[$k]);
            } else {
                $dest[$k] = $v;
            }
        }
    }

    /**
     * Returns the list of changed keys
     *
     * @return array
     */
    public function changedData()
    {
        return $this->changedData;
    }

    /**
     * Navigate to nested value in source array by name,
     * creating a null placeholder if it doesn't exist.
     *
     * @internal
     * @param string $name
     * @param array $source
     * @return mixed Reference to value in $source
     */
    protected function &nestedValueRef($name, &$source)
    {
        // Find var to change
        $var = &$source;
        foreach (explode('.', $name ?? '') as $namePart) {
            if (!isset($var)) {
                $var = [];
            }
            if (!isset($var[$namePart])) {
                $var[$namePart] = null;
            }
            $var = &$var[$namePart];
        }
        return $var;
    }

    /**
     * Navigate to nested value in source array by name,
     * returning null if it doesn't exist.
     *
     * @internal
     * @param string $name
     * @param array $source
     * @return mixed Value in array in $source
     */
    protected function nestedValue($name, $source)
    {
        // Find var to change
        $var = $source;
        foreach (explode('.', $name ?? '') as $namePart) {
            if (!isset($var[$namePart])) {
                return null;
            }
            $var = $var[$namePart];
        }
        return $var;
    }

    /**
     * Apply all changes using separate keys and data sources and a destination
     *
     * @internal
     * @param array $changes
     * @param array $source
     * @param array $destination
     */
    protected function recursivelyApplyChanges($changes, $source, &$destination)
    {
        $source = $source ?: [];
        foreach ($changes as $key => $changed) {
            if ($changed === true) {
                // Determine if replacement or removal
                if (array_key_exists($key, $source ?? [])) {
                    $destination[$key] = $source[$key];
                } else {
                    unset($destination[$key]);
                }
            } else {
                // Recursively apply
                $destVal = &$this->nestedValueRef($key, $destination);
                $sourceVal = $this->nestedValue($key, $source);
                $this->recursivelyApplyChanges($changed, $sourceVal, $destVal);
            }
        }
    }

    /**
     * Regenerate session id
     *
     * @internal This is for internal use only. Isn't a part of public API.
     */
    public function regenerateSessionId()
    {
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
