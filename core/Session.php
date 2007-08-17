<?php

/**
 * Handles all manipulation of the session.
 * 
 * The static methods are used to manipulate the currently active controller's session.
 * The instance methods are used to manipulate a particular session.  There can be more than one of these created.
 * 
 * In order to support things like testing, the session is associated with a particular Controller.  In normal usage, this is loaded from
 * and saved to the regular PHP session, but for things like static-page-generation and unit-testing, you can create multiple Controllers,
 * each with their own session.
 * 
 * The instance object is basically just a way of manipulating a set of nested maps, and isn't specific to session data.
 * This class is currently really basic and could do with a more well-thought-out implementation
 *
 * $session->myVar = 'XYZ' would be fine, as would Session::data->myVar.  What about the equivalent
 * of Session::get('member.abc')?  Are the explicit accessor methods acceptable?  Do we need a
 * broader spectrum of functions, such as Session::inc("cart.$productID", 2)?  And what should 
 * Session::get("cart") then return?  An array?
 *
 * @todo Decide whether this class is really necessary, and if so, overhaul it.  Perhaps use
 * __set() and __get() on an instance, rather than static functions?
 */
class Session {
	public static function set($name, $val) {
		return Controller::curr()->getSession()->inst_set($name, $val);
	}
	public static function addToArray($name, $val) {
		return Controller::curr()->getSession()->inst_addToArray($name, $val);
	}
	public static function get($name) {
		return Controller::curr()->getSession()->inst_get($name);
	}
	public static function clear($name) {
		return Controller::curr()->getSession()->inst_clear($name);
	}
	public static function getAll() {
		return Controller::curr()->getSession()->inst_getAll($name);
	}

	/**
	 * Session data
	 */
	protected $data = array();
	
	/**
	 * Create a new session object, with the given starting data
	 * @param $data Can be an array of data (such as $_SESSION) or another Session object to clone.
	 */
	function __construct($data) {
		if($data instanceof Session) $data = $data->inst_getAll();
		
		$this->data = $data;
	}

	public function inst_set($name, $val) {
		$names = explode('.', $name);
		
		// We still want to do this even if we have strict path checking for legacy code
		$var = &$this->data;
			
		foreach($names as $n) {
			$var = &$var[$n];
		}
			
		$var = $val;
	}
	
	public function inst_addToArray($name, $val) {
		$names = explode('.', $name);
		
		// We still want to do this even if we have strict path checking for legacy code
		$var = &$this->data;
			
		foreach($names as $n) {
			$var = &$var[$n];
		}
			
		$var[] = $val;
	}
	
	public function inst_get($name) {
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

	public function inst_clear($name) {
		$names = explode('.', $name);

		// We still want to do this even if we have strict path checking for legacy code
		$var = &$this->data;
			
		foreach($names as $n) {
			$var = &$var[$n];
		}
			
		$var = null;
	}
		
	public function inst_getAll() {
		return $this->data;
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
		Session::set("FormInfo.$formname.message", $message);
		Session::set("FormInfo.$formname.type", $type);
	}

	public static function start() {
		if(!session_id() && !headers_sent()) {
			session_set_cookie_params(0, Director::baseURL());
			session_start();
		}
	}
}

?>
