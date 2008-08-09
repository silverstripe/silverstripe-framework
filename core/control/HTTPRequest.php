<?php

/**
 * Represents a HTTP-request, including a URL that is tokenised for parsing, and a request method (GET/POST/PUT/DELETE).
 * This is used by {@link RequestHandlingData} objects to decide what to do.
 * 
 * The intention is that a single HTTPRequest object can be passed from one object to another, each object calling
 * match() to get the information that they need out of the URL.  This is generally handled by 
 * {@link RequestHandlingData::handleRequest()}.
 */
class HTTPRequest extends Object implements ArrayAccess {
	/**
	 * The non-extension parts of the URL, separated by "/"
	 */
	protected $dirParts;

	/**
	 * The URL extension
	 */
	protected $extension;
	
	/**
	 * The HTTP method
	 */
	protected $httpMethod;
	
	protected $getVars = array();
	protected $postVars = array();
	
	protected $allParams = array();
	protected $latestParams = array();
	
	protected $unshiftedButParsedParts = 0;
	
	function getVars() {
		return $this->getVars;
	}
	function postVars() {
		return $this->postVars;
	}
	function requestVars() {
		return array_merge($this->getVars, $this->postVars);
	}
	
	function getVar($name) {
		if(isset($this->getVars[$name])) return $this->getVars[$name];
	}
	function postVar($name) {
		if(isset($this->postVars[$name])) return $this->postVars[$name];
	}
	function requestVar($name) {
		if(isset($this->postVars[$name])) return $this->postVars[$name];
		if(isset($this->getVars[$name])) return $this->getVars[$name];
	}
	
	/**
	 * Enables the existence of a key-value pair in the request to be checked using
	 * array syntax, so isset($request['title']) will check for $_POST['title'] and $_GET['title]
	 *
	 * @param unknown_type $offset
	 * @return boolean
	 */
	function offsetExists($offset) {
		if(isset($this->postVars[$offset])) return true;
		if(isset($this->getVars[$offset])) return true;
		return false;
	}
	
	/**
	 * Access a request variable using array syntax. eg: $request['title'] instead of $request->postVar('title')
	 *
	 * @param unknown_type $offset
	 * @return unknown
	 */
	function offsetGet($offset) {
		return $this->requestVar($offset);
	}
	
	/**
	 * @ignore
	 */
	function offsetSet($offset, $value) {}
	
	/**
	 * @ignore
	 */
	function offsetUnset($offset) {}

	/**
	 * Construct a HTTPRequest from a URL relative to the site root.
	 */
	function __construct($httpMethod, $url, $getVars = array(), $postVars = array()) {
		$this->httpMethod = $httpMethod;
		
		$url = preg_replace(array('/\/+/','/^\//', '/\/$/'),array('/','',''), $url);
		
		if(preg_match('/^(.*)\.([A-Za-z][A-Za-z0-9]*)$/', $url, $matches)) {
			$url = $matches[1];
			$this->extension = $matches[2];
		}
		if($url) $this->dirParts = split('/+', $url);
		else $this->dirParts = array();
		
		$this->getVars = (array)$getVars;
		$this->postVars = (array)$postVars;
		
		parent::__construct();
	}
	
