<?php
/**
 * RestfulService class allows you to consume various RESTful APIs.
 * Through this you could connect and aggregate data of various web services.
 * For more info visit wiki documentation - http://doc.silverstripe.org/doku.php?id=restfulservice
 *
 * @package framework
 * @subpackage integration
 */
class RestfulService extends ViewableData implements Flushable {

	protected $baseURL;
	protected $queryString;
	protected $errorTag;
	protected $checkErrors;
	protected $cache_expire;
	protected $authUsername, $authPassword;
	protected $customHeaders = array();
	protected $proxy;

	/**
	 * @config
	 * @var array
	 */
	private static $default_proxy;

	/**
	 * @config
	 * @var array
	 */
	private static $default_curl_options = array();

	/**
	 * @config
	 * @var bool Flushes caches if set to true. This is set by {@link flush()}
	 */
	private static $flush = false;

	/**
	 * Triggered early in the request when someone requests a flush.
	 */
	public static function flush() {
		self::$flush = true;
	}

	/**
	 * set a curl option that will be applied to all requests as default
	 * {@see http://php.net/manual/en/function.curl-setopt.php#refsect1-function.curl-setopt-parameters}
	 *
	 * @deprecated 4.0 Use the "RestfulService.default_curl_options" config setting instead
	 * @param int $option The cURL opt Constant
	 * @param mixed $value The cURL opt value
	 */
	public static function set_default_curl_option($option, $value) {
		Deprecation::notice('4.0', 'Use the "RestfulService.default_curl_options" config setting instead');
		Config::inst()->update('RestfulService', 'default_curl_options', array($option => $value));
	}

	/**
	 * set many defauly curl options at once
	 *
	 * @deprecated 4.0 Use the "RestfulService.default_curl_options" config setting instead
	 */
	public static function set_default_curl_options($optionArray) {
		Deprecation::notice('4.0', 'Use the "RestfulService.default_curl_options" config setting instead');
		Config::inst()->update('RestfulService', 'default_curl_options', $optionArray);
	}

	/**
	 * Sets default proxy settings for outbound RestfulService connections
	 *
	 * @param string $proxy The URL of the proxy to use.
	 * @param int $port Proxy port
	 * @param string $user The proxy auth user name
	 * @param string $password The proxy auth password
	 * @param boolean $socks Set true to use socks5 proxy instead of http
	 * @deprecated 4.0 Use the "RestfulService.default_curl_options" config setting instead,
	 *             with direct reference to the CURL_* options
	 */
	public static function set_default_proxy($proxy, $port = 80, $user = "", $password = "", $socks = false) {
		Deprecation::notice(
			'4.0',
			'Use the "RestfulService.default_curl_options" config setting instead, '
				. 'with direct reference to the CURL_* options'
		);
		config::inst()->update('RestfulService', 'default_proxy', array(
			CURLOPT_PROXY => $proxy,
			CURLOPT_PROXYUSERPWD => "{$user}:{$password}",
			CURLOPT_PROXYPORT => $port,
			CURLOPT_PROXYTYPE => ($socks ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP)
		));
	}

	/**
 	* Creates a new restful service.
 	* @param string $base Base URL of the web service eg: api.example.com
 	* @param int $expiry Set the cache expiry interva. Defaults to 1 hour (3600 seconds)
 	*/
	public function __construct($base, $expiry=3600){
		$this->baseURL = $base;
		$this->cache_expire = $expiry;
		parent::__construct();
		$this->proxy = $this->config()->default_proxy;
	}

	/**
 	* Sets the Query string parameters to send a request.
 	* @param array $params An array passed with necessary parameters.
 	*/
	public function setQueryString($params=NULL){
		$this->queryString = http_build_query($params,'','&');
	}

	/**
	 * Set proxy settings for this RestfulService instance
	 *
	 * @param string $proxy The URL of the proxy to use.
	 * @param int $port Proxy port
	 * @param string $user The proxy auth user name
	 * @param string $password The proxy auth password
	 * @param boolean $socks Set true to use socks5 proxy instead of http
	 */
	public function setProxy($proxy, $port = 80, $user = "", $password = "", $socks = false) {
		$this->proxy = array(
			CURLOPT_PROXY => $proxy,
			CURLOPT_PROXYUSERPWD => "{$user}:{$password}",
			CURLOPT_PROXYPORT => $port,
			CURLOPT_PROXYTYPE => ($socks ? CURLPROXY_SOCKS5 : CURLPROXY_HTTP)
		);
	}

	/**
	 * Set basic authentication
	 */
	public function basicAuth($username, $password) {
		$this->authUsername = $username;
		$this->authPassword = $password;
	}

	/**
	 * Set a custom HTTP header
	 */
	public function httpHeader($header) {
		$this->customHeaders[] = $header;
	}

