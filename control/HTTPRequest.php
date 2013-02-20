<?php

/**
 * Represents a HTTP-request, including a URL that is tokenised for parsing, and a request method
 * (GET/POST/PUT/DELETE). This is used by {@link RequestHandler} objects to decide what to do.
 * 
 * The intention is that a single SS_HTTPRequest object can be passed from one object to another, each object calling
 * match() to get the information that they need out of the URL.  This is generally handled by 
 * {@link RequestHandler::handleRequest()}.
 * 
 * @todo Accept X_HTTP_METHOD_OVERRIDE http header and $_REQUEST['_method'] to override request types (useful for
 *       webclients not supporting PUT and DELETE)
 * 
 * @package framework
 * @subpackage control
 */
class SS_HTTPRequest extends SS_HttpMessage implements ArrayAccess {

	/**
	 * @var string $url
	 */
	protected $url;

	/**
	 * The non-extension parts of the passed URL as an array, originally exploded by the "/" separator.
	 * All elements of the URL are loaded in here,
	 * and subsequently popped out of the array by {@link shift()}.
	 * Only use this structure for internal request handling purposes.
	 */
	protected $dirParts;

	/**
	 * @var string $httpMethod The HTTP method in all uppercase: GET/PUT/POST/DELETE/HEAD
	 */
	protected $httpMethod;
	
	/**
	 * @var array $getVars Contains alls HTTP GET parameters passed into this request.
	 */
	protected $getVars = array();
	
	/**
	 * @var array $postVars Contains alls HTTP POST parameters passed into this request.
	 */
	protected $postVars = array();

	/**
	 * @var array $allParams Contains an assiciative array of all
	 * arguments matched in all calls to {@link RequestHandler->handleRequest()}.
	 * It's a "historical record" that's specific to the current call of
	 * {@link handleRequest()}, and is only complete once the "last call" to that method is made.
	 */
	protected $allParams = array();
	
	/**
	 * @var array $latestParams Contains an associative array of all
	 * arguments matched in the current call from {@link RequestHandler->handleRequest()},
	 * as denoted with a "$"-prefix in the $url_handlers definitions.
	 * Contains different states throughout its lifespan, so just useful
	 * while processed in {@link RequestHandler} and to get the last
	 * processes arguments.
	 */
	protected $latestParams = array();
	
	/**
	 * @var array $routeParams Contains an associative array of all arguments
	 * explicitly set in the route table for the current request.
	 * Useful for passing generic arguments via custom routes.
	 * 
	 * E.g. The "Locale" parameter would be assigned "en_NZ" below
	 * 
	 * Director:
	 *   rules:
	 *     'en_NZ/$URLSegment!//$Action/$ID/$OtherID':
	 *       Controller: 'ModelAsController'
	 *       Locale: 'en_NZ'
	 */
	protected $routeParams = array();
	
	protected $unshiftedButParsedParts = 0;
	
	/**
	 * Construct a SS_HTTPRequest from a URL relative to the site root.
	 */
	public function __construct($httpMethod, $url, $getVars = array(), $postVars = array(), $body = null) {
		$this->httpMethod = strtoupper(self::detect_method($httpMethod, $postVars));
		$this->url = $url;

		//Normalize URL if its relative (strictly speaking), or has leading slashes
		if(Director::is_relative_url($url) || preg_match('/^\//', $url)) {
			$this->url = preg_replace(array('/\/+/','/^\//', '/\/$/'),array('/','',''), $this->url);
		}

		if($this->url) $this->dirParts = preg_split('|/+|', $this->url);
		else $this->dirParts = array();
		
		$this->getVars = (array)$getVars;
		$this->postVars = (array)$postVars;
		$this->setBody($body);
	}

	/**
	 * @return bool
	 */
	public function isGET() {
		return $this->httpMethod == 'GET';
	}

	/**
	 * @return bool
	 */
	public function isPOST() {
		return $this->httpMethod == 'POST';
	}

	/**
	 * @return bool
	 */
	public function isPUT() {
		return $this->httpMethod == 'PUT';
	}

	/**
	 * @return bool
	 */
	public function isDELETE() {
		return $this->httpMethod == 'DELETE';
	}

	/**
	 * @return bool
	 */
	public function isHEAD() {
		return $this->httpMethod == 'HEAD';
	}

	/**
	 * @return array
	 */
	public function getVars() {
		return $this->getVars;
	}

	/**
	 * @return array
	 */
	public function postVars() {
		return $this->postVars;
	}
	
