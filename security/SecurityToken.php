<?php
/**
 * @package sapphire
 * @subpackage security
 */

/**
 * Cross Site Request Forgery (CSRF) protection for the {@link Form} class and other GET links.
 * Can be used globally (through {@link SecurityToken::inst()})
 * or on a form-by-form basis {@link Form->getSecurityToken()}.
 * 
 * <b>Usage in forms</b>
 * 
 * This protective measure is automatically turned on for all new {@link Form} instances,
 * and can be globally disabled through {@link disable()}.
 * 
 * <b>Usage in custom controller actions</b>
 * 
 * <code>
 * class MyController extends Controller {
 * 	function mygetaction($request) {
 * 		if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
 * 
 * 		// valid action logic ...
 * 	}
 * }
 * </code>
 * 
 * @todo Make token name form specific for additional forgery protection.
 */
class SecurityToken extends Object {
	
	/**
	 * @var String
	 */
	protected static $default_name = 'SecurityID';
	
	/**
	 * @var SecurityToken
	 */
	protected static $inst = null;
	
	/**
	 * @var boolean
	 */
	protected static $enabled = true;
	
	/**
	 * @var String $name
	 */
	protected $name = null;
	
	/**
	 * @param $name
	 */
	function __construct($name = null) {
		$this->name = ($name) ? $name : self::get_default_name();
		// only regenerate if the token isn't already set in the session
		if(!$this->getValue()) $this->setValue($this->generate());
		
		parent::__construct();
	}
	
	/**
	 * Gets a global token (or creates one if it doesnt exist already).
	 * 
	 * @return SecurityToken
	 */
	static function inst() {
		if(!self::$inst) self::$inst = new SecurityToken();

		return self::$inst;
	}
	
	/**
	 * Globally disable the token (override with {@link NullSecurityToken})
	 * implementation. Note: Does not apply for 
	 */
	static function disable() {
		self::$enabled = false;
		self::$inst = new NullSecurityToken();
	}
	
	/**
	 * Globally enable tokens that have been previously disabled through {@link disable}.
	 */
	static function enable() {
		self::$enabled = true;
		self::$inst = new SecurityToken();
	}
	
	/**
	 * @return boolean
	 */
	static function is_enabled() {
		return self::$enabled;
	}
	
	/**
	 * @return String
	 */
	static function get_default_name() {
		return self::$default_name;
	}
	
	/**
	 * @return String
	 */
	function setName($name) {
		$val = $this->getValue();
		$this->name = $name;
		$this->setValue($val);
	}
	
	/**
	 * @return String
	 */
	function getName() {
		return $this->name;
	}
	
	/**
	 * @return String
	 */
	function getValue() {
		return Session::get($this->getName());
	}
	
	/**
	 * @param String $val
	 */
	function setValue($val) {
		Session::set($this->getName(), $val);
	}
	
	/**
	 * Checks for an existing CSRF token in the current users session.
	 * This check is automatically performed in {@link Form->httpSubmission()}
	 * if a form has security tokens enabled.
	 * This direct check is mainly used for URL actions on {@link FormField} that are not routed
	 * through {@link Form->httpSubmission()}.
	 * 
	 * Typically you'll want to check {@link Form->securityTokenEnabled()} before calling this method.
	 * 
	 * @param String $compare
	 * @return Boolean
	 */
	function check($compare) {
		return ($compare && $this->getValue() && $compare == $this->getValue());
	}
	
	/**
	 * See {@link check()}.
	 * 
	 * @param SS_HTTPRequest $request
	 * @return Boolean
	 */
	function checkRequest($request) {
		return $this->check($request->requestVar($this->getName()));
	}
	
	/**
	 * Note: Doesn't call {@link FormField->setForm()}
	 * on the returned {@link HiddenField}, you'll need to take
	 * care of this yourself.
	 * 
	 * @param FieldSet $fieldset
	 * @return HiddenField|false
	 */
	function updateFieldSet(&$fieldset) {
		if(!$fieldset->fieldByName($this->getName())) {
			$field = new HiddenField($this->getName(), null, $this->getValue());
			$fieldset->push($field);
			return $field;
		} else {
			return false;
		}
	}
	
	/**
	 * @param String $url
	 * @return String
	 */
	function addToUrl($url) {
		return Controller::join_links($url, sprintf('?%s=%s', $this->getName(), $this->getValue()));
	}
	
	/**
	 * You can't disable an existing instance, it will need to be overwritten like this:
	 * <code>
	 * $old = SecurityToken::inst(); // isEnabled() returns true
	 * SecurityToken::disable();
	 * $new = SecurityToken::inst(); // isEnabled() returns false
	 * </code>
	 * 
	 * @return boolean
	 */
	function isEnabled() {
		return !($this instanceof NullSecurityToken);
	}
	
	/**
	 * @uses RandomGenerator
	 * 
	 * @return String
	 */
	protected function generate() {
		$generator = new RandomGenerator();
		return $generator->randomToken('sha1');
	}
	
}

/**
 * Specialized subclass for disabled security tokens - always returns
 * TRUE for token checks. Use through {@link SecurityToken::disable()}.
 */
class NullSecurityToken extends SecurityToken {
	
	/**
	 * @param String
	 * @return boolean
	 */
	function check($compare) {
		return true;
	}
	
	/**
	 * @param SS_HTTPRequest $request
	 * @return Boolean
	 */
	function checkRequest($request) {
		return true;
	}
	
	/**
	 * @param FieldSet $fieldset
	 * @return false
	 */
	function updateFieldSet(&$fieldset) {
		// Remove, in case it was added beforehand
		$fieldset->removeByName($this->getName());
		
		return false;
	}
	
	/**
	 * @param String $url
	 * @return String
	 */
	function addToUrl($url) {
		return $url;
	}
	
	/**
	 * @return String
	 */
	function getValue() {
		return null;
	}
	
	/**
	 * @param String $val
	 */
	function setValue($val) {
		// no-op
	}
	
	/**
	 * @return String
	 */
	function generate() {
		return null;
	}
	
}