	/**
	 * Matches a URL pattern
	 * The pattern can contain a number of segments, separted by / (and an extension indicated by a .)
	 * 
	 * The parts can be either literals, or, if they start with a $ they are interpreted as variables.
	 *  - Literals must be provided in order to match
	 *  - $Variables are optional
	 *  - However, if you put ! at the end of a variable, then it becomes mandatory.
	 * 
	 * For example:
	 *  - admin/crm/list will match admin/crm/$Action/$ID/$OtherID, but it won't match admin/crm/$Action!/$ClassName!
	 * 
	 * The pattern can optionally start with an HTTP method and a space.  For example, "POST $Controller/$Action".
	 * This is used to define a rule that only matches on a specific HTTP method.
	 */
	function match($pattern, $shiftOnSuccess = false) {
		// Check if a specific method is required
		if(preg_match('/^([A-Za-z]+) +(.*)$/', $pattern, $matches)) {
			$requiredMethod = $matches[1];
			if($requiredMethod != $this->httpMethod) return false;
			
			// If we get this far, we can match the URL pattern as usual.
			$pattern = $matches[2];
		}
		
		// Special case for the root URL controller
		if(!$pattern) {
			return ($this->dirParts == array()) ? array('Matched' => true) : false;
		}

		// Check for the '//' marker that represents the "shifting point"
		$doubleSlashPoint = strpos($pattern, '//');
		if($doubleSlashPoint !== false) {
			$shiftCount = substr_count($pattern, '/', 0, $doubleSlashPoint) + 1;
			$pattern = str_replace('//', '/', $pattern);
			$patternParts = explode('/', $pattern);
			
		} else {
			$patternParts = explode('/', $pattern);
			$shiftCount = sizeof($patternParts);
		}

		$matched = true;
		$arguments = array();

		foreach($patternParts as $i => $part) {
			$part = trim($part);
			
			// Match a variable
			if(isset($part[0]) && $part[0] == '$') {
				// A variable ending in ! is required
				if(substr($part,-1) == '!') {
					$varRequired = true;
					$varName = substr($part,1,-1);
				} else {
					$varRequired = false;
					$varName = substr($part,1);
				}
				
				// Fail if a required variable isn't populated
				if($varRequired && !isset($this->dirParts[$i])) return false;
				
				$arguments[$varName] = isset($this->dirParts[$i]) ? $this->dirParts[$i] : null;
				if($part == '$Controller' && !class_exists($arguments['Controller'])) {
					return false;
				}
			
			// Literal parts must always be there
			} else if(!isset($this->dirParts[$i]) || $this->dirParts[$i] != $part) {
				return false;
			}
		}
		
		if($shiftOnSuccess) {
			$this->shift($shiftCount);
			// We keep track of pattern parts that we looked at but didn't shift off.
			// This lets us say that we have *parsed* the whole URL even when we haven't *shifted* it all
			$this->unshiftedButParsedParts = sizeof($patternParts) - $shiftCount;
		}
		
		$this->latestParams = $arguments;
		
		// Load the arguments that actually have a value into $this->allParams
		// This ensures that previous values aren't overridden with blanks
		foreach($arguments as $k => $v) {
			if($v || !isset($this->allParams[$k])) $this->allParams[$k] = $v;
		}
		
		return $arguments;
	}
	
	function allParams() {
		return $this->allParams;
	}
	function latestParams() {
		return $this->latestParams;
	}
	function latestParam($name) {
		if(isset($this->latestParams[$name]))
			return $this->latestParams[$name];
		else
			return null;
	}
	function param($name) {
		if(isset($this->allParams[$name]))
			return $this->allParams[$name];
		else
			return null;
	}
	
	function remaining() {
		return implode("/", $this->dirParts);
	}
	
	/**
	 * Returns true if the give pattern is an empty pattern - that is, one that only matches completely parsed
	 * URLs.  It will also return true if this is a completely parsed URL and the pattern contains only variable
	 * references.
	 */
	function isEmptyPattern($pattern) {
		if(preg_match('/^([A-Za-z]+) +(.*)$/', $pattern, $matches)) {
			$pattern = $matches[2];
		}
		
		if(trim($pattern) == "") return true;
		
		if(!$this->dirParts) {
			return preg_replace('/\$[A-Za-z][A-Za-z0-9]*(\/|$)/','',$pattern) == "";
		}
	}
	
	/**
	 * Shift one or more parts off the beginning of the URL.
	 * If you specify shifting more than 1 item off, then the items will be returned as an array
	 */
	function shift($count = 1) {
		if($count == 1) return array_shift($this->dirParts);
		else for($i=0;$i<$count;$i++) $return[] = array_shift($this->dirParts);
	}
	
	/**
	 * Returns true if the URL has been completely parsed.
	 * This will respect parsed but unshifted directory parts.
	 */
	function allParsed() {
		return sizeof($this->dirParts) <= $this->unshiftedButParsedParts;
		
	}
}