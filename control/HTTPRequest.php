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
class SS_HTTPRequest extends SS_HTTPMessage implements ArrayAccess {

	/**
	 * @var string $url
	 */
	protected $url;

	/**
	 * @var array $urlParts
	 */
	private $urlParts;

	/**
	 * @var string $httpMethod The HTTP method in all uppercase: GET/PUT/POST/DELETE/HEAD
	 */
	protected $method;
	
	/**
	 * @var array $getVars Contains alls HTTP GET parameters passed into this request.
	 */
	protected $getVars = array();
	
	/**
	 * @var array $postVars Contains alls HTTP POST parameters passed into this request.
	 */
	protected $postVars = array();

	/**
	 * @var array $filesVars
	 */
	protected $filesVars = array();

	private $matchedParams = array();

	private $latestParams = array();

	private $routeParams = array();

	private $unshiftedButParsed = 0;

	/**
	 * Constructs a new request object.
	 *
	 * @param string $method the HTTP method
	 * @param string $url the URL relative to the site root
	 * @param array $getVars an array of GET vars
	 * @param array $postVars an array of POST vars
	 * @param array $filesVars an array of FILES vars
	 * @param string $body the request body
	 */
	public function __construct($method = null,
	                            $url = null,
	                            $getVars = array(),
	                            $postVars = array(),
	                            $filesVars = array(),
	                            $body = null) {
		$this->method = strtoupper(self::detect_method($method, $postVars));
		$this->setUrl($url);

		$this->getVars = (array) $getVars;
		$this->postVars = (array) $postVars;
		$this->filesVars = (array) $filesVars;

		$this->setBody($body);
	}

	/**
	 * Allow the setting of a URL
	 *
	 * This is here so that RootURLController can change the URL of the request
	 * without us loosing all the other info attached (like headers)
	 *
	 * @param string The new URL
	 *
	 * @return SS_HTTPRequest The updated request
	 */
	public function setURL($url) {
		$this->url = $url;

		//Normalize URL if its relative (strictly speaking), or has leading slashes
		if(Director::is_relative_url($url) || substr($url, 0, 1) == '/') {
			$this->url = preg_replace('|/+|', '/', trim($this->url, '/'));
		}

		$this->urlParts = $this->url ? preg_split('|/+|', $this->url) : array();

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isGet() {
		return $this->method == 'GET';
	}

	/**
	 * @return bool
	 */
	public function isPost() {
		return $this->method == 'POST';
	}

	/**
	 * @return bool
	 */
	public function isPut() {
		return $this->method == 'PUT';
	}

	/**
	 * @return bool
	 */
	public function isDelete() {
		return $this->method == 'DELETE';
	}

	/**
	 * @return bool
	 */
	public function isHead() {
		return $this->method == 'HEAD';
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
	 * @return array
	 */
	public function filesVars() {
		return $this->filesVars;
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
	 * Gets a files var by name.
	 *
	 * @param $name
	 * @return mixed
	 */
	public function filesVar($name) {
		if(isset($this->filesVars[$name])) return $this->filesVars[$name];
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

	public function getURLParts() {
		return $this->urlParts;
	}

	/**
	 * Gets the remaining URL that has not been routed.
	 *
	 * @return string
	 */
	public function getRemainingURL() {
		return implode('/', $this->urlParts);
	}

	/**
	 * Returns whether or not the entire URL has been handled.
	 *
	 * @return bool
	 */
	public function isAllRouted() {
		return count($this->getUrlParts()) <= $this->getUnshiftedButParsed();
	}

	/**
	 * Returns a map of all parameters included in the request.
	 *
	 * @return array
	 */
	public function getParams() {
		return array_merge($this->getRouteParams(), $this->getMatchedParams());
	}

	/**
	 * Gets a parameter by name, either from a matched parameter or one included in the route
	 * definition.
	 *
	 * @param string $name the parameter name
	 * @return string
	 */
	public function getParam($name) {
		$params = $this->getParams();

		if(isset($params[$name])) {
			return $params[$name];
		}
	}

	/**
	 * Gets a map of all parameters that were matched in the URL.
	 *
	 * @return array
	 */
	public function getMatchedParams() {
		return $this->matchedParams;
	}

	/**
	 * Gets a parameter by name that was matched in the URL.
	 *
	 * @param string $name the parameter name
	 * @return string
	 */
	public function getMatchedParam($name) {
		if(isset($this->matchedParams[$name])) return $this->matchedParams[$name];
	}

	/**
	 * Gets a map of the parameters that were matched by the most recent route match.
	 *
	 * @return array
	 */
	public function getLatestParams() {
		return $this->latestParams;
	}

	/**
	 * Gets a parameter by name that was matched in the most recent route match.
	 *
	 * @param string $name the parameter name
	 * @return string
	 */
	public function getLatestParam($name) {
		if(isset($this->latestParams[$name])) return $this->latestParams[$name];
	}

	/**
	 * Gets a map of parameters that were include in the route definition.
	 *
	 * @return array
	 */
	public function getRouteParams() {
		return $this->routeParams;
	}

	/**
	 * Gets a parameter by name that was included in the route definition.
	 *
	 * @param string $name the parameter name
	 * @return mixed
	 */
	public function getRouteParam($name) {
		if(isset($this->routeParams[$name])) return $this->routeParams[$name];
	}

	/**
	 * Sets the parameters that were matched in the route definition that was matched.
	 *
	 * @param array $params
	 * @return $this
	 */
	public function setRouteParams(array $params) {
		$this->routeParams = $params;
		return $this;
	}

	/**
	 * Shifts one or more parts off the start of the URL.
	 *
	 * @param int $count
	 * @return array|string
	 */
	public function shift($count = 1) {
		if($count == 1) {
			return array_shift($this->urlParts);
		} else {
			$result = array();
			$count = min($count, count($this->urlParts));

			for($i = 0; $i < $count; $i++) {
				$result[] = array_shift($this->urlParts);
			}

			return $result;
		}
	}

	/**
	 * Shifts all parameter values down a space.
	 *
	 * @return string
	 */
	public function shiftParams() {
		$keys   = array_keys($this->getMatchedParams());
		$values = array_values($this->getMatchedParams());
		$value  = array_shift($values);

		// push additional unparsed URL parts onto the parameter stack
		if(array_key_exists($this->getUnshiftedButParsed(), $this->urlParts)) {
			$values[] = $this->urlParts[$this->getUnshiftedButParsed()];
		}

		foreach($keys as $position => $key) {
			$this->matchedParams[$key] = isset($values[$position]) ? $values[$position] : null;
		}

		return $value;
	}

	/**
	 * Pushes an array of named parameters onto the request.
	 *
	 * @param array $params
	 */
	public function pushParams(array $params) {
		$this->latestParams = $params;

		foreach($params as $k => $v) {
			if($v || !isset($this->matchedParams[$k])) $this->matchedParams[$k] = $v;
		}
	}

	/**
	 * @return int
	 */
	public function getUnshiftedButParsed() {
		return $this->unshiftedButParsed;
	}

	/**
	 * @param int $count
	 * @return $this
	 */
	public function setUnshiftedButParsed($count) {
		$this->unshiftedButParsed = $count;
		return $this;
	}

	/**
	 * Returns true if this request an ajax request,
	 * based on custom HTTP ajax added by common JavaScript libraries,
	 * or based on an explicit "ajax" request parameter.
	 * 
	 * @return boolean
	 */
	public function isAJAX() {
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
		$response->setHeader("Content-Type", "$mimeType; name=\"" . addslashes($fileName) . "\"");
		$response->setHeader("Content-disposition", "attachment; filename=" . addslashes($fileName));
		$response->setHeader("Content-Length", strlen($fileData));
		$response->setHeader("Pragma", ""); // Necessary because IE has issues sending files over SSL
		
		if(strstr($_SERVER["HTTP_USER_AGENT"],"MSIE") == true) {
			$response->setHeader('Cache-Control', 'max-age=3, must-revalidate'); // Workaround for IE6 and 7
		}
		
		return $response;
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
	public function getAcceptMimeTypes($includeQuality = false) {
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
	public function getMethod() {
		return $this->method;
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

	/**
	 * @deprecated 3.2 Use {@link getParams()}
	 */
	public function allParams() {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->getParams()');
		return $this->getParams();
	}

	/**
	 * @deprecated 3.2 Use {@link getParam()}
	 */
	public function param($name) {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->getParam()');
		return $this->getParam($name);
	}

	/**
	 * @deprecated 3.2 Use {@link getParams()}
	 */
	public function params() {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->getParams()');
		return $this->getParams();
	}

	/**
	 * @deprecated 3.2 Use {@link getLatestParams()}
	 */
	public function latestParams() {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->getLatestParams()');
		return $this->getLatestParams();
	}

	/**
	 * @deprecated 3.2 Use {@link getLatestParam()}
	 */
	public function latestParam($name) {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->getLatestParam()');
		return $this->getLatestParam($name);
	}

	/**
	 * @deprecated 3.2 Use {@link shiftParams()}
	 */
	public function shiftAllParams() {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->shiftParams()');
		return $this->shiftParams();
	}

	/**
	 * @deprecated 3.2 Use {@link isAllRouted()}
	 */
	public function allParsed() {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->isAllRouted()');
		return $this->isAllRouted();
	}

	/**
	 * @deprecated 3.2 Use {@link getRemainingUrl()}
	 */
	public function remaining() {
		Deprecation::notice('3.2.0', 'Use SS_HTTPRequest->getRemainingUrl()');
		return $this->getRemainingUrl();
	}

	/**
	 * @deprecated 3.2 Use {@link getMethod()}.
	 */
	public function httpMethod() {
		return $this->getMethod();
	}

}
