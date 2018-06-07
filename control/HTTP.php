<?php

/**
 * A class with HTTP-related helpers.
 * Like Debug, this is more a bundle of methods than a class ;-)
 *
 * @package framework
 * @subpackage misc
 * @see https://docs.silverstripe.org/en/developer_guides/performance/http_cache_headers/
 */
class HTTP {

	/**
	 * @var int $cache_age
	 */
	protected static $cache_age = 0;

	/**
	 * @var int $modification_date
	 */
	protected static $modification_date = null;

	/**
	 * @var string $etag
	 */
	protected static $etag = null;

	/**
	 * @config
	 */
	private static $cache_ajax_requests = true;

	/**
	 * @config
	 * @var bool
	 */
	private static $disable_http_cache = false;

	/**
	 * Mapping of extension to mime types
	 *
	 * @var array
	 * @config
	 */
	private static $MimeTypes = array();

	/**
	 * List of names to add to the Cache-Control header.
	 *
	 * @see HTTPCacheControl::__construct()
	 * @config
	 * @var array Keys are cache control names, values are boolean flags
	 */
	private static $cache_control = array();

	/**
	 * Vary string; A comma separated list of var header names
	 *
	 * @config
	 * @var string|null
	 */
	private static $vary = null;

	/**
	 * Turns a local system filename into a URL by comparing it to the script
	 * filename.
	 *
	 * @param string
	 * @return string
	 */
	public static function filename2url($filename) {
		$slashPos = -1;

		$commonLength = null;
		while(($slashPos = strpos($filename, "/", $slashPos+1)) !== false) {
			if(substr($filename, 0, $slashPos) == substr($_SERVER['SCRIPT_FILENAME'],0,$slashPos)) {
				$commonLength = $slashPos;
			} else {
				break;
			}
		}

		$urlBase = substr(
			$_SERVER['PHP_SELF'],
			0,
			-(strlen($_SERVER['SCRIPT_FILENAME']) - $commonLength)
		);

		$url = $urlBase . substr($filename, $commonLength);
		$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";

		// Count the number of extra folders the script is in.
		// $prefix = str_repeat("../", substr_count(substr($_SERVER[SCRIPT_FILENAME],$commonBaseLength)));

		return "$protocol://". $_SERVER['HTTP_HOST'] . $url;
	}

	/**
	 * Turn all relative URLs in the content to absolute URLs
	 *
	 * @param string $html
	 * @return string
	 */
	public static function absoluteURLs($html) {
		$html = str_replace('$CurrentPageURL', $_SERVER['REQUEST_URI'], $html);
		return HTTP::urlRewriter($html, function($url) {
			//no need to rewrite, if uri has a protocol (determined here by existence of reserved URI character ":")
			if(preg_match('/^\w+:/', $url)){
				return $url;
			}
			return Director::absoluteURL($url, true);
		});
	}

	/**
	 * Rewrite all the URLs in the given content, evaluating the given string as PHP code.
	 *
	 * Put $URL where you want the URL to appear, however, you can't embed $URL in strings
	 * Some example code:
	 * <ul>
	 * <li><code>'"../../" . $URL'</code></li>
	 * <li><code>'myRewriter($URL)'</code></li>
	 * <li><code>'(substr($URL,0,1)=="/") ? "../" . substr($URL,1) : $URL'</code></li>
	 * </ul>
	 *
	 * As of 3.2 $code should be a callable which takes a single parameter and returns
	 * the rewritten URL. e.g.
	 *
	 * <code>
	 * function($url) {
	 *		return Director::absoluteURL($url, true);
	 * }
	 * </code>
	 *
	 * @param string $content The HTML to search for links to rewrite
	 * @param string|callable $code Either a string that can evaluate to an expression
	 * to rewrite links (depreciated), or a callable that takes a single
	 * parameter and returns the rewritten URL
	 * @return string The content with all links rewritten as per the logic specified in $code
	 */
	public static function urlRewriter($content, $code) {
		if(!is_callable($code)) {
			Deprecation::notice('4.0', 'HTTP::urlRewriter expects a callable as the second parameter');
		}

		// Replace attributes
		$regExps = array();
		$attribs = array("src","background","a" => "href","link" => "href", "base" => "href");
		foreach($attribs as $tag => $attrib) {
			if(!is_numeric($tag)) $tagPrefix = "$tag ";
			else $tagPrefix = "";

			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *\")([^\"]*)(\")/i";
			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *')([^']*)(')/i";
			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *)([^\"' ]*)( )/i";
		}
		// Replace css styles
		// @todo - http://www.css3.info/preview/multiple-backgrounds/
		$styles = array('background-image', 'background', 'list-style-image', 'list-style', 'content');
		foreach($styles as $style) {
			$regExps[] = "/($style:[^;]*url *\(\")([^\"]+)(\"\))/i";
			$regExps[] = "/($style:[^;]*url *\(')([^']+)('\))/i";
			$regExps[] = "/($style:[^;]*url *\()([^\"\)')]+)(\))/i";
		}

		// Callback for regexp replacement
		$callback = function($matches) use($code) {
			if(is_callable($code)) {
				$rewritten = $code($matches[2]);
			} else {
				// Expose the $URL variable to be used by the $code expression
				$URL = $matches[2];
				array($URL); // Ensure $URL is available to scope of below code
				$rewritten = eval("return ($code);");
			}
			return $matches[1] . $rewritten . $matches[3];
		};

		// Execute each expression
		foreach($regExps as $regExp) {
			$content = preg_replace_callback($regExp, $callback, $content);
		}

		return $content;
	}

	/**
	 * Will try to include a GET parameter for an existing URL,
	 * preserving existing parameters and fragments.
	 * If no URL is given, falls back to $_SERVER['REQUEST_URI'].
	 * Uses parse_url() to dissect the URL, and http_build_query() to reconstruct it
	 * with the additional parameter. Converts any '&' (ampersand)
	 * URL parameter separators to the more XHTML compliant '&amp;'.
	 *
	 * CAUTION: If the URL is determined to be relative,
	 * it is prepended with Director::absoluteBaseURL().
	 * This method will always return an absolute URL because
	 * Director::makeRelative() can lead to inconsistent results.
	 *
	 * @param String $varname
	 * @param String $varvalue
	 * @param String $currentURL Relative or absolute URL (Optional).
	 * @param String $separator Separator for http_build_query(). (Optional).
	 * @return String Absolute URL
	 */
	public static function setGetVar($varname, $varvalue, $currentURL = null, $separator = '&amp;') {
		$uri = $currentURL ? $currentURL : Director::makeRelative($_SERVER['REQUEST_URI']);

		$isRelative = false;
		// We need absolute URLs for parse_url()
		if(Director::is_relative_url($uri)) {
			$uri = Director::absoluteBaseURL() . $uri;
			$isRelative = true;
		}

		// try to parse uri
		$parts = parse_url($uri);
		if(!$parts) {
			throw new InvalidArgumentException("Can't parse URL: " . $uri);
		}

		// Parse params and add new variable
		$params = array();
		if(isset($parts['query'])) parse_str($parts['query'], $params);
		$params[$varname] = $varvalue;

		// Generate URI segments and formatting
		$scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
		$user = (isset($parts['user']) && $parts['user'] != '')  ? $parts['user'] : '';

		if($user != '') {
			// format in either user:pass@host.com or user@host.com
			$user .= (isset($parts['pass']) && $parts['pass'] != '') ? ':' . $parts['pass'] . '@' : '@';
		}

		$host = (isset($parts['host'])) ? $parts['host'] : '';
		$port = (isset($parts['port']) && $parts['port'] != '') ? ':'.$parts['port'] : '';
		$path = (isset($parts['path']) && $parts['path'] != '') ? $parts['path'] : '';

		// handle URL params which are existing / new
		$params = ($params) ?  '?' . http_build_query($params, null, $separator) : '';

		// keep fragments (anchors) intact.
		$fragment = (isset($parts['fragment']) && $parts['fragment'] != '') ?  '#'.$parts['fragment'] : '';

		// Recompile URI segments
		$newUri =  $scheme . '://' . $user . $host . $port . $path . $params . $fragment;

		if($isRelative) return Director::makeRelative($newUri);

		return $newUri;
	}

	public static function RAW_setGetVar($varname, $varvalue, $currentURL = null) {
		$url = self::setGetVar($varname, $varvalue, $currentURL);
		return Convert::xml2raw($url);
	}

	/**
	 * Search for all tags with a specific attribute, then return the value of that attribute in a flat array.
	 *
	 * @param string $content
	 * @param array $attributes an array of tags to attributes, for example "[a] => 'href', [div] => 'id'"
	 * @return array
	 */
	public static function findByTagAndAttribute($content, $attributes) {
		$regexes = array();

		foreach($attributes as $tag => $attribute) {
			$regexes[] = "/<{$tag} [^>]*$attribute *= *([\"'])(.*?)\\1[^>]*>/i";
			$regexes[] = "/<{$tag} [^>]*$attribute *= *([^ \"'>]+)/i";
		}

		$result = array();

		if($regexes) foreach($regexes as $regex) {
			if(preg_match_all($regex, $content, $matches)) {
				$result = array_merge_recursive($result, (isset($matches[2]) ? $matches[2] : $matches[1]));
			}
		}

		return count($result) ? $result : null;
	}

