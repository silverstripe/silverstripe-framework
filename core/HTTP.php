<?php

/**
 * @package sapphire
 * @subpackage misc
 */

/**
 * A class with HTTP-related helpers.
 * Like Debug, this is more a bundle of methods than a class ;-)
 * 
 * @package sapphire
 * @subpackage misc
 */
class HTTP {

	static $userName;
	static $password;

	/**
	 * Turns a local system filename into a URL by comparing it to the script filename
	 */
	static function filename2url($filename) {
		$slashPos = -1;
		while(($slashPos = strpos($filename, "/", $slashPos+1)) !== false) {
			if(substr($filename, 0, $slashPos) == substr($_SERVER['SCRIPT_FILENAME'],0,$slashPos)) {
				$commonLength = $slashPos;
			} else {
				break;
			}
		}

		$urlBase = substr($_SERVER['PHP_SELF'], 0, -(strlen($_SERVER['SCRIPT_FILENAME']) - $commonLength));
		$url = $urlBase . substr($filename, $commonLength);
		$protocol = $_SERVER['HTTPS'] ? "https" : "http";
		return "$protocol://". $_SERVER['HTTP_HOST'] . $url;

		// Count the number of extra folders the script is in.
		// $prefix = str_repeat("../", substr_count(substr($_SERVER[SCRIPT_FILENAME],$commonBaseLength)));
	}

	/**
	 * Turn all relative URLs in the content to absolute URLs
	 */
	static function absoluteURLs($html) {
		$html = str_replace('$CurrentPageURL', $_SERVER['REQUEST_URI'], $html);
		return HTTP::urlRewriter($html, '(substr($URL,0,1) == "/") ? ( Director::protocolAndHost() . $URL ) : ( (ereg("^[A-Za-z]+:", $URL)) ? $URL : Director::absoluteBaseURL() . $URL )' );
	}

	/*
	 * Rewrite all the URLs in the given content, evaluating the given string as PHP code
	 *
	 * Put $URL where you want the URL to appear, however, you can't embed $URL in strings
	 * Some example code:
	 *  '"../../" . $URL'
	 *  'myRewriter($URL)'
	 *  '(substr($URL,0,1)=="/") ? "../" . substr($URL,1) : $URL'
	 */
	static function urlRewriter($content, $code) {
		$attribs = array("src","background","a" => "href","link" => "href", "base" => "href");
		foreach($attribs as $tag => $attrib) {
			if(!is_numeric($tag)) $tagPrefix = "$tag ";
			else $tagPrefix = "";

			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *\")([^\"]*)(\")/ie";
			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *')([^']*)(')/ie";
			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *)([^\"' ]*)( )/ie";
		}
		$regExps[] = '/(background-image:[^;]*url *\()([^)]+)(\))/ie';
		$regExps[] = '/(background:[^;]*url *\()([^)]+)(\))/ie';

		// Make
		$code = 'stripslashes("$1") . (' . str_replace('$URL', 'stripslashes("$2")', $code) . ') . stripslashes("$3")';

		foreach($regExps as $regExp) {
			$content = preg_replace($regExp, $code, $content);
		}

		return $content;
	}

	static function setGetVar($varname, $varvalue, $currentURL = null) {
		$currentURL = $currentURL ? $currentURL : $_SERVER['REQUEST_URI'];

		$scriptbase = $currentURL;
		$scriptbase = str_replace('&amp;','&',$scriptbase);

		$scriptbase = ereg_replace("&$varname=[^&]*",'',$scriptbase);
		$scriptbase = ereg_replace("\?$varname=[^&]*&",'?',$scriptbase);
		$scriptbase = ereg_replace("\?$varname=[^&]*",'',$scriptbase);

		$suffix = '';
		if(($hashPos = strpos($scriptbase,'#')) !== false) {
			$suffix .= substr($scriptbase, $hashPos);
			$scriptbase = substr($scriptbase, 0, $hashPos);
		}

		if($varvalue !== null) {
			$scriptbase .= (strrpos($scriptbase,'?') !== false) ? '&' : '?';
			$scriptbase .= "$varname=" . (isset($urlEncodeVarValue) ? urlencode($varvalue) : $varvalue);
		}

		$scriptbase = str_replace('&','&amp;',$scriptbase);
		return $scriptbase . $suffix;
	}

