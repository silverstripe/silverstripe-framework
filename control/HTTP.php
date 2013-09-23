<?php

/**
 * A class with HTTP-related helpers.
 * Like Debug, this is more a bundle of methods than a class ;-)
 * 
 * @package framework
 * @subpackage misc
 */
class HTTP {

	/**
	 * @var int $cache_age
	 */
	protected static $cache_age = 0;

	/**
	 * @var timestamp $modification_date
	 */
	protected static $modification_date = null;

	/**
	 * @var string $etag
	 */
	protected static $etag = null;

	/**
	 * Turns a local system filename into a URL by comparing it to the script 
	 * filename.
	 *
	 * @param string
	 */
	public static function filename2url($filename) {
		$slashPos = -1;

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
	 * @return The content with all links rewritten as per the logic specified in $code
	 */
	public static function urlRewriter($content, $code) {
		if(!is_callable($code)) {
			Deprecation::notice(3.1, 'HTTP::urlRewriter expects a callable as the second parameter');
		}
		
		// Replace attributes
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
		$ext = File::get_file_extension($filename);
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
	 */
	public static function set_cache_age($age) {
		self::$cache_age = $age;
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
		self::$etag = $etag;
	}

	/**
	 * Add the appropriate caching headers to the response, including If-Modified-Since / 304 handling.
	 *
	 * @param SS_HTTPResponse The SS_HTTPResponse object to augment.  Omitted the argument or passing a string is
	 *                            deprecated; in these cases, the headers are output directly.
	 */
	public static function add_cache_headers($body = null) {
		// Validate argument
		if($body && !($body instanceof SS_HTTPResponse)) {
			user_error("HTTP::add_cache_headers() must be passed an SS_HTTPResponse object", E_USER_WARNING);
			$body = null;
		}

		// Development sites have frequently changing templates; this can get stuffed up by the code
		// below.
		if(Director::isDev()) return;
		
		// The headers have been sent and we don't have an SS_HTTPResponse object to attach things to; no point in
		// us trying.
		if(headers_sent() && !$body) return;

		// Popuplate $responseHeaders with all the headers that we want to build
		$responseHeaders = array();
		if(function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			if(isset($requestHeaders['X-Requested-With']) && $requestHeaders['X-Requested-With']=='XMLHttpRequest') {
				self::$cache_age = 0;
			}
			// bdc: now we must check for DUMB IE6:
			if(isset($requestHeaders['x-requested-with']) && $requestHeaders['x-requested-with']=='XMLHttpRequest') {
				self::$cache_age = 0;
			}
		}

		if(self::$cache_age > 0) {
			$responseHeaders["Cache-Control"] = "max-age=" . self::$cache_age . ", must-revalidate, no-transform";
			$responseHeaders["Pragma"] = "";

			// To do: User-Agent should only be added in situations where you *are* actually
			// varying according to user-agent.
			$responseHeaders['Vary'] = 'Cookie, X-Forwarded-Protocol, User-Agent, Accept';
		}
		else {
			if($body) {
				// Grab header for checking. Unfortunately HTTPRequest uses a mistyped variant.
				$contentDisposition = $body->getHeader('Content-disposition');
				if (!$contentDisposition) $contentDisposition = $body->getHeader('Content-Disposition');
			}

			if(
				$body &&
				Director::is_https() &&
				strstr($_SERVER["HTTP_USER_AGENT"], 'MSIE')==true &&
				strstr($contentDisposition, 'attachment;')==true
			) {
				// IE6-IE8 have problems saving files when https and no-cache are used
				// (http://support.microsoft.com/kb/323308)
				// Note: this is also fixable by ticking "Do not save encrypted pages to disk" in advanced options.
				$responseHeaders["Cache-Control"] = "max-age=3, must-revalidate, no-transform";
				$responseHeaders["Pragma"] = "";
			} else {
				$responseHeaders["Cache-Control"] = "no-cache, max-age=0, must-revalidate, no-transform";
			}
		}

		if(self::$modification_date && self::$cache_age > 0) {
			$responseHeaders["Last-Modified"] = self::gmt_date(self::$modification_date);

			// Chrome ignores Varies when redirecting back (http://code.google.com/p/chromium/issues/detail?id=79758)
			// which means that if you log out, you get redirected back to a page which Chrome then checks against 
			// last-modified (which passes, getting a 304)
			// when it shouldn't be trying to use that page at all because it's the "logged in" version.
			// By also using and etag that includes both the modification date and all the varies 
			// values which we also check against we can catch this and not return a 304
			$etagParts = array(self::$modification_date, serialize($_COOKIE));
			if (isset($_SERVER['HTTP_X_FORWARDED_PROTOCOL'])) $etagParts[] = $_SERVER['HTTP_X_FORWARDED_PROTOCOL'];
			if (isset($_SERVER['HTTP_USER_AGENT'])) $etagParts[] = $_SERVER['HTTP_USER_AGENT'];
			if (isset($_SERVER['HTTP_ACCEPT'])) $etagParts[] = $_SERVER['HTTP_ACCEPT'];

			$etag = sha1(implode(':', $etagParts));
			$responseHeaders["ETag"] = $etag;

			// 304 response detection
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				$ifModifiedSince = strtotime(stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']));

				// As above, only 304 if the last request had all the same varies values
				// (or the etag isn't passed as part of the request - but with chrome it always is)
				$matchesEtag = !isset($_SERVER['HTTP_IF_NONE_MATCH']) || $_SERVER['HTTP_IF_NONE_MATCH'] == $etag;

				if($ifModifiedSince >= self::$modification_date && $matchesEtag) {
					if($body) {
						$body->setStatusCode(304);
						$body->setBody('');
					} else {
						header('HTTP/1.0 304 Not Modified');
						die();
					}
				}
			}

			$expires = time() + self::$cache_age;
			$responseHeaders["Expires"] = self::gmt_date($expires);
		}

		if(self::$etag) {
			$responseHeaders['ETag'] = self::$etag;
		}
		
		// Now that we've generated them, either output them or attach them to the SS_HTTPResponse as appropriate
		foreach($responseHeaders as $k => $v) {
			if($body) $body->addHeader($k, $v);
			else if(!headers_sent()) header("$k: $v");
		}
	}


	/**
	 * Return an {@link http://www.faqs.org/rfcs/rfc2822 RFC 2822} date in the
	 * GMT timezone (a timestamp is always in GMT: the number of seconds
	 * since January 1 1970 00:00:00 GMT)
	 */
	public static function gmt_date($timestamp) {
		return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
	}
	
	/* 
	 * Return static variable cache_age in second
	 */
	public static function get_cache_age() {
		return self::$cache_age;
	}

}


