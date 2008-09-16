<?php

/**
 * Represents a HTTP-request, including a URL that is tokenised for parsing, and a request method (GET/POST/PUT/DELETE).
 * This is used by {@link RequestHandlingData} objects to decide what to do.
 * 
 * The intention is that a single HTTPRequest object can be passed from one object to another, each object calling
 * match() to get the information that they need out of the URL.  This is generally handled by 
 * {@link RequestHandlingData::handleRequest()}.
 * 
 * @todo Accept X_HTTP_METHOD_OVERRIDE http header and $_REQUEST['_method'] to override request types (useful for webclients
 *   not supporting PUT and DELETE)
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
	 * The HTTP method: GET/PUT/POST/DELETE/HEAD
	 */
	protected $httpMethod;
	
	protected $getVars = array();
	
	protected $postVars = array();

	/**
	 * HTTP Headers like "Content-Type: text/xml"
	 *
	 * @see http://en.wikipedia.org/wiki/List_of_HTTP_headers
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * Raw HTTP body, used by PUT and POST requests.
	 *
	 * @var string
	 */
	protected $body;
	
	protected $allParams = array();
	
	protected $latestParams = array();
	
	protected $unshiftedButParsedParts = 0;
	
	function isGET() {
		return $this->httpMethod == 'GET';
	}
	
	function isPOST() {
		return $this->httpMethod == 'POST';
	}
	
	function isPUT() {
		return $this->httpMethod == 'PUT';
	}

	function isDELETE() {
		return $this->httpMethod == 'DELETE';
	}	

	function isHEAD() {
		return $this->httpMethod == 'HEAD';
	}	
	
	function setBody($body) {
		$this->body = $body;
	}
	
	function getBody() {
		return $this->body;
	}
	
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
	
	function getExtension() {
		return $this->extension;
	}
	
	/**
	 * Add a HTTP header to the response, replacing any header of the same name.
	 * 
	 * @param string $header Example: "Content-Type"
	 * @param string $value Example: "text/xml" 
	 */
	function addHeader($header, $value) {
		$this->headers[$header] = $value;
	}
	
	/**
	 * @return array
	 */
	function getHeaders() {
		return $this->headers;
	}
	
	/**
	 * Remove an existing HTTP header
	 *
	 * @param string $header
	 */
	function getHeader($header) {
		return (isset($this->headers[$header])) ? $this->headers[$header] : null;			
	}
	
	/**
	 * Remove an existing HTTP header by its name,
	 * e.g. "Content-Type".
	 *
	 * @param string $header
	 */
	function removeHeader($header) {
		if(isset($this->headers[$header])) unset($this->headers[$header]);
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
	function __construct($httpMethod, $url, $getVars = array(), $postVars = array(), $body = null) {
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
		$this->body = $body;
		
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
				
			// Literal parts with extension
			} else if(isset($this->dirParts[$i]) && $this->dirParts[$i] . '.' . $this->extension == $part) {
				continue;
				
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
		
		if($arguments === array()) $arguments['_matched'] = true;
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
	 * Returns true if this is a URL that will match without shifting off any of the URL.
	 * This is used by the request handler to prevent infinite parsing loops.
	 */
	function isEmptyPattern($pattern) {
		if(preg_match('/^([A-Za-z]+) +(.*)$/', $pattern, $matches)) {
			$pattern = $matches[2];
		}
		
		if(trim($pattern) == "") return true;
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
	
	/**
	 * Returns the client IP address which
	 * originated this request.
	 *
	 * @return string
	 */
	function getIP() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	  		//check ip from share internet
			return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	  		//to check ip is pass from proxy
			return  $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif(isset($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}
	}
	
	/**
	 * Returns all mimetypes from the HTTP "Accept" header
	 * as an array.
	 * 
	 * @param boolean $includeQuality Don't strip away optional "quality indicators", e.g. "application/xml;q=0.9" (Default: false)
	 * @return array
	 */
	function getAcceptMimetypes($includeQuality = false) {
	   $mimetypes = array();
	   $mimetypesWithQuality = explode(',',$this->getHeader('Accept'));
	   foreach($mimetypesWithQuality as $mimetypeWithQuality) {
	      $mimetypes[] = ($includeQuality) ? $mimetypeWithQuality : preg_replace('/;.*/', '', $mimetypeWithQuality);
	   }
	   return $mimetypes;
	}
}