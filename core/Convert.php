<?php
/**
 * Library of conversion functions, implemented as static methods.
 *
 * The methods are all of the form (format)2(format), where the format is one of
 * 
 *  raw: A UTF8 string
 *  attr: A UTF8 string suitable for inclusion in an HTML attribute
 *  js: A UTF8 string suitable for inclusion in a double-quoted javascript string.
 * 
 *  array: A PHP associative array
 *  json: JavaScript object notation
 *
 *  html: HTML source suitable for use in a page or email
 *  text: Plain-text content, suitable for display to a user as-is, or insertion in a plaintext email.
 * 
 * Objects of type {@link ViewableData} can have an "escaping type",
 * which determines if they are automatically escaped before output by {@link SSViewer}. 
 * 
 * @package framework
 * @subpackage misc
 */
class Convert {
	
	/**
	 * Convert a value to be suitable for an XML attribute.
	 * 
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	public static function raw2att($val) {
		return self::raw2xml($val);
	}

	/**
	 * Convert a value to be suitable for an HTML attribute.
	 * 
	 * @param string|array $val String to escape, or array of strings
	 * @return array|string
	 */
	public static function raw2htmlatt($val) {
		return self::raw2att($val);
	}

	/**
	 * Convert a value to be suitable for an HTML attribute.
	 * 
	 * This is useful for converting human readable values into
	 * a value suitable for an ID or NAME attribute.
	 * 
	 * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
	 * @uses Convert::raw2att()
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	public static function raw2htmlname($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2htmlname($v);
			return $val;
		} else {
			return preg_replace('/[^a-zA-Z0-9\-_:.]+/','', $val);
		}
	}
	
	/**
	 * Ensure that text is properly escaped for XML.
	 * 
	 * @see http://www.w3.org/TR/REC-xml/#dt-escape
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	public static function raw2xml($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2xml($v);
			return $val;
		} else {
			return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
		}
	}
	
	/**
	 * Ensure that text is properly escaped for Javascript.
	 *
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	public static function raw2js($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2js($v);
			return $val;
		} else {
			return str_replace(array("\\", '"', "\n", "\r", "'"), array("\\\\", '\"', '\n', '\r', "\\'"), $val);
		}
	}

	/**
	 * Encode a value as a JSON encoded string.
	 *
	 * @param mixed $val Value to be encoded
	 * @return string JSON encoded string
	 */
	public static function raw2json($val) {
		return json_encode($val);
	}

	/**
	 * Encode an array as a JSON encoded string.
	 * THis is an alias to {@link raw2json()}
	 *
	 * @param array $val Array to convert
	 * @return string JSON encoded string
	 */
	public static function array2json($val) {
		return self::raw2json($val);
	}