	/**
	 * @deprecated since version 4.0
	 */
	protected function constructURL(){
		Deprecation::notice('4.0', 'constructURL is deprecated, please use `getAbsoluteRequestURL` instead');
		return Controller::join_links($this->baseURL, '?' . $this->queryString);
	}

	/**
	 * Makes a request to the RESTful server, and return a {@link RestfulService_Response} object for parsing of the
	 * result.
	 *
	 * @todo Better POST, PUT, DELETE, and HEAD support
	 * @todo Caching of requests - probably only GET and HEAD requestst
	 * @todo JSON support in RestfulService_Response
	 * @todo Pass the response headers to RestfulService_Response
	 *
	 * This is a replacement of {@link connect()}.
	 *
	 * @return RestfulService_Response - If curl request produces error, the returned response's status code will
	 *                                   be 500
	 */
	public function request($subURL = '', $method = "GET", $data = null, $headers = null, $curlOptions = array()) {

		$url = $this->getAbsoluteRequestURL($subURL);
		$method = strtoupper($method);

		assert(in_array($method, array('GET','POST','PUT','DELETE','HEAD','OPTIONS','PATCH')));

		$cache_path = $this->getCachePath(array(
			$url,
			$method,
			$data,
			array_merge((array)$this->customHeaders, (array)$headers),
			$curlOptions + (array)$this->config()->default_curl_options,
			$this->getBasicAuthString()
		));

		// Check for unexpired cached feed (unless flush is set)
		//assume any cache_expire that is 0 or less means that we dont want to
		// cache
		if($this->cache_expire > 0 && !self::$flush
				&& @file_exists($cache_path)
				&& @filemtime($cache_path) + $this->cache_expire > time()) {

			$store = file_get_contents($cache_path);
			$response = unserialize($store);

		} else {
			$response = $this->curlRequest($url, $method, $data, $headers, $curlOptions);

			if(!$response->isError()) {
				// Serialise response object and write to cache
				$store = serialize($response);
				file_put_contents($cache_path, $store);
			}
			else {
				// In case of curl or/and http indicate error, populate response's cachedBody property
				// with cached response body with the cache file exists
				if (@file_exists($cache_path)) {
					$store = file_get_contents($cache_path);
					$cachedResponse = unserialize($store);

					$response->setCachedResponse($cachedResponse);
				}
				else {
					$response->setCachedResponse(false);
				}
			}
		}

		return $response;
	}

	/**
	 * Actually performs a remote service request using curl. This is used by
	 * {@link RestfulService::request()}.
	 *
	 * @param  string $url
	 * @param  string $method
	 * @param  array $data
	 * @param  array $headers
	 * @param  array $curlOptions
	 * @return RestfulService_Response
	 */
	public function curlRequest($url, $method, $data = null, $headers = null, $curlOptions = array()) {
		$ch        = curl_init();
		$timeout   = 5;
		$sapphireInfo = new SapphireInfo();
		$useragent = 'SilverStripe/' . $sapphireInfo->Version();
		$curlOptions = $curlOptions + (array)$this->config()->default_curl_options;

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		if(!ini_get('open_basedir')) curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);


		// Write headers to a temporary file
		$headerfd = tmpfile();
		curl_setopt($ch, CURLOPT_WRITEHEADER, $headerfd);

		// Add headers
		if($this->customHeaders) {
			$headers = array_merge((array)$this->customHeaders, (array)$headers);
		}

		if($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// Add authentication
		if($this->authUsername) curl_setopt($ch, CURLOPT_USERPWD, $this->getBasicAuthString());

		// Add fields to POST and PUT requests
		if($method == 'POST' || $method == 'PATCH') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} elseif($method == 'PUT') {
			$put = fopen("php://temp", 'r+');
			fwrite($put, $data);
			fseek($put, 0);

			curl_setopt($ch, CURLOPT_PUT, 1);
			curl_setopt($ch, CURLOPT_INFILE, $put);
			curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
		}

		// Apply proxy settings
		if(is_array($this->proxy)) {
			curl_setopt_array($ch, $this->proxy);
		}

		// Set any custom options passed to the request() function
		curl_setopt_array($ch, $curlOptions);

		// Run request
		$body = curl_exec($ch);

		rewind($headerfd);
		$headers = stream_get_contents($headerfd);
		fclose($headerfd);

		$response = $this->extractResponse($ch, $headers, $body);
		curl_close($ch);

