<?php

namespace SilverStripe\Control;

use BadMethodCallException;
use SilverStripe\Core\Config\Configurable;

/**
 * Handles all manipulation of the session.
 *
 * The static methods are used to manipulate the currently active controller's session.
 * The instance methods are used to manipulate a particular session.  There can be more than one of these created.
 *
 * In order to support things like testing, the session is associated with a particular Controller.  In normal usage,
 * this is loaded from and saved to the regular PHP session, but for things like static-page-generation and
 * unit-testing, you can create multiple Controllers, each with their own session.
 *
 * The instance object is basically just a way of manipulating a set of nested maps, and isn't specific to session
 * data.
 *
 * <b>Saving Data</b>
 *
 * You can write a value to a users session from your PHP code using the static function {@link Session::set()}. You
 * can add this line in any function or file you wish to save the value.
 *
 * <code>
 *  Session::set('MyValue', 6);
 * </code>
 *
 * Saves the value of "6" to the MyValue session data. You can also save arrays or serialized objects in session (but
 * note there may be size restrictions as to how much you can save)
 *
 * <code>
 *  // save a variable
 *  $var = 1;
 *  Session::set('MyVar', $var);
 *
 *  // saves an array
 *  Session::set('MyArrayOfValues', array('1', '2', '3'));
 *
 *  // saves an object (you'll have to unserialize it back)
 *  $object = new Object();
 *
 *  Session::set('MyObject', serialize($object));
 * </code>
 *
 * <b>Accessing Data</b>
 *
 * Once you have saved a value to the Session you can access it by using the {@link Session::get()} function.
 * Like the {@link Session::set()} function you can use this anywhere in your PHP files.
 *
 * The values in the comments are the values stored from the previous example.
 *
 * <code>
 * public function bar() {
 *  $value = Session::get('MyValue'); // $value = 6
 *  $var   = Session::get('MyVar'); // $var = 1
 *  $array = Session::get('MyArrayOfValues'); // $array = array(1,2,3)
 *  $object = Session::get('MyObject', unserialize($object)); // $object = Object()
 * }
 * </code>
 *
 * You can also get all the values in the session at once. This is useful for debugging.
 *
 * <code>
 * Session::get_all(); // returns an array of all the session values.
 * </code>
 *
 * <b>Clearing Data</b>
 *
 * Once you have accessed a value from the Session it doesn't automatically wipe the value from the Session, you have
 * to specifically remove it. To clear a value you can either delete 1 session value by the name that you saved it
 *
 * <code>
 * Session::clear('MyValue'); // MyValue is no longer 6.
 * </code>
 *
 * Or you can clear every single value in the session at once. Note SilverStripe stores some of its own session data
 * including form and page comment information. None of this is vital but clear_all will clear everything.
 *
 * <code>
 *  Session::clear_all();
 * </code>
 *
 * @see Cookie
 * @todo This class is currently really basic and could do with a more well-thought-out implementation.
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
    private static $session_ips = array();

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
     * Session data.
     * Will be null if session has not been started
     *
     * @var array|null
     */
    protected $data = null;

    /**
     * @var array
     */
    protected $changedData = array();

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
    }

    /**
     * Init this session instance before usage
     *
     * @param HTTPRequest $request
     */
    public function init(HTTPRequest $request)
    {
        if (!$this->isStarted()) {
            $this->start($request);
        }

        // Funny business detected!
        if (isset($this->data['HTTP_USER_AGENT'])) {
            if ($this->data['HTTP_USER_AGENT'] !== $this->userAgent($request)) {
                $this->clearAll();
                $this->destroy();
                $this->start($request);
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
        $this->destroy();
        $this->init($request);
    }

    /**
     * Determine if this session has started
     *
     * @return bool
     */
    public function isStarted()
    {
        return isset($this->data);
    }

    /**
     * Begin session
     *
     * @param HTTPRequest $request The request for which to start a session
     */
    public function start(HTTPRequest $request)
    {
        if ($this->isStarted()) {
            throw new BadMethodCallException("Session has already started");
        }

        $path = $this->config()->get('cookie_path');
        if (!$path) {
            $path = Director::baseURL();
        }
        $domain = $this->config()->get('cookie_domain');
        $secure = Director::is_https($request) && $this->config()->get('cookie_secure');
        $session_path = $this->config()->get('session_store_path');
        $timeout = $this->config()->get('timeout');

        // Director::baseURL can return absolute domain names - this extracts the relevant parts
        // for the session otherwise we can get broken session cookies
        if (Director::is_absolute_url($path)) {
            $urlParts = parse_url($path);
            $path = $urlParts['path'];
            if (!$domain) {
                $domain = $urlParts['host'];
            }
        }

        if (!session_id() && !headers_sent()) {
            if ($domain) {
                session_set_cookie_params($timeout, $path, $domain, $secure, true);
            } else {
                session_set_cookie_params($timeout, $path, null, $secure, true);
            }

            // Allow storing the session in a non standard location
            if ($session_path) {
                session_save_path($session_path);
            }

            // If we want a secure cookie for HTTPS, use a seperate session name. This lets us have a
            // seperate (less secure) session for non-HTTPS requests
            if ($secure) {
                session_name('SECSESSID');
            }

            session_start();

            $this->data = isset($_SESSION) ? $_SESSION : array();
        } else {
            $this->data = [];
        }

        // Modify the timeout behaviour so it's the *inactive* time before the session expires.
        // By default it's the total session lifetime
        if ($timeout && !headers_sent()) {
            Cookie::set(session_name(), session_id(), $timeout/86400, $path, $domain ? $domain
                : null, $secure, true);
        }
    }

    /**
     * Destroy this session
     *
     * @param bool $removeCookie
     */
    public function destroy($removeCookie = true)
    {
        if (session_id()) {
            if ($removeCookie) {
                $path = $this->config()->get('cookie_path') ?: Director::baseURL();
                $domain = $this->config()->get('cookie_domain');
                $secure = $this->config()->get('cookie_secure');
                Cookie::force_expiry(session_name(), $path, $domain, $secure, true);
            }
            session_destroy();
        }
        // Clean up the superglobal - session_destroy does not do it.
        // http://nz1.php.net/manual/en/function.session-destroy.php
        unset($_SESSION);
        $this->data = null;
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
        if (!$this->isStarted()) {
            throw new BadMethodCallException("Session cannot be modified until it's started");
        }

        // Quicker execution path for "."-free names
        if (strpos($name, '.') === false) {
            $this->data[$name] = $val;
            $this->changedData[$name] = $val;
        } else {
            $names = explode('.', $name);

            // We still want to do this even if we have strict path checking for legacy code
            $var = &$this->data;
            $diffVar = &$this->changedData;

            // Iterate twice over the names - once to see if the value needs to be changed,
            // and secondly to get the changed data value. This is done to solve a problem
            // where iterating over the diff var would create empty arrays, and the value
            // would then not be set, inadvertently clearing session values.
            foreach ($names as $n) {
                $var = &$var[$n];
            }

            if ($var !== $val) {
                foreach ($names as $n) {
                    $diffVar = &$diffVar[$n];
                }

                $var = $val;
                $diffVar = $val;
            }
        }
        return $this;
    }

    /**
     * Merge value with array
     *
     * @param string $name
     * @param mixed $val
     */
    public function addToArray($name, $val)
    {
        if (!$this->isStarted()) {
            throw new BadMethodCallException("Session cannot be modified until it's started");
        }

        $names = explode('.', $name);

        // We still want to do this even if we have strict path checking for legacy code
        $var = &$this->data;
        $diffVar = &$this->changedData;

        foreach ($names as $n) {
            $var = &$var[$n];
            $diffVar = &$diffVar[$n];
        }

        $var[] = $val;
        $diffVar[sizeof($var)-1] = $val;
    }

    /**
     * Get session value
     *
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if (!$this->isStarted()) {
            throw new BadMethodCallException("Session cannot be accessed until it's started");
        }

        // Quicker execution path for "."-free names
        if (strpos($name, '.') === false) {
            if (isset($this->data[$name])) {
                return $this->data[$name];
            }
            return null;
        } else {
            $names = explode('.', $name);

            if (!isset($this->data)) {
                return null;
            }

            $var = $this->data;

            foreach ($names as $n) {
                if (!isset($var[$n])) {
                    return null;
                }
                $var = $var[$n];
            }

            return $var;
        }
    }

    /**
     * Clear session value
     *
     * @param string $name
     * @return $this
     */
    public function clear($name)
    {
        if (!$this->isStarted()) {
            throw new BadMethodCallException("Session cannot be modified until it's started");
        }

        $names = explode('.', $name);

        // We still want to do this even if we have strict path checking for legacy code
        $var = &$this->data;
        $diffVar = &$this->changedData;

        foreach ($names as $n) {
            // don't clear a record that doesn't exist
            if (!isset($var[$n])) {
                return $this;
            }
            $var = &$var[$n];
        }

        // only loop to find data within diffVar if var is proven to exist in the above loop
        foreach ($names as $n) {
            $diffVar = &$diffVar[$n];
        }

        if ($var !== null) {
            $var = null;
            $diffVar = null;
        }
        return $this;
    }

    /**
     * Clear all values
     */
    public function clearAll()
    {
        if (!$this->isStarted()) {
            throw new BadMethodCallException("Session cannot be modified until it's started");
        }

        if ($this->data && is_array($this->data)) {
            foreach (array_keys($this->data) as $key) {
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

            $this->recursivelyApply($this->changedData, $_SESSION);
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
                    $dest[$k] = array();
                }
                $this->recursivelyApply($v, $dest[$k]);
            } else {
                $dest[$k] = $v;
            }
        }
    }

    /**
     * Return the changed data, for debugging purposes.
     *
     * @return array
     */
    public function changedData()
    {
        return $this->changedData;
    }
}