	/**
	 * Returns all combined HTTP GET and POST parameters
	 * passed into this request. If a parameter with the same
	 * name exists in both arrays, the POST value is returned.
	 * 
	 * @return array
	 */
	public function requestVars() {
		return ArrayLib::array_merge_recursive($this->getVars, $this->postVars);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getVar($name) {
		if(isset($this->getVars[$name])) return $this->getVars[$name];
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function postVar($name) {
		if(isset($this->postVars[$name])) return $this->postVars[$name];
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function requestVar($name) {
		if(isset($this->postVars[$name])) return $this->postVars[$name];
		if(isset($this->getVars[$name])) return $this->getVars[$name];
	}

	/**
	 * Gets the extension included in the request URL.
	 * 
	 * @return string
	 */
	public function getExtension() {
		return pathinfo($this->getURL(), PATHINFO_EXTENSION);
	}

	/**
	 * Checks if the {@link SS_HTTPRequest->getExtension()} on this request matches one of the more common media types
	 * embedded into a webpage - e.g. css, png.
	 *
	 * This is useful for things like determining wether to display a fully rendered error page or not. Note that the
	 * media file types is not at all comprehensive.
	 *
	 * @return bool
	 */
	public function isMedia() {
		return in_array($this->getExtension(), array('css', 'js', 'jpg', 'jpeg', 'gif', 'png', 'bmp', 'ico'));
	}

	/**
	 * Returns the URL used to generate the page
	 *
	 * @param bool $includeGetVars whether or not to include the get parameters\
	 * @return string
	 */
	public function getURL($includeGetVars = false) {
		$url = $this->url;

		if ($includeGetVars) { 
			// if we don't unset $vars['url'] we end up with /my/url?url=my/url&foo=bar etc 
			
			$vars = $this->getVars();
			unset($vars['url']);

			if (count($vars)) {
				$url .= '?' . http_build_query($vars);
			}
		}
		else if(strpos($url, "?") !== false) {
			$url = substr($url, 0, strpos($url, "?"));
		}

		return $url; 
	}

	/**
	 * Returns true if this request an ajax request,
	 * based on custom HTTP ajax added by common JavaScript libraries,
	 * or based on an explicit "ajax" request parameter.
	 * 
	 * @return boolean
	 */
	public function isAjax() {
		return (
			$this->requestVar('ajax') ||
			$this->getHeader('X-Requested-With') && $this->getHeader('X-Requested-With') == "XMLHttpRequest"
		);
	}
	
	/**
	 * Enables the existence of a key-value pair in the request to be checked using
	 * array syntax, so isset($request['title']) will check for $_POST['title'] and $_GET['title']
	 *
	 * @param unknown_type $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
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
	public function offsetGet($offset) {
		return $this->requestVar($offset);
	}
	
	/**
	 * @ignore
	 */
	public function offsetSet($offset, $value) {}
	
	/**
	 * @ignore
	 */
	public function offsetUnset($offset) {}
	
	/**
	 * Construct an SS_HTTPResponse that will deliver a file to the client
	 *
	 * @static
	 * @param $fileData
	 * @param $fileName
	 * @param null $mimeType
	 * @return SS_HTTPResponse
	 */
	public static function send_file($fileData, $fileName, $mimeType = null) {
		if(!$mimeType) {
			$mimeType = HTTP::get_mime_type($fileName);
		}
		$response = new SS_HTTPResponse($fileData);
		$response->addHeader("Content-Type", "$mimeType; name=\"" . addslashes($fileName) . "\"");
		$response->addHeader("Content-disposition", "attachment; filename=" . addslashes($fileName));
		$response->addHeader("Content-Length", strlen($fileData));
		$response->addHeader("Pragma", ""); // Necessary because IE has issues sending files over SSL
		
		if(strstr($_SERVER["HTTP_USER_AGENT"],"MSIE") == true) {
			$response->addHeader('Cache-Control', 'max-age=3, must-revalidate'); // Workaround for IE6 and 7
		}
		
		return $response;
	}
	
	/**
	 * Matches a URL pattern
	 * The pattern can contain a number of segments, separated by / (and an extension indicated by a .)
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
	 *
	 * @param $pattern
	 * @param bool $shiftOnSuccess
	 * @return array|bool
	 */
	public function match($pattern, $shiftOnSuccess = false) {
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
			$shiftCount = substr_count(substr($pattern,0,$doubleSlashPoint), '/') + 1;
			$pattern = str_replace('//', '/', $pattern);
			$patternParts = explode('/', $pattern);
			
			
		} else {
			$patternParts = explode('/', $pattern);
			$shiftCount = sizeof($patternParts);
		}

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
				if($part == '$Controller' && (!ClassInfo::exists($arguments['Controller'])
						|| !is_subclass_of($arguments['Controller'], 'Controller'))) {
					
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

		if($arguments === array()) $arguments['_matched'] = true;
		return $arguments;
	}

	/**
	 * @return array
	 */
	public function allParams() {
		return $this->allParams;
	}
	
	/**
	 * Shift all the parameter values down a key space, and return the shifted value.
	 *
	 * @return string
	 */
	public function shiftAllParams() {
		$keys    = array_keys($this->allParams);
		$values  = array_values($this->allParams);
		$value   = array_shift($values);

		// push additional unparsed URL parts onto the parameter stack
		if(array_key_exists($this->unshiftedButParsedParts, $this->dirParts)) {
			$values[] = $this->dirParts[$this->unshiftedButParsedParts];
		}

		foreach($keys as $position => $key) {
			$this->allParams[$key] = isset($values[$position]) ? $values[$position] : null;
		}

		return $value;
	}

	/**
	 * @return array
	 */
	public function latestParams() {
		return $this->latestParams;
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function latestParam($name) {
		if(isset($this->latestParams[$name])) return $this->latestParams[$name];
		else return null;
	}

	/**
	 * @return array
	 */
	public function routeParams() {
		return $this->routeParams;
	}

	/**
	 * @param $params
	 * @return SS_HTTPRequest $this
	 */
	public function setRouteParams($params) {
		$this->routeParams = $params;
		return $this;
	}

	/**
	 * @return array
	 */
	public function params() {
		return array_merge($this->allParams, $this->routeParams);
	}
	
	/**
	 * Finds a named URL parameter (denoted by "$"-prefix in $url_handlers)
	 * from the full URL, or a parameter specified in the route table
	 * 
	 * @param string $name
	 * @return string Value of the URL parameter (if found)
	 */
	public function param($name) {
		$params = $this->params();
		if(isset($params[$name])) return $params[$name];
		else return null;
	}
	
	/**
	 * Returns the unparsed part of the original URL
	 * separated by commas. This is used by {@link RequestHandler->handleRequest()}
	 * to determine if further URL processing is necessary.
	 * 
	 * @return string Partial URL
	 */
	public function remaining() {
		return implode("/", $this->dirParts);
	}
	
	/**
	 * Returns true if this is a URL that will match without shifting off any of the URL.
	 * This is used by the request handler to prevent infinite parsing loops.
	 *
	 * @param $pattern
	 * @return bool
	 */
	public function isEmptyPattern($pattern) {
		if(preg_match('/^([A-Za-z]+) +(.*)$/', $pattern, $matches)) {
			$pattern = $matches[2];
		}
		
		if(trim($pattern) == "") return true;
	}
	
	/**
	 * Shift one or more parts off the beginning of the URL.
	 * If you specify shifting more than 1 item off, then the items will be returned as an array
	 *
	 * @param int $count Shift Count
	 * @return String|Array
	 */
	public function shift($count = 1) {
		$return = array();
		
		if($count == 1) return array_shift($this->dirParts);
		
		for($i=0;$i<$count;$i++) {
			$value = array_shift($this->dirParts);
			
			if($value === null) break;
			
			$return[] = $value;
		}
		
		return $return;
	}

	/**
	 * Returns true if the URL has been completely parsed.
	 * This will respect parsed but unshifted directory parts.
	 *
	 * @return bool
	 */
	public function allParsed() {
		return sizeof($this->dirParts) <= $this->unshiftedButParsedParts;
	}
	
	/**
	 * Returns the client IP address which
	 * originated this request.
	 *
	 * @return string
	 */
	public function getIP() {
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
	 * @param boolean $includeQuality Don't strip away optional "quality indicators", e.g. "application/xml;q=0.9"
	 *                                (Default: false)
	 * @return array
	 */
	public function getAcceptMimetypes($includeQuality = false) {
		$mimetypes = array();
		$mimetypesWithQuality = explode(',',$this->getHeader('Accept'));
		foreach($mimetypesWithQuality as $mimetypeWithQuality) {
			$mimetypes[] = ($includeQuality) ? $mimetypeWithQuality : preg_replace('/;.*/', '', $mimetypeWithQuality);
		}
		return $mimetypes;
	}
	
	/**
	 * @return string HTTP method (all uppercase)
	 */
	public function httpMethod() {
		return $this->httpMethod;
	}
	
	/**
	 * Gets the "real" HTTP method for a request.
	 * 
	 * Used to work around browser limitations of form
	 * submissions to GET and POST, by overriding the HTTP method
	 * with a POST parameter called "_method" for PUT, DELETE, HEAD.
	 * Using GET for the "_method" override is not supported,
	 * as GET should never carry out state changes.
	 * Alternatively you can use a custom HTTP header 'X-HTTP-Method-Override'
	 * to override the original method in {@link Director::direct()}. 
	 * The '_method' POST parameter overrules the custom HTTP header.
	 *
	 * @param string $origMethod Original HTTP method from the browser request
	 * @param array $postVars
	 * @return string HTTP method (all uppercase)
	 */
	public static function detect_method($origMethod, $postVars) {
		if(isset($postVars['_method'])) {
			if(!in_array(strtoupper($postVars['_method']), array('GET','POST','PUT','DELETE','HEAD'))) {
				user_error('Director::direct(): Invalid "_method" parameter', E_USER_ERROR);
			}
			return strtoupper($postVars['_method']);
		} else {
			return $origMethod;
		}
	}
}