		return $response;
	}

	/**
	 * A function to return the auth string. This helps consistency through the
	 * class but also allows tests to pull it out when generating the expected
	 * cache keys
	 *
	 * @see {self::getCachePath()}
	 * @see {RestfulServiceTest::createFakeCachedResponse()}
	 *
	 * @return string The auth string to be base64 encoded
	 */
	protected function getBasicAuthString() {
		return $this->authUsername . ':' . $this->authPassword;
	}

	/**
	 * Generate a cache key based on any cache data sent. The cache data can be
	 * any type
	 *
	 * @param mixed $cacheData The cache seed for generating the key
	 * @param string the md5 encoded cache seed.
	 */
	protected function generateCacheKey($cacheData) {
		return md5(var_export($cacheData, true));
	}

	/**
	 * Generate the cache path
	 *
	 * This is mainly so that the cache path can be generated in a consistent
	 * way in tests without having to hard code the cachekey generate function
	 * in tests
	 *
	 * @param mixed $cacheData The cache seed {@see self::generateCacheKey}
	 *
	 * @return string The path to the cache file
	 */
	protected function getCachePath($cacheData) {
		return TEMP_FOLDER . "/xmlresponse_" . $this->generateCacheKey($cacheData);
	}

	/**
	 * Extracts the response body and headers from a full curl response
	 *
	 * @param curl_handle $ch The curl handle for the request
	 * @param string $rawResponse The raw response text
	 *
	 * @return RestfulService_Response The response object
	 */
	protected function extractResponse($ch, $rawHeaders, $rawBody) {
		//get the status code
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		//get a curl error if there is one
		$curlError = curl_error($ch);
		//normalise the status code
		if(curl_error($ch) !== '' || $statusCode == 0) $statusCode = 500;
		//parse the headers
		$parts = array_filter(explode("\r\n\r\n", $rawHeaders));
		$lastHeaders = array_pop($parts);
		$headers = $this->parseRawHeaders($lastHeaders);
		//return the response object
		return new RestfulService_Response($rawBody, $statusCode, $headers);
	}

	/**
	 * Takes raw headers and parses them to turn them to an associative array
	 *
	 * Any header that we see more than once is turned into an array.
	 *
	 * This is meant to mimic http_parse_headers {@link http://php.net/manual/en/function.http-parse-headers.php}
	 * thanks to comment #77241 on that page for foundation of this
	 *
	 * @param string $rawHeaders The raw header string
	 * @return array The assosiative array of headers
	 */
	protected function parseRawHeaders($rawHeaders) {
		$headers = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $rawHeaders));
		foreach( $fields as $field ) {
			if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
				$match[1] = preg_replace_callback(
					'/(?<=^|[\x09\x20\x2D])./',
					create_function('$matches', 'return strtoupper($matches[0]);'),
					trim($match[1])
				);
				if( isset($headers[$match[1]]) ) {
					if (!is_array($headers[$match[1]])) {
						$headers[$match[1]] = array($headers[$match[1]]);
					}
					$headers[$match[1]][] = $match[2];
				} else {
					$headers[$match[1]] = trim($match[2]);
				}
			}
		}
		return $headers;
	}


	/**
	 * Returns a full request url
	 * @param string
	 */
	public function getAbsoluteRequestURL($subURL = '') {
		$url = Controller::join_links($this->baseURL, $subURL, '?' . $this->queryString);

		return str_replace(' ', '%20', $url); // Encode spaces
	}

	/**
 	* Gets attributes as an array, of a particular type of element.
 	* Example : <photo id="2636" owner="123" secret="ab128" server="2">
 	* returns id, owner,secret and sever attribute values of all such photo elements.
 	* @param string $xml The source xml to parse, this could be the original response received.
 	* @param string $collection The name of parent node which wraps the elements, if available
 	* @param string $element The element we need to extract the attributes.
 	*/

	public function getAttributes($xml, $collection=NULL, $element=NULL){
		$xml = new SimpleXMLElement($xml);
		$output = new ArrayList();

		if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};

		if($childElements){
			foreach($childElements as $child){
				$data = array();
				foreach($child->attributes() as $key => $value){
					$data["$key"] = Convert::raw2xml($value);
				}
				$output->push(new ArrayData($data));
			}
		}
		return $output;

	}

	/**
 	* Gets an attribute of a particular element.
 	* @param string $xml The source xml to parse, this could be the original response received.
 	* @param string $collection The name of the parent node which wraps the element, if available
 	* @param string $element The element we need to extract the attribute
 	* @param string $attr The name of the attribute
 	*/

	public function getAttribute($xml, $collection=NULL, $element=NULL, $attr){
		$xml = new SimpleXMLElement($xml);
		$attr_value = "";

		if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};

		if($childElements)
			$attr_value = (string) $childElements[$attr];

		return Convert::raw2xml($attr_value);

	}


	/**
 	* Gets set of node values as an array.
 	* When you get to the depth in the hierarchy use node_child_subchild syntax to get the value.
 	* @param string $xml The the source xml to parse, this could be the original response received.
 	* @param string $collection The name of parent node which wraps the elements, if available
 	* @param string $element The element we need to extract the node values.
 	*/

	public function getValues($xml, $collection=NULL, $element=NULL){
		$xml = new SimpleXMLElement($xml);
		$output = new ArrayList();

			$childElements = $xml;
		if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};

		if($childElements){
			foreach($childElements as $child){
				$data = array();
				$this->getRecurseValues($child,$data);
				$output->push(new ArrayData($data));
			}
		}
		return $output;
	}

	protected function getRecurseValues($xml,&$data,$parent=""){
		$conv_value = "";
		$child_count = 0;
		foreach($xml as $key=>$value)
		{
			$child_count++;
			$k = ($parent == "") ? (string)$key : $parent . "_" . (string)$key;
			if($this->getRecurseValues($value,$data,$k) == 0){  // no childern, aka "leaf node"
				$conv_value = Convert::raw2xml($value);
			}
			//Review the fix for similar node names overriding it's predecessor
			if(array_key_exists($k, $data) == true) {
				$data[$k] = $data[$k] . ",". $conv_value;
			}
			else {
				$data[$k] = $conv_value;
			}


		}
		return $child_count;

	}

	/**
 	* Gets a single node value.
 	* @param string $xml The source xml to parse, this could be the original response received.
 	* @param string $collection The name of parent node which wraps the elements, if available
 	* @param string $element The element we need to extract the node value.
 	*/

	public function getValue($xml, $collection=NULL, $element=NULL){
		$xml = new SimpleXMLElement($xml);

		if($collection)
			$childElements = $xml->{$collection};
		if($element)
			$childElements = $xml->{$collection}->{$element};

		if($childElements)
			return Convert::raw2xml($childElements);
	}

	/**
 	* Searches for a node in document tree and returns it value.
 	* @param string $xml source xml to parse, this could be the original response received.
 	* @param string $node Node to search for
 	*/
	public function searchValue($xml, $node=NULL){
		$xml = new SimpleXMLElement($xml);
		$childElements = $xml->xpath($node);

		if($childElements)
			return Convert::raw2xml($childElements[0]);
	}

	/**
 	* Searches for a node in document tree and returns its attributes.
 	* @param string $xml the source xml to parse, this could be the original response received.
 	* @param string $node Node to search for
 	*/
	public function searchAttributes($xml, $node=NULL){
		$xml = new SimpleXMLElement($xml);
		$output = new ArrayList();

		$childElements = $xml->xpath($node);

		if($childElements)
		foreach($childElements as $child){
		$data = array();
			foreach($child->attributes() as $key => $value){
				$data["$key"] = Convert::raw2xml($value);
			}

			$output->push(new ArrayData($data));
		}

		return $output;
	}
}