	public static function getLinksIn($content) {
		return self::findByTagAndAttribute($content, array("a" => "href"));
	}

	public static function getImagesIn($content) {
		return self::findByTagAndAttribute($content, array("img" => "src"));
	}

	/**
	 * Get the MIME type based on a file's extension.
	 *
	 * If the finfo class exists in PHP, and the file actually exists, then use that
	 * extension, otherwise fallback to a list of commonly known MIME types.
	 *
	 * @uses finfo
	 * @param string $filename Relative path to filename from project root, e.g. "mysite/tests/file.csv"
	 * @return string MIME type
	 */
	public static function get_mime_type($filename) {
		// If the finfo module is compiled into PHP, use it.
		$path = BASE_PATH . DIRECTORY_SEPARATOR . $filename;
		if(class_exists('finfo') && file_exists($path)) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			return $finfo->file($path);
		}

		// Fallback to use the list from the HTTP.yml configuration and rely on the file extension
		// to get the file mime-type
		$ext = strtolower(File::get_file_extension($filename));
		// Get the mime-types
		$mimeTypes = Config::inst()->get('HTTP', 'MimeTypes');

		// The mime type doesn't exist
		if(!isset($mimeTypes[$ext])) {
			return 'application/unknown';
		}

		return $mimeTypes[$ext];
	}

	/**
	 * Set the maximum age of this page in web caches, in seconds
	 *
	 * @param int $age
	 */
	public static function set_cache_age($age) {
		self::$cache_age = $age;
		HTTPCacheControl::singleton()->setMaxAge($age);
	}

	public static function register_modification_date($dateString) {
		$timestamp = strtotime($dateString);
		if($timestamp > self::$modification_date)
			self::$modification_date = $timestamp;
	}

	public static function register_modification_timestamp($timestamp) {
		if($timestamp > self::$modification_date)
			self::$modification_date = $timestamp;
	}

	public static function register_etag($etag) {
		if (0 !== strpos($etag, '"')) {
			$etag = sprintf('"%s"', $etag);
		}
		self::$etag = $etag;
	}

	/**
	 * Add the appropriate caching headers to the response, including If-Modified-Since / 304 handling.
	 * Note that setting HTTP::$cache_age will overrule any cache headers set by PHP's
	 * session_cache_limiter functionality. It is your responsibility to ensure only cacheable data
	 * is in fact cached, and HTTP::$cache_age isn't set when the HTTP body contains session-specific content.
	 *
	 * @param SS_HTTPResponse $body The SS_HTTPResponse object to augment.  Omitted the argument or passing a string is
	 *                            deprecated; in these cases, the headers are output directly.
	 */
	public static function add_cache_headers($body = null) {
		// Validate argument
		if($body && !($body instanceof SS_HTTPResponse)) {
			user_error("HTTP::add_cache_headers() must be passed an SS_HTTPResponse object", E_USER_WARNING);
			$body = null;
		}

		// The headers have been sent and we don't have an SS_HTTPResponse object to attach things to; no point in
		// us trying.
		if(headers_sent() && !$body) {
			return;
		}

		// Warn if already assigned cache-control headers
		if ($body && $body->getHeader('Cache-Control')) {
			trigger_error(
				'Cache-Control header has already been set. '
				. 'Please use HTTPCacheControl API to set caching options instead.',
				E_USER_WARNING
			);
			return;
		}

		$config = Config::inst()->forClass(__CLASS__);

		// Get current cache control state
		$cacheControl = HTTPCacheControl::singleton();

		// if http caching is disabled by config, disable it - used on dev environments due to frequently changing
		// templates and other data. will be overridden by forced publicCache() or privateCache() calls
		if ($config->get('disable_http_cache')) {
			$cacheControl->disableCache();
		}

		// Populate $responseHeaders with all the headers that we want to build
		$responseHeaders = array();

		// if no caching ajax requests, disable ajax if is ajax request
		if (!$config->get('cache_ajax_requests') && Director::is_ajax()) {
			$cacheControl->disableCache();
		}

		// Errors disable cache (unless some errors are cached intentionally by usercode)
		if ($body && $body->isError()) {
			// Even if publicCache(true) is specfied, errors will be uncachable
			$cacheControl->disableCache(true);
		}

		// If sessions exist we assume that the responses should not be cached by CDNs / proxies as we are
		// likely to be supplying information relevant to the current user only
		if (Session::get_all()) {
			// Don't force in case user code chooses to opt in to public caching
			$cacheControl->privateCache();
		}

		// split the current vary header into it's parts and merge it with the config settings
		// to create a list of unique vary values
		$configVary = $config->get('vary');
		$bodyVary = $body ? $body->getHeader('Vary') : '';
		$vary = self::combineVary($configVary, $bodyVary);
		if ($vary) {
			$responseHeaders['Vary'] = $vary;
		}

		// deal with IE6-IE8 problems with https and no-cache
		$contentDisposition = null;
		if($body) {
			// Grab header for checking. Unfortunately HTTPRequest uses a mistyped variant.
			$contentDisposition = $body->getHeader('Content-Disposition', true);
		}

		if(
			$body &&
			Director::is_https() &&
			isset($_SERVER['HTTP_USER_AGENT']) &&
			strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') == true &&
			strstr($contentDisposition, 'attachment;') == true &&
			($cacheControl->hasDirective('no-cache') || $cacheControl->hasDirective('no-store'))
		) {
			// IE6-IE8 have problems saving files when https and no-cache/no-store are used
			// (http://support.microsoft.com/kb/323308)
			// Note: this is also fixable by ticking "Do not save encrypted pages to disk" in advanced options.
			$cacheControl->privateCache(true);
		}

		if (self::$modification_date) {
			$responseHeaders["Last-Modified"] = self::gmt_date(self::$modification_date);
		}

		// if we can store the cache responses we should generate and send etags
		if (!$cacheControl->hasDirective('no-store')) {
			// Chrome ignores Varies when redirecting back (http://code.google.com/p/chromium/issues/detail?id=79758)
			// which means that if you log out, you get redirected back to a page which Chrome then checks against
			// last-modified (which passes, getting a 304)
			// when it shouldn't be trying to use that page at all because it's the "logged in" version.
			// By also using and etag that includes both the modification date and all the varies
			// values which we also check against we can catch this and not return a 304
			$etag = self::generateETag($body);
			if ($etag) {
				$responseHeaders['ETag'] = $etag;

				// 304 response detection
				if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
					// As above, only 304 if the last request had all the same varies values
					// (or the etag isn't passed as part of the request - but with chrome it always is)
					$matchesEtag = $_SERVER['HTTP_IF_NONE_MATCH'] == $etag;

					if ($matchesEtag) {
						if ($body) {
							$body->setStatusCode(304);
							$body->setBody('');
						} else {
							// this is wrong, we need to send the same vary headers and so on
							header('HTTP/1.0 304 Not Modified');
							die();
						}
					}
				}
			}
		}

		if ($cacheControl->hasDirective('max-age')) {
			$expires = time() + $cacheControl->getDirective('max-age');
			$responseHeaders["Expires"] = self::gmt_date($expires);
		}

		// etag needs to be a quoted string according to HTTP spec
		if (!empty($responseHeaders['ETag']) && 0 !== strpos($responseHeaders['ETag'], '"')) {
			$responseHeaders['ETag'] = sprintf('"%s"', $responseHeaders['ETag']);
		}

		// Merge with cache control headers
		$responseHeaders = array_merge($responseHeaders, $cacheControl->generateHeaders());

		// Now that we've generated them, either output them or attach them to the SS_HTTPResponse as appropriate
		foreach($responseHeaders as $k => $v) {
			if($body) {
				// Set the header now if it's not already set.
				if ($body->getHeader($k) === null) {
					$body->addHeader($k, $v);
				}
			} elseif(!headers_sent()) {
				header("$k: $v");
			}
		}
	}

	/**
	 * @param SS_HTTPResponse|string $response
	 *
	 * @return string|false
	 */
	protected static function generateETag($response)
	{
		if (self::$etag) {
			return self::$etag;
		}
		if ($response instanceof SS_HTTPResponse) {
			return $response->getHeader('ETag') ?: sprintf('"%s"', md5($response->getBody()));
		}
		if ($response) {
			return sprintf('"%s"', md5($response));
		}
		return false;
	}

	/**
	 * Return an {@link http://www.faqs.org/rfcs/rfc2822 RFC 2822} date in the
	 * GMT timezone (a timestamp is always in GMT: the number of seconds
	 * since January 1 1970 00:00:00 GMT)
	 *
	 * @param int $timestamp
	 * @return string
	 */
	public static function gmt_date($timestamp) {
		return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
	}

	/**
	 * Return static variable cache_age in second
	 *
	 * @return int
	 */
	public static function get_cache_age() {
		return self::$cache_age;
	}

	/**
	 * Combine vary strings
	 *
	 * @param string $vary,... Each vary as a separate arg
	 * @return string
	 */
	protected static function combineVary($vary)
	{
		$varies = array();
		foreach (func_get_args() as $arg) {
			$argVaries = array_filter(preg_split("/\s*,\s*/", trim($arg)));
			if ($argVaries) {
				$varies = array_merge($varies, $argVaries);
			}
		}
		return implode(', ', array_unique($varies));
	}
}
