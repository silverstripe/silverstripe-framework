<?php
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
 * 	Session::set('MyValue', 6);
 * </code>
 * 
 * Saves the value of "6" to the MyValue session data. You can also save arrays or serialized objects in session (but
 * note there may be size restrictions as to how much you can save)
 * 
 * <code>
 * 	// save a variable
 * 	$var = 1;
 * 	Session::set('MyVar', $var);
 * 
 * 	// saves an array
 * 	Session::set('MyArrayOfValues', array('1','2','3'));
 * 
 * 	// saves an object (you'll have to unserialize it back)
 * 	$object = new Object();
 * 
 * 	Session::set('MyObject', serialize($object));
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
 * 	$value = Session::get('MyValue'); // $value = 6
 * 	$var   = Session::get('MyVar'); // $var = 1 
 * 	$array = Session::get('MyArrayOfValues'); // $array = array(1,2,3)
 * 	$object = Session::get('MyObject', unserialize($object)); // $object = Object()
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
 * Session::clear('MyValue'); // myvalue is no longer 6.
 * </code>
 * 
 * Or you can clear every single value in the session at once. Note SilverStripe stores some of its own session data
 * including form and page comment information. None of this is vital but clear_all will clear everything.
 * 
 * <code>
 * 	Session::clear_all();
 * </code>
 * 
 * @see Cookie
 * @todo This class is currently really basic and could do with a more well-thought-out implementation.
 *
 * @package framework
 * @subpackage control
 */

class Session {

	/**
	 * @var $timeout Set session timeout in seconds.
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
	 * Session data
	 */
	protected $data = array();
	
	protected $changedData = array();