	public static function raw2sql($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2sql($v);
			return $val;
		} else {
			return DB::getConn()->addslashes($val);
		}
	}

	/**
	 * Convert XML to raw text.
	 * @uses html2raw()
	 * @todo Currently &#xxx; entries are stripped; they should be converted
	 */
	public static function xml2raw($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::xml2raw($v);
			return $val;
		} else {
			// More complex text needs to use html2raw instead
			if(strpos($val,'<') !== false) return self::html2raw($val);
			else return html_entity_decode($val, ENT_QUOTES, 'UTF-8');
		}
	}

	/**
	 * Convert a JSON encoded string into an object.
	 *
	 * @param string $val
	 * @return object|boolean
	 */
	public static function json2obj($val) {
		return json_decode($val);
	}

	/**
	 * Convert a JSON string into an array.
	 * 
	 * @uses json2obj
	 * @param string $val JSON string to convert
	 * @return array|boolean
	 */
	public static function json2array($val) {
		return json_decode($val, true);
	}
	
	/**
	 * Converts an XML string to a PHP array
	 *
	 * @uses recursiveXMLToArray()
	 * @param string
	 *
	 * @return array
	 */
	public static function xml2array($val) {
		$xml = new SimpleXMLElement($val);
		return self::recursiveXMLToArray($xml);
	}

	/**
	 * Convert a XML string to a PHP array recursively. Do not 
	 * call this function directly, Please use {@link Convert::xml2array()}
	 * 
	 * @param SimpleXMLElement
	 * 
	 * @return mixed
	 */
	protected static function recursiveXMLToArray($xml) {
		if(is_object($xml) && get_class($xml) == 'SimpleXMLElement') {
			$attributes = $xml->attributes();
			foreach($attributes as $k => $v) {
				if($v) $a[$k] = (string) $v;
			}
			$x = $xml;
			$xml = get_object_vars($xml);
		}
		if(is_array($xml)) {
			if(count($xml) == 0) return (string) $x; // for CDATA
			foreach($xml as $key => $value) {
				$r[$key] = self::recursiveXMLToArray($value);
			}
			if(isset($a)) $r['@'] = $a; // Attributes
			return $r;
		}
		
		return (string) $xml;
	}
	
	/**
	 * Create a link if the string is a valid URL
	 *
	 * @param string The string to linkify
	 * @return A link to the URL if string is a URL
	 */
	public static function linkIfMatch($string) {
		if( preg_match( '/^[a-z+]+\:\/\/[a-zA-Z0-9$-_.+?&=!*\'()%]+$/', $string ) )
			return "<a style=\"white-space: nowrap\" href=\"$string\">$string</a>";
		else
			return $string;
	}
	
	/**
	 * Simple conversion of HTML to plaintext.
	 * 
	 * @param $data string
	 * @param $preserveLinks boolean
	 * @param $wordwrap array 
	 */
	public static function html2raw($data, $preserveLinks = false, $wordWrap = 60, $config = null) {
		$defaultConfig = array(
			'PreserveLinks' => false,
			'ReplaceBoldAsterisk' => true,
			'CompressWhitespace' => true,
			'ReplaceImagesWithAlt' => true,
		);
		if(isset($config)) {
			$config = array_merge($defaultConfig,$config);
		} else {
			$config = $defaultConfig;
		}

		$data = preg_replace("/<style([^A-Za-z0-9>][^>]*)?>.*?<\/style[^>]*>/is","", $data);
		$data = preg_replace("/<script([^A-Za-z0-9>][^>]*)?>.*?<\/script[^>]*>/is","", $data);

		if($config['ReplaceBoldAsterisk']) {
			$data = preg_replace('%<(strong|b)( [^>]*)?>|</(strong|b)>%i','*',$data);
		}
		
		// Expand hyperlinks
		if(!$preserveLinks && !$config['PreserveLinks']) {
			$data = preg_replace_callback('/<a[^>]*href\s*=\s*"([^"]*)">(.*?)<\/a>/i', function($matches) {
				return Convert::html2raw($matches[2]) . "[$matches[1]]";
			}, $data);
			$data = preg_replace_callback('/<a[^>]*href\s*=\s*([^ ]*)>(.*?)<\/a>/i', function($matches) {
				return Convert::html2raw($matches[2]) . "[$matches[1]]";
			}, $data);
		}
	
		// Replace images with their alt tags
		if($config['ReplaceImagesWithAlt']) {
			$data = preg_replace('/<img[^>]*alt *= *"([^"]*)"[^>]*>/i', ' \\1 ', $data);
			$data = preg_replace('/<img[^>]*alt *= *([^ ]*)[^>]*>/i', ' \\1 ', $data);
		}
	
		// Compress whitespace
		if($config['CompressWhitespace']) {
			$data = preg_replace("/\s+/", " ", $data);
		}
		
		// Parse newline tags
		$data = preg_replace("/\s*<[Hh][1-6]([^A-Za-z0-9>][^>]*)?> */", "\n\n", $data);
		$data = preg_replace("/\s*<[Pp]([^A-Za-z0-9>][^>]*)?> */", "\n\n", $data);
		$data = preg_replace("/\s*<[Dd][Ii][Vv]([^A-Za-z0-9>][^>]*)?> */", "\n\n", $data);
		$data = preg_replace("/\n\n\n+/", "\n\n", $data);

		$data = preg_replace("/<[Bb][Rr]([^A-Za-z0-9>][^>]*)?> */", "\n", $data);
		$data = preg_replace("/<[Tt][Rr]([^A-Za-z0-9>][^>]*)?> */", "\n", $data);
		$data = preg_replace("/<\/[Tt][Dd]([^A-Za-z0-9>][^>]*)?> */", "    ", $data);
		$data = preg_replace('/<\/p>/i', "\n\n", $data );
	
		// Replace HTML entities
		//$data = preg_replace("/&#([0-9]+);/e", 'chr(\1)', $data);
		//$data = str_replace(array("&lt;","&gt;","&amp;","&nbsp;"), array("<", ">", "&", " "), $data);
		$data = html_entity_decode($data, ENT_COMPAT , 'UTF-8');
		// Remove all tags (but optionally keep links)
		
		// strip_tags seemed to be restricting the length of the output
		// arbitrarily. This essentially does the same thing.
		if(!$preserveLinks && !$config['PreserveLinks']) {
			$data = preg_replace('/<\/?[^>]*>/','', $data);
		} else {
			$data = strip_tags($data, '<a>');
		}
		return trim(wordwrap(trim($data), $wordWrap));
	}

	/**
	 * There are no real specifications on correctly encoding mailto-links,
	 * but this seems to be compatible with most of the user-agents.
	 * Does nearly the same as rawurlencode().
	 * Please only encode the values, not the whole url, e.g.
	 * "mailto:test@test.com?subject=" . Convert::raw2mailto($subject)
	 * 
	 * @param $data string
	 * @return string
	 * @see http://www.ietf.org/rfc/rfc1738.txt
	 */
	public static function raw2mailto($data) {
		return str_ireplace(
			array("\n",'?','=',' ','(',')','&','@','"','\'',';'),
			array('%0A','%3F','%3D','%20','%28','%29','%26','%40','%22','%27','%3B'),
			$data
		);
	}
	
	/**
	 * Convert a string (normally a title) to a string suitable for using in
	 * urls and other html attributes. Uses {@link URLSegmentFilter}.
	 *
	 * @param string 
	 * @return string
	 */
	public static function raw2url($title) {
		$f = URLSegmentFilter::create();
		return $f->filter($title);
	}
	
	/**
	 * Normalises newline sequences to conform to (an) OS specific format.
	 * @param string $data Text containing potentially mixed formats of newline
	 * sequences including \r, \r\n, \n, or unicode newline characters
	 * @param string $nl The newline sequence to normalise to. Defaults to that
	 * specified by the current OS
	 */
	public static function nl2os($data, $nl = PHP_EOL) {
		return preg_replace('~\R~u', $nl, $data);
	}
}