	static function RAW_setGetVar($varname, $varvalue, $currentURL = null) {
		$url = self::setGetVar($varname, $varvalue, $currentURL);
		return Convert::xml2raw($url);
	}

	static function findByTagAndAttribute($content, $attribs) {

		foreach($attribs as $tag => $attrib) {
			if(!is_numeric($tag)) $tagPrefix = "$tag ";
			else $tagPrefix = "";

			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *\")([^\"]*)(\")/ie";
			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *')([^']*)(')/ie";
			$regExps[] = "/(<{$tagPrefix}[^>]*$attrib *= *)([^\"' ]*)( )/ie";
		}

		foreach($regExps as $regExp) {
			$content = preg_replace($regExp, '$items[] = "$2"', $content);
		}

		return isset($items) ? $items : null;
	}

	static function getLinksIn($content) {
		return self::findByTagAndAttribute($content, array("a" => "href"));
	}
	static function getImagesIn($content) {
		return self::findByTagAndAttribute($content, array("img" => "src"));
	}

	/*
	 * Outputs appropriate header for downloading a file
	 * exits() after the call, so that no further output is given
	 */
	static function sendFileToBrowser($fileData, $fileName, $mimeType = false) {
		if(!$mimeType) $mimeType = self::getMimeType($fileName);
		$ext = strtolower(substr($fileName,strrpos($fileName,'.')+1));
		$inlineExtensions = array('pdf','png','jpg','jpe','gif','swf','htm','html','txt','text','avi','wmv','mov','mpe','mpg','mp3','mpeg');

		if(in_array($ext, $inlineExtensions)) $inline = true;

		header("Content-Type: $mimeType; name=\"" . addslashes($fileName) . "\"");
		//header("Content-Type: $mimeType" );
		// Downloadable
		//if(!$inline)
			$dispHeader = "Content-disposition: attachment; filename=" . addslashes($fileName) . "";

		// Debug::message('CD: ' . strlen( $dispHeader ) );


		header( $dispHeader );
		header("Content-Length: " . strlen($fileData));

		echo $fileData;

		exit();
	}

	/*
	 * Get mime type based on extension
	 */
	static function getMimeType($filename) {
		global $global_mimetypes;
		if(!$global_mimetypes) self::loadMimeTypes();
		$ext = strtolower(substr($filename,strrpos($filename,'.')+1));
		if(isset($global_mimetypes[$ext])) return $global_mimetypes[$ext];
	}

	/*
	 * Load the mime-type data from the system file
	 */
	static function loadMimeTypes() {
		if(@file_exists('/etc/mime.types')) {
			$mimeTypes = file('/etc/mime.types');
			foreach($mimeTypes as $typeSpec) {
				if(($typeSpec = trim($typeSpec)) && substr($typeSpec,0,1) != "#") {
					$parts = split("[ \t\r\n]+", $typeSpec);
					if(sizeof($parts) > 1) {
						$mimeType = array_shift($parts);
						foreach($parts as $ext) {
							$ext = strtolower($ext);
							$mimeData[$ext] = $mimeType;
						}
					}
				}
			}

		// Fail-over for if people don't have /etc/mime.types on their server.  it's unclear how important this actually is
		} else {
			$mimeData = array(
				"doc" => "application/msword",
				"xls" => "application/vnd.ms-excel",
				"rtf" => "application/rtf",
			);
		}

		global $global_mimetypes;
		$global_mimetypes = $mimeData;
		return $mimeData;
	}

	/**
	 * Send an HTTP request to the host
	 */
	static function sendRequest( $host, $path, $query, $port = 80 ) {
		$socket = fsockopen( $host, $port, $errno, $error );

		if( !$socket )
			return $error;

		if( $query )
			$query = '?' . $query;

		if( self::$userName && self::$password ) {
			$auth = "Authorization: Basic " . base64_encode( self::$userName . ':' . self::$password ) . "\r\n";
		} else {
			$auth = '';
		}

		$request = "GET {$path}{$query} HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n{$auth}\r\n";

		fwrite( $socket, $request );
		$response = stream_get_contents( $socket );

		return $response;
	}

	static function sendPostRequest( $host, $path, $data, $name, $query = '', $port = 80 ) {

		$socket = fsockopen( $host, $port, $errno, $error );

		if( !$socket )
			return $error;

		if( self::$userName && self::$password )
			$auth = "Authorization: Basic " . base64_encode( self::$userName . ':' . self::$password ) . "\r\n";

		if( $query )
			$query = '?' . $query;

		$data = urlencode( $data );
		$data = $name . '=' . $data;
		$length = strlen( $data );

		$request = "POST {$path}{$query} HTTP/1.1\r\nHost: $host\r\n{$auth}Content-Type: application/x-www-form-urlencoded\r\nContent-Length: $length\r\n\r\n";

		$request .= $data . "\r\n\r\n";

		fwrite( $socket, $request );
		$response = stream_get_contents( $socket );

		/*if( $query )
			$query = '?' . $query;

		$vars['synchronise'] = $data;

		$curl = curl_init('http://' . $host . $path );

		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $vars );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_VERBOSE, true );
		curl_setopt( $curl, CURLOPT_USERPWD, self::$userName . ':' . self::$password);

		$response = curl_exec( $curl );
		curl_close( $curl );*/

		return $response;

	}

	protected static $cache_age = 86400, $modification_date = null;
	protected static $etag = null;

	/**
	 * Set the maximum age of this page in web caches, in seconds
	 */
	static function set_cache_age($age) {
		self::$cache_age = $age;
	}

	static function register_modification_date($dateString) {
		$timestamp = strtotime($dateString);
		if($timestamp > self::$modification_date)
			self::$modification_date = $timestamp;
	}

	static function register_modification_timestamp($timestamp) {
		if($timestamp > self::$modification_date)
			self::$modification_date = $timestamp;
	}

	static function register_etag($etag) {
		self::$etag = $etag;
	}

	/**
	 * Add the appropriate caching headers to the response
	 *
	 * @param string The reponse body
	 */
	static function add_cache_headers($body = null) {
		// Development sites have frequently changing templates; this can get stuffed up by the code
		// below.
		if(Director::isDev()) return;

		if(!headers_sent()) {
			if(function_exists('apache_request_headers')) {
				$headers = apache_request_headers();
				if(isset($headers['X-Requested-With']) && $headers['X-Requested-With'] == 'XMLHttpRequest') self::$cache_age = 0;
            // bdc: now we must check for DUMB IE6:
            if(isset($headers['x-requested-with']) && $headers['x-requested-with'] == 'XMLHttpRequest') self::$cache_age = 0;
			}

			if(self::$cache_age > 0) {
				header("Cache-Control: max-age=" . self::$cache_age . ", must-revalidate");
				header("Pragma:");
			} else {
				header("Cache-Control: no-cache, max-age=0, must-revalidate");
			}

			if(self::$modification_date && self::$cache_age > 0) {
				header("Last-Modified: " . self::gmt_date(self::$modification_date));

				$expires = 2 * time() - self::$modification_date;
				header("Expires: " . self::gmt_date($expires));
			}

			if(self::$etag) {
				header('ETag: ' . self::$etag);
			}
		}
	}


	/**
	 * Return an {@link http://www.faqs.org/rfcs/rfc2822 RFC 2822} date in the
	 * GMT timezone (a timestamp is always in GMT: the number of seconds
	 * since January 1 1970 00:00:00 GMT)
	 */
	static function gmt_date($timestamp) {
		return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
	}

}


?>
