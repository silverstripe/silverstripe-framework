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
	 * Convert a value to be suitable for an HTML ID attribute. Replaces non
	 * supported characters with a space.
	 *
	 * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
	 *
	 * @param array|string $val String to escape, or array of strings
	 *
	 * @return array|string
	 */
	public static function raw2htmlname($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) {
				$val[$k] = self::raw2htmlname($v);
			}

			return $val;
		} else {
			return self::raw2att($val);
		}
	}

	/**
	 * Convert a value to be suitable for an HTML ID attribute. Replaces non
	 * supported characters with an underscore.
	 *
	 * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
	 *
	 * @param array|string $val String to escape, or array of strings
	 *
	 * @return array|string
	 */
	public static function raw2htmlid($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) {
				$val[$k] = self::raw2htmlid($v);
			}

			return $val;
		} else {
			return trim(preg_replace(
				'/_+/', '_', preg_replace('/[^a-zA-Z0-9\-_:.]+/','_', $val)),
				'_'
			);
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
			return str_replace(
				// Intercepts some characters such as <, >, and & which can interfere
				array("\\", '"', "\n", "\r", "'", "<", ">", "&"),
				array("\\\\", '\"', '\n', '\r', "\\'", "\\x3c", "\\x3e", "\\x26"),
				$val
			);
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

	/**
	 * Safely encodes a value (or list of values) using the current database's
	 * safe string encoding method
	 *
	 * @param mixed|array $val Input value, or list of values as an array
	 * @param boolean $quoted Flag indicating whether the value should be safely
	 * quoted, instead of only being escaped. By default this function will
	 * only escape the string (false).
	 * @return string|array Safely encoded value in the same format as the input
	 */
	public static function raw2sql($val, $quoted = false) {
		if(is_array($val)) {
			foreach($val as $k => $v) {
				$val[$k] = self::raw2sql($v, $quoted);
			}
			return $val;
		} else {
			if($quoted) {
				return DB::get_conn()->quoteString($val);
			} else {
				return DB::get_conn()->escapeString($val);
			}
		}
	}

	/**
	 * Safely encodes a SQL symbolic identifier (or list of identifiers), such as a database,
	 * table, or column name. Supports encoding of multi identfiers separated by
	 * a delimiter (e.g. ".")
	 *
	 * @param string|array $identifier The identifier to escape. E.g. 'SiteTree.Title'
	 * @param string $separator The string that delimits subsequent identifiers
	 * @return string|array The escaped identifier. E.g. '"SiteTree"."Title"'
	 */
	public static function symbol2sql($identifier, $separator = '.') {
		if(is_array($identifier)) {
			foreach($identifier as $k => $v) {
				$identifier[$k] = self::symbol2sql($v, $separator);
			}
			return $identifier;
		} else {
			return DB::get_conn()->escapeIdentifier($identifier, $separator);
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
	 * See http://phpsecurity.readthedocs.org/en/latest/Injection-Attacks.html#xml-external-entity-injection
	 *
	 * @uses recursiveXMLToArray()
	 * @param string $val
	 * @param boolean $disableDoctypes Disables the use of DOCTYPE, and will trigger an error if encountered.
	 * false by default.
	 * @param boolean $disableExternals Disables the loading of external entities. false by default.
	 * @return array
	 */
	public static function xml2array($val, $disableDoctypes = false, $disableExternals = false) {
		// Check doctype
		if($disableDoctypes && preg_match('/\<\!DOCTYPE.+]\>/', $val)) {
			throw new InvalidArgumentException('XML Doctype parsing disabled');
		}

		// Disable external entity loading
		if($disableExternals) $oldVal = libxml_disable_entity_loader($disableExternals);
		try {
			$xml = new SimpleXMLElement($val);
			$result = self::recursiveXMLToArray($xml);
		} catch(Exception $ex) {
			if($disableExternals) libxml_disable_entity_loader($oldVal);
			throw $ex;
		}
		if($disableExternals) libxml_disable_entity_loader($oldVal);
		return $result;
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
	 * @param string $string The string to linkify
	 * @return string A link to the URL if string is a URL
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
	 * @param string $data Input data
	 * @param bool $preserveLinks
	 * @param int $wordWrap
	 * @param array $config
	 * @return string
	 */
	public static function html2raw($data, $preserveLinks = false, $wordWrap = 0, $config = null) {
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
		$data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
		// Remove all tags (but optionally keep links)

		// strip_tags seemed to be restricting the length of the output
		// arbitrarily. This essentially does the same thing.
		if(!$preserveLinks && !$config['PreserveLinks']) {
			$data = preg_replace('/<\/?[^>]*>/','', $data);
		} else {
			$data = strip_tags($data, '<a>');
		}

		// Wrap
		if($wordWrap) $data = wordwrap(trim($data), $wordWrap);
		return trim($data);
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
	 *
	 * @param string $data Text containing potentially mixed formats of newline
	 * sequences including \r, \r\n, \n, or unicode newline characters
	 * @param string $nl The newline sequence to normalise to. Defaults to that
	 * specified by the current OS
	 * @return string
	 */
	public static function nl2os($data, $nl = PHP_EOL) {
		return preg_replace('~\R~u', $nl, $data);
	}

	/**
	 * Encode a value into a string that can be used as part of a filename.
	 * All string data must be UTF-8 encoded.
	 *
	 * @param mixed $val Value to be encoded
	 * @return string
	 */
	public static function base64url_encode($val) {
		return rtrim(strtr(base64_encode(json_encode($val)), '+/', '~_'), '=');
	}

	/**
	 * Decode a value that was encoded with Convert::base64url_encode.
	 *
	 * @param string $val Value to be decoded
	 * @return mixed Original value
	 */
	public static function base64url_decode($val) {
		return json_decode(
			base64_decode(str_pad(strtr($val, '~_', '+/'), strlen($val) % 4, '=', STR_PAD_RIGHT)),
			true
		);
	}
}