/**
 * @package framework
 * @subpackage integration
 */
class RestfulService_Response extends SS_HTTPResponse {
	protected $simpleXML;

	/**
	 * @var boolean It should be populated with cached request
	 * when a request referring to this response was unsuccessful
	 */
	protected $cachedResponse = false;

	public function __construct($body, $statusCode = 200, $headers = null) {
		$this->setbody($body);
		$this->setStatusCode($statusCode);
		$this->headers = $headers;
	}

	public function simpleXML() {
		if(!$this->simpleXML) {
			try {
				$this->simpleXML = new SimpleXMLElement($this->body);
			}
			catch(Exception $e) {
				user_error("String could not be parsed as XML. " . $e, E_USER_WARNING);
			}
		}
		return $this->simpleXML;
	}

	/**
	 * get the cached response object. This allows you to access the cached
	 * eaders, not just the cached body.
	 *
	 * @return RestfulSerivice_Response The cached response object
	 */
	public function getCachedResponse() {
		return $this->cachedResponse;
	}

	/**
	 * @return string
	 */
	public function getCachedBody() {
		if ($this->cachedResponse) {
			return $this->cachedResponse->getBody();
		}
		return false;
	}

	/**
	 * @param string
	 * @deprecated since version 4.0
	 */
	public function setCachedBody($content) {
		Deprecation::notice('4.0', 'Setting the response body is now deprecated, set the cached request instead');
		if (!$this->cachedResponse) {
			$this->cachedResponse = new RestfulService_Response($content);
		}
		else {
			$this->cachedResponse->setBody($content);
		}
	}

	/**
	 * @param string
	 */
	public function setCachedResponse($response) {
		$this->cachedResponse = $response;
	}

	/**
	 * Return an array of xpath matches
	 */
	public function xpath($xpath) {
		return $this->simpleXML()->xpath($xpath);
	}

	/**
	 * Return the first xpath match
	 */
	public function xpath_one($xpath) {
		$items = $this->xpath($xpath);
		if (isset($items[0])) {
			return $items[0];
		}
	}
}
