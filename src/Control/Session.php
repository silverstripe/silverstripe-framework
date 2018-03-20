<?php

namespace SilverStripe\Control;

use BadMethodCallException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
 * Once you've retrieved a session instance, you can write a value to a users session using the function
 * {@link Session::set()}.
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
    /**
     * Original session data before modification
     * @var array
     */
    protected $original;

    /**
     * @var SessionInterface
     */
    protected $handler;

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
    public function __construct($data = [])
    {
        if ($data instanceof Session) {
            $data = $data->getAll();
        }

        $this->original = $data ?: [];
    }

    /**
     * @return SessionInterface
     */
    protected function getHandler()
    {
        if (!$this->handler) {
            $this->handler = Injector::inst()->get(SessionInterface::class);

            // If source session data was provided, set it now
            if ($this->original) {
                $this->handler->replace($this->original);
            }
        }

        return $this->handler;
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
        $handler = $this->getHandler();
        if (!$this->isStarted()) {
            $this->start();
        }

        // Funny business detected!
        if ($handler->has('HTTP_USER_AGENT')) {
            if ($handler->get('HTTP_USER_AGENT') !== $this->userAgent($request)) {
                $this->destroy();
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
        return $this->getHandler()->isStarted();
    }

    /**
     * Begin session
     * @return $this
     */
    public function start()
    {
        if ($this->isStarted()) {
            throw new BadMethodCallException("Session has already started");
        }

        $this->getHandler()->start();
        $this->original = $this->getHandler()->all();
        return $this;
    }

    /**
     * Destroy this session
     * @return $this
     */
    public function destroy()
    {
        $this->getHandler()->invalidate();
        return $this;
    }

    /**
     * Set session value
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value)
    {
        $handler = $this->getHandler();
        $pieces = explode('.', $name);
        $key = array_shift($pieces);

        // If the name doesn't include any dots, we can just set the value
        if (empty($pieces)) {
            $handler->set($name, $value);
            return $this;
        }

        // Traverse down existing array (adding placeholders if needed) to set the value
        $existing = $handler->get($key);
        $var =& $existing;
        foreach ($pieces as $namePart) {
            if (!isset($var) || !is_array($var)) {
                $var = [];
            }
            if (!isset($var[$namePart])) {
                $var[$namePart] = null;
            }
            $var =& $var[$namePart];
        }
        $var = $value;
        $handler->set($key, $existing);

        return $this;
    }

    /**
     * Get session value
     *
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        $handler = $this->getHandler();
        $pieces = explode('.', $name);
        $key = array_shift($pieces);

        $value = $handler->get($key);
        foreach ($pieces as $namePart) {
            if (!isset($value) || !is_array($value) || !isset($value[$namePart])) {
                return null;
            }

            $value = $value[$namePart];
        }

        return $value;
    }

    /**
     * Clear session value
     *
     * @param string $name
     * @return $this
     */
    public function clear($name)
    {
        $handler = $this->getHandler();
        $pieces = explode('.', $name);
        $key = array_shift($pieces);

        $existing = $handler->get($key);

        // If the value is scalar, or the name doesn't include any dots, just remove it
        if (!is_array($existing) || empty($pieces)) {
            $this->getHandler()->remove($name);
            return $this;
        }

        // Otherwise we need to traverse down through the array value to find the key to remove
        $var =& $existing;
        $i = 0;
        $length = count($pieces);
        foreach ($pieces as $namePart) {
            // If the key doesn't exist, or the last key didn't point to an array, we can't remove anything
            if (!is_array($var) || !isset($var[$namePart])) {
                return $this;
            }

            $i++;
            if ($i === $length) {
                unset($var[$namePart]);
            } else {
                $var =& $var[$namePart];
            }
        }
        $handler->set($key, $existing);

        return $this;
    }

    /**
     * Clear all values
     * @return $this
     */
    public function clearAll()
    {
        $this->getHandler()->clear();
        return $this;
    }

    /**
     * Get all values
     *
     * @return array
     */
    public function getAll()
    {
        return $this->getHandler()->all();
    }

    /**
     * Set user agent key
     *
     * @param HTTPRequest $request
     * @return $this
     */
    public function finalize(HTTPRequest $request)
    {
        $this->set('HTTP_USER_AGENT', $this->userAgent($request));
        return $this;
    }

    /**
     * Save data to session
     * Only save the changes, so that anyone manipulating $_SESSION directly doesn't get burned.
     *
     * @param HTTPRequest $request
     * @return $this
     */
    public function save(HTTPRequest $request)
    {
        $this->finalize($request);
        $this->getHandler()->save();
        return $this;
    }

    /**
     * Returns the list of changed data
     *
     * @return array
     */
    public function changedData()
    {
        return array_merge(
            $this->recursiveDiff($this->original, $this->getAll()),
            $this->recursiveDiff($this->getAll(), $this->original)
        );
    }

    /**
     * @internal
     * @param $array1
     * @param $array2
     * @return array
     */
    protected function recursiveDiff($array1, $array2)
    {
        $result = [];
        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    $nestedDiff = $this->recursiveDiff($value, $array2[$key]);
                    if (!empty($nestedDiff)) {
                        $result[$key] = $nestedDiff;
                    }
                } else {
                    if ($value != $array2[$key]) {
                        $result[$key] = true;
                    }
                }
            } else {
                $result[$key] = true;
            }
        }

        return $result;
    }
}