	protected function userAgent() {
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			return $_SERVER['HTTP_USER_AGENT'];
		} else {
			return '';
		}
	}

	/**
	 * Start PHP session, then create a new Session object with the given start data.
	 *
	 * @param $data Can be an array of data (such as $_SESSION) or another Session object to clone.
	 */
	public function __construct($data) {
		if($data instanceof Session) $data = $data->inst_getAll();

		$this->data = $data;
		
		if (isset($this->data['HTTP_USER_AGENT'])) {
			if ($this->data['HTTP_USER_AGENT'] != $this->userAgent()) {
				// Funny business detected!
				$this->inst_clearAll();
				
				Session::destroy();
				Session::start();
			}
		}
	}

	/**
	 * Cookie domain, for example 'www.php.net'.
	 * 
	 * To make cookies visible on all subdomains then the domain
	 * must be prefixed with a dot like '.php.net'.
	 *
	 * @deprecated 3.2 Use the "Session.cookie_domain" config setting instead
	 * 
	 * @param string $domain The domain to set
	 */
	public static function set_cookie_domain($domain) {
		Deprecation::notice('3.2', 'Use the "Session.cookie_domain" config setting instead');
		Config::inst()->update('Session', 'cookie_domain', $domain);
	}

	/**
	 * Get the cookie domain.
	 *
	 * @deprecated 3.2 Use the "Session.cookie_domain" config setting instead
	 * 
	 * @return string
	 */
	public static function get_cookie_domain() {
		Deprecation::notice('3.2', 'Use the "Session.cookie_domain" config setting instead');
		return Config::inst()->get('Session', 'cookie_domain');
	}

	/**
	 * Path to set on the domain where the session cookie will work.
	 * Use a single slash ('/') for all paths on the domain.
	 *
	 * @deprecated 3.2 Use the "Session.cookie_path" config setting instead
	 *
	 * @param string $path The path to set
	 */
	public static function set_cookie_path($path) {
		Deprecation::notice('3.2', 'Use the "Session.cookie_path" config setting instead');
		Config::inst()->update('Session', 'cookie_path', $path);
	}

	/**
	 * Get the path on the domain where the session cookie will work.
	 *
	 * @deprecated 3.2 Use the "Session.cookie_path" config setting instead
	 * 
	 * @return string
	 */
	public static function get_cookie_path() {
		Deprecation::notice('3.2', 'Use the "Session.cookie_path" config setting instead');
		if(Config::inst()->get('Session', 'cookie_path')) {
			return Config::inst()->get('Session', 'cookie_path');
		} else {
			return Director::baseURL();
		}
	}

	/**
	 * Secure cookie, tells the browser to only send it over SSL.
	 *
	 * @deprecated 3.2 Use the "Session.cookie_secure" config setting instead
	 * 
	 * @param boolean $secure
	 */
	public static function set_cookie_secure($secure) {
		Deprecation::notice('3.2', 'Use the "Session.cookie_secure" config setting instead');
		Config::inst()->update('Session', 'cookie_secure', (bool)$secure);
	}

	/**
	 * Get if the cookie is secure
	 *
	 * @deprecated 3.2 Use the "Session.cookie_secure" config setting instead
	 * 
	 * @return boolean
	 */
	public static function get_cookie_secure() {
		Deprecation::notice('3.2', 'Use the "Session.cookie_secure" config setting instead');
		return Config::inst()->get('Session', 'cookie_secure');
	}

	/**
	 * Set the session store path
	 * 
	 * @deprecated 3.2 Use the "Session.session_store_path" config setting instead
	 * 
	 * @param string $path Filesystem path to the session store
	 */ 
	public static function set_session_store_path($path) {
		Deprecation::notice('3.2', 'Use the "Session.session_store_path" config setting instead');
		Config::inst()->update('Session', 'session_store_path', $path);
	}
	
	/**
	 * Get the session store path
	 * @return string
	 */
	public static function get_session_store_path() {
		Deprecation::notice('3.2', 'Use the "Session.session_store_path" config setting instead');
		return Config::inst()->get('Session', 'session_store_path');
	}

	/**
	 * Provide an <code>array</code> of rules specifing timeouts for IPv4 address ranges or
	 * individual IPv4 addresses. The key is an IP address or range and the value is the time
	 * until the session expires in seconds. For example:
	 * 
	 * Session::set_timeout_ips(array(
	 * 		'127.0.0.1' => 36000	
	 * ));
	 * 
	 * Any user connecting from 127.0.0.1 (localhost) will have their session expired after 10 hours.
	 *
	 * Session::set_timeout is used to set the timeout value for any users whose address is not in the given IP range.
	 *
	 * @deprecated 3.2 Use the "Session.timeout_ips" config setting instead
	 * 
	 * @param array $session_ips Array of IPv4 rules.
	 */
	public static function set_timeout_ips($ips) {
		Deprecation::notice('3.2', 'Use the "Session.timeout_ips" config setting instead');
		Config::inst()->update('Session', 'timeout_ips', $ips);
	}
	
	/**
	 * Add a value to a specific key in the session array
	 */
	public static function add_to_array($name, $val) {
		return self::current_session()->inst_addToArray($name, $val);
	}
	
	/**
	 * Set a key/value pair in the session
	 *
	 * @param string $name Key
	 * @param string $val Value
	 */
	public static function set($name, $val) {
		return self::current_session()->inst_set($name, $val);
	}
	
	/**
	 * Return a specific value by session key
	 *
	 * @param string $name Key to lookup
	 */
	public static function get($name) {
		return self::current_session()->inst_get($name);
	}
	
	/**
	 * Return all the values in session
	 *
	 * @return Array
	 */
	public static function get_all() {
		return self::current_session()->inst_getAll();
	}
		
	/**
	 * Clear a given session key, value pair.
	 *
	 * @param string $name Key to lookup
	 */
	public static function clear($name) {
		return self::current_session()->inst_clear($name);
	}
	
	/**
	 * Clear all the values
	 *
	 * @return void
	 */
	public static function clear_all() {
		self::current_session()->inst_clearAll();
		self::$default_session = null;			
	}
		
	/**
	 * Save all the values in our session to $_SESSION
	 */
	public static function save() {
		return self::current_session()->inst_save();
	}
	
	protected static $default_session = null;
	
	protected static function current_session() {
		if(Controller::has_curr()) {
			return Controller::curr()->getSession();
		} else {
			if(!self::$default_session) self::$default_session = new Session(isset($_SESSION) ? $_SESSION : array());
			return self::$default_session;
		}
	}

	public function inst_set($name, $val) {
		// Quicker execution path for "."-free names
		if(strpos($name,'.') === false) {
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
			foreach($names as $n) {
				$var = &$var[$n];
			}

			if($var !== $val) {
				foreach($names as $n) {
					$diffVar = &$diffVar[$n];
				}

				$var = $val;
				$diffVar = $val;
			}
		}
	}

	public function inst_addToArray($name, $val) {
		$names = explode('.', $name);
		
		// We still want to do this even if we have strict path checking for legacy code
		$var = &$this->data;
		$diffVar = &$this->changedData;
			
		foreach($names as $n) {
			$var = &$var[$n];
			$diffVar = &$diffVar[$n];
		}
			
		$var[] = $val;
		$diffVar[sizeof($var)-1] = $val;
	}
	
	public function inst_get($name) {
		// Quicker execution path for "."-free names
		if(strpos($name,'.') === false) {
			if(isset($this->data[$name])) return $this->data[$name];
			
		} else {
			$names = explode('.', $name);
		
			if(!isset($this->data)) {
				return null;
			}
		
			$var = $this->data;

			foreach($names as $n) {
				if(!isset($var[$n])) {
					return null;
				}
				$var = $var[$n];
			}

			return $var;
		}
	}

	public function inst_clear($name) {
		$names = explode('.', $name);

		// We still want to do this even if we have strict path checking for legacy code
		$var = &$this->data;
		$diffVar = &$this->changedData;
			
		foreach($names as $n) {
			// don't clear a record that doesn't exist
			if(!isset($var[$n])) return;
			$var = &$var[$n];
		}

		// only loop to find data within diffVar if var is proven to exist in the above loop
		foreach($names as $n) {
			$diffVar = &$diffVar[$n];
		}

		if($var !== null) {
			$var = null;
			$diffVar = null;
		}
	}

	public function inst_clearAll() {
		if($this->data && is_array($this->data)) {
			foreach(array_keys($this->data) as $key) {
				$this->inst_clear($key);
			}
		}
	}

	public function inst_getAll() {
		return $this->data;
	}

	public function inst_finalize() {
		$this->inst_set('HTTP_USER_AGENT', $this->userAgent());
	}

	/**
	 * Save data to session
	 * Only save the changes, so that anyone manipulating $_SESSION directly doesn't get burned.
	 */ 
	public function inst_save() {
		if($this->changedData) {
			$this->inst_finalize();
			if(!isset($_SESSION)) Session::start();
			$this->recursivelyApply($this->changedData, $_SESSION);
		}
	}
	
	/**
	 * Recursively apply the changes represented in $data to $dest.
	 * Used to update $_SESSION
	 */	
	protected function recursivelyApply($data, &$dest) {
		foreach($data as $k => $v) {
			if(is_array($v)) {
				if(!isset($dest[$k]) || !is_array($dest[$k])) $dest[$k] = array();
				$this->recursivelyApply($v, $dest[$k]);
			} else {
				$dest[$k] = $v;
			}
		}
	}

	/**
	 * Return the changed data, for debugging purposes.
	 * @return array
	 */
	public function inst_changedData() {
		return $this->changedData;
	}

	/**
	* Sets the appropriate form message in session, with type. This will be shown once,
	* for the form specified.
	*
	* @param formname the form name you wish to use ( usually $form->FormName() )
	* @param messsage the message you wish to add to it
	* @param type the type of message
	*/
	public static function setFormMessage($formname,$message,$type){
		Session::set("FormInfo.$formname.formError.message", $message);
		Session::set("FormInfo.$formname.formError.type", $type);
	}

	/**
	 * Is there a session ID in the request?
	 * @return bool
	 */
	public static function request_contains_session_id() {
		$secure = Director::is_https() && Config::inst()->get('Session', 'cookie_secure');
		$name = $secure ? 'SECSESSID' : session_name();
		return isset($_COOKIE[$name]) || isset($_REQUEST[$name]);
	}

	/**
	 * Initialize session.
	 *
	 * @param string $sid Start the session with a specific ID
	 */
	public static function start($sid = null) {
		$path = Config::inst()->get('Session', 'cookie_path');
		if(!$path) $path = Director::baseURL();
		$domain = Config::inst()->get('Session', 'cookie_domain');
		$secure = Director::is_https() && Config::inst()->get('Session', 'cookie_secure');
		$session_path = Config::inst()->get('Session', 'session_store_path');
		$timeout = Config::inst()->get('Session', 'timeout');

		if(!session_id() && !headers_sent()) {
			if($domain) {
				session_set_cookie_params($timeout, $path, $domain,
					$secure /* secure */, true /* httponly */);
			} else {
				session_set_cookie_params($timeout, $path, null,
					$secure /* secure */, true /* httponly */);
			}

			// Allow storing the session in a non standard location
			if($session_path) session_save_path($session_path);

			// If we want a secure cookie for HTTPS, use a seperate session name. This lets us have a
			// seperate (less secure) session for non-HTTPS requests
			if($secure) session_name('SECSESSID');

			// @ is to supress win32 warnings/notices when session wasn't cleaned up properly
			// There's nothing we can do about this, because it's an operating system function!
			if($sid) session_id($sid);
			@session_start();
		}

		// Modify the timeout behaviour so it's the *inactive* time before the session expires.
		// By default it's the total session lifetime
		if($timeout && !headers_sent()) {
			Cookie::set(session_name(), session_id(), $timeout/86400, $path, $domain ? $domain
				: null, $secure, true);
		}
	}

	/**
	 * Destroy the active session.
	 *
	 * @param bool $removeCookie If set to TRUE, removes the user's cookie, FALSE does not remove
	 */
	public static function destroy($removeCookie = true) {
		if(session_id()) {
			if($removeCookie) {
				$path = Config::inst()->get('Session', 'cookie_path');
				if(!$path) $path = Director::baseURL();
				$domain = Config::inst()->get('Session', 'cookie_domain');
				$secure = Config::inst()->get('Session', 'cookie_secure');
				
				if($domain) {
					Cookie::set(session_name(), '', null, $path, $domain, $secure, true);
				}
				else {
					Cookie::set(session_name(), '', null, $path, null, $secure, true);
				}
				
				unset($_COOKIE[session_name()]);
			}

			session_destroy();

			// Clean up the superglobal - session_destroy does not do it.
			// http://nz1.php.net/manual/en/function.session-destroy.php
			unset($_SESSION);
		}
	}
	
	/**
	 * Set the timeout of a Session value
	 *
	 * @deprecated 3.2 Use the "Session.timeout" config setting instead
	 * 
	 * @param int $timeout Time until a session expires in seconds. Defaults to expire when browser is closed.
	 */
	public static function set_timeout($timeout) {
		Deprecation::notice('3.2', 'Use the "Session.timeout" config setting instead');
		Config::inst()->update('Session', 'timeout', (int)$timeout);
	}
	
	/**
	 * @deprecated 3.2 Use the "Session.timeout" config setting instead
	 */
	public static function get_timeout() {
		Deprecation::notice('3.2', 'Use the "Session.timeout" config setting instead');
		return Config::inst()->get('Session', 'timeout');
	}
}
