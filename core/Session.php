<?php

/**
 * @package sapphire
 * @subpackage control
 */

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
 * @package sapphire
 * @subpackage control
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
		return Controller::curr()->getSession()->inst_getAll();
	}
	public static function save() {
		return Controller::curr()->getSession()->inst_save();
	}

	/**
	 * Session data
	 */
	protected $data = array();
	protected $changedData = array();
	
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
		$diffVar = &$this->changedData;
			
		foreach($names as $n) {
			$var = &$var[$n];
			$diffVar = &$diffVar[$n];
		}
			
		$var = $val;
		$diffVar = $val;
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
		$diffVar = &$this->changedData;
			
		foreach($names as $n) {
			$var = &$var[$n];
			$diffVar = &$diffVar[$n];
		}
			
		$var = null;
		$diffVar = null;
	}
		
	public function inst_getAll() {
		return $this->data;
	}
	
	/**
	 * Save data to session
	 * Only save the changes, so that anyone manipulating $_SESSION directly doesn't get burned.
	 */ 
	public function inst_save() {
		$this->recursivelyApply($this->changedData, $_SESSION);
	}
	
	/**
	 * Recursively apply the changes represented in $data to $dest.
	 * Used to update $_SESSION
	 */	
	protected function recursivelyApply($data, &$dest) {
		foreach($data as $k => $v) {
			if(is_array($v)) {
				if(!isset($dest[$k])) $dest[$k] = array();
				$this->recursivelyApply($v, $dest[$k]);
			} else {
				$dest[$k] = $v;
			}
		}
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
