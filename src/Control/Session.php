<?php

namespace SilverStripe\Control;

use BadMethodCallException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Dev\Deprecation;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

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
 * $session->remove('MyValue'); // MyValue is no longer 6.
 * </code>
 *
 * Or you can clear every single value in the session at once. Note SilverStripe stores some of its own session data
 * including form and page comment information. None of this is vital but `clearAll()` will clear everything.
 *
 * <code>
 *  $session->clear();
 * </code>
 *
 * @see Cookie
 * @see HTTPRequest
 */
class Session extends SymfonySession
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
    private static string $cookie_samesite = 'Lax';

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

    public function __construct(SessionStorageInterface $storage = null, AttributeBagInterface $attributes = null, FlashBagInterface $flashes = null, callable $usageReporter = null)
    {


        if (!$storage) {

            $config = $this->config();

            $storage = new NativeSessionStorage($this->buildCookieParams());
        }

        parent::__construct($storage);
    }

    /**
     * Destroy existing session and restart
     *
     * @param HTTPRequest $request
     */
    public function restart(HTTPRequest $request)
    {
        $this->invalidate();
        $this->start();
    }

    /**
     * Build the parameters used for setting the session cookie.
     */
    private function buildCookieParams(): array
    {
        $config = $this->config();
        $timeout = $config->get('timeout');
        $path = $config->get('cookie_path');
        $domain = $config->get('cookie_domain');
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

        $sameSite = $config->get('cookie_samesite');
        Cookie::validateSameSite($sameSite);


        $secure = $this->isCookieSecure($sameSite);

        return [
            'cookie_lifetime' => $timeout ?: 0,
            'cookie_path' => $path,
            'cookie_domain' => $domain ?: null,
            'cookie_secure' => $secure,
            'cookie_httponly' => true,
            'cookie_samesite' => $sameSite,
        ];
    }

    /**
     * Determines what the value for the `secure` cookie attribute should be.
     */
    private function isCookieSecure(string $sameSite): bool
    {
        if ($sameSite === 'None') {
            return true;
        }
        return $this->config()->get('cookie_secure');
    }

    /**
     * Destroy this session
     *
     * @param bool $removeCookie
     * @param HTTPRequest $request The request for which to destroy a session
     */
    public function destroy($removeCookie = true, HTTPRequest $request = null)
    {
        if ($this->isStarted()) {
            if ($removeCookie) {
                if (!$request) {
                    $request = Controller::curr()->getRequest();
                }
                $path = $this->config()->get('cookie_path') ?: Director::baseURL();
                $domain = $this->config()->get('cookie_domain');
                $secure = Director::is_https($request) && $this->config()->get('cookie_secure');
                Cookie::force_expiry(session_name(), $path, $domain, $secure, true);
            }
            $this->invalidate();
        }
    }

    /**
     * Clear all values
     *
     */
    public function clearAll()
    {
        $this->clear();
    }

    /**
     * Get all values
     *
     * @return array|null
     */
    public function getAll()
    {
        return $this->all();
    }

    /**
     * Set user agent key
     *
     * @param HTTPRequest $request
     */
    public function finalize(HTTPRequest $request)
    {
        $this->set('HTTP_USER_AGENT', $request->headers->get('user-agent'));
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
