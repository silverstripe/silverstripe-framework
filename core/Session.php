<?php

/**
 * Handles all manipulation of the session.
 *
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
		$names = explode('.', $name);
		
		// We still want to do this even if we have strict path checking for legacy code
		$var = &$_SESSION;
			
		foreach($names as $n) {
			$var = &$var[$n];
		}
			
		$var = $val;
	}
	
	public static function addToArray($name, $val) {
		$names = explode('.', $name);
		
		// We still want to do this even if we have strict path checking for legacy code
		$var = &$_SESSION;
			
		foreach($names as $n) {
			$var = &$var[$n];
		}
			
		$var[] = $val;
	}
	
	public static function get($name) {
		$names = explode('.', $name);
		$var = $_SESSION;

		foreach($names as $n) {
			if(!isset($var[$n])) {
				return null;
			}
			$var = $var[$n];
		}

		return $var;
	}

	public static function clear($name) {
		$names = explode('.', $name);

		// We still want to do this even if we have strict path checking for legacy code
		$var = &$_SESSION;
			
		foreach($names as $n) {
			$var = &$var[$n];
		}
			
		$var = null;
	}
		
	public static function getAll() {
		return $_SESSION;
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
