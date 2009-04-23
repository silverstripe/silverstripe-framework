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
 * @package sapphire
 * @subpackage misc
 */
class Convert extends Object {
	
	static function raw2att($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2att($v);
			return $val;
			
		} else {
			return str_replace(array('&','"',"'",'<','>'),array('&amp;','&quot;','&#39;','&lt;','&gt;'),$val);
		}
	}
	
	/**
	 * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
	 * @uses raw2att
	 */
	static function raw2htmlatt($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2att($v);
			return $val;
			
		} else {
			$val = str_replace(array('&','"',"'",'<','>'),array('&amp;','&quot;','&#39;','&lt;','&gt;'),$val);
			$val = preg_replace('/[^a-zA-Z0-9\-_]*/','', $val);
			return $val;
		}
	}
	
	/**
	 * Ensure that text is properly escaped for XML.
	 *
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	static function raw2xml($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2xml($v);
			return $val;
		} else {
			return str_replace(array('&', '<', '>', "\n"), array('&amp;', '&lt;', '&gt;', '<br />'), $val);
		}
	}
	
	/**
	 * Ensure that text is properly escaped for Javascript.
	 *
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	static function raw2js($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2js($v);
			return $val;
		} else {
			return str_replace(array("\\", '"', "\n", "\r", "'"), array("\\\\", '\"', '\n', '\r', "\\'"), $val);
		}
	}
	
	/**
	 * Uses the PHP5.2 native json_encode function if available,
	 * otherwise falls back to the Services_JSON class.
	 * 
	 * @see http://pear.php.net/pepr/pepr-proposal-show.php?id=198
	 * @uses Director::baseFolder()
	 * @uses Services_JSON
	 *
	 * @param mixed $val
	 * @return string JSON safe string
	 */
	static function raw2json($val) {
		if(function_exists('json_encode')) {
			return json_encode($val);	
		} else {
			require_once(Director::baseFolder() . '/sapphire/thirdparty/json/JSON.php');			
			$json = new Services_JSON();
			return $json->encode($val);
		}
	}
	
	
	static function raw2sql($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2sql($v);
			return $val;
			
		} else {
			return addslashes($val);
		}
	}

	/**
	 * Convert XML to raw text
	 * @uses html2raw()
	 * @todo Currently &#xxx; entries are stripped; they should be converted
	 */
	static function xml2raw($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::xml2raw($v);
			return $val;
			
		} else {

			// More complex text needs to use html2raw instaed
			if(strpos($val,'<') !== false) return self::html2raw($val);
			
			// For simpler stuff, a simple str_replace will do
			else {
				$converted = str_replace(array('&amp;', '&lt;', '&gt;'), array('&', '<', '>'), $val);
				$converted = ereg_replace('&#[0-9]+;', '', $converted);
				return $converted;
			}
		}
	}
	
	/**
	 * @deprecated 2.3 Functionality too ambiguous - not sure if its converting
	 * xml trees to js objects, xml properties to json, ...
	 */
	static function xml2js($val) {		
		return self::raw2js(self::html2raw($val));
	}
	
	/**
	 * @deprecated 2.3 use raw2att()
	 */
	static function xml2att($val) {		
		return self::raw2att(self::xml2raw($val));
	}
	
	/**
	 * @deprecated 2.3 Use raw2sql()
	 */
	static function xml2sql($val) {		
		return self::raw2sql(self::xml2raw($val));
	}
	
	/**
	 * @deprecated 2.3
	 */
	static function js2raw($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::js2raw($v);
			return $val;
			
		} else {
			return str_replace(array('\"','\n','\r'), array('"',"\n","\r"), $val);
		}
	}
	
	/**
	 * @deprecated 2.3 Use raw2xml()
	 */
	static function js2xml($val) {		
		return self::raw2xml(self::js2raw($val));
	}
	
	/**
	 * @deprecated 2.3 Use raw2att()
	 */
	static function js2att($val) {		
		return self::raw2att(self::js2raw($val));
	}
	
	/**
	 * @deprecated 2.3 Use raw2sql()
	 */
	static function js2sql($val) {		
		return self::raw2sql(self::js2raw($val));
	}
	
	/**
	 * Uses the PHP5.2 native json_decode function if available,
	 * otherwise falls back to the Services_JSON class.
	 * 
	 * @see http://pear.php.net/pepr/pepr-proposal-show.php?id=198
	 *
	 * @param string $val
	 * @return mixed JSON safe string
	 */
	static function json2obj($val) {
		//if(function_exists('json_decode')) {
		//	return json_decode($val);	
		//} else {
			require_once(Director::baseFolder() . '/sapphire/thirdparty/json/JSON.php');			
			$json = new Services_JSON();
			return $json->decode($val);
		//}
	}

	/**
	 * @uses json2obj
	 */
	static function json2array($val) {
		$json = self::json2obj($val);
		$arr = array();
		foreach($json as $k => $v) {
			$arr[$k] = $v;
		}
		return $arr;
	}
	
	/**
	 * @uses recursiveXMLToArray()
	 */
	static function xml2array($val) {
		$xml = new SimpleXMLElement($val);
		return self::recursiveXMLToArray($xml);
	}
	
	protected static function recursiveXMLToArray($xml) {
		if (get_class($xml) == 'SimpleXMLElement') {
	       $attributes = $xml->attributes();
	       foreach($attributes as $k=>$v) {
	           if ($v) $a[$k] = (string) $v;
	       }
	       $x = $xml;
	       $xml = get_object_vars($xml);
	   }
	   if (is_array($xml)) {
	       if (count($xml) == 0) return (string) $x; // for CDATA
	       foreach($xml as $key=>$value) {
	           $r[$key] = self::recursiveXMLToArray($value);
	       }
	       if (isset($a)) $r['@'] = $a;    // Attributes
	       return $r;
	   }
	   return (string) $xml;
	}

	static function array2json( $array ) {
		if(function_exists("json_encode")) {
			return json_encode($array);
		}
		$result = array();
		
		foreach( $array as $key => $value )
			if( is_array( $value ) )
				$result[] = "'$key':" . Convert::array2json( $value );
			else
				$result[] = "'$key':'$value'";
			
		return '{' . implode( ', ', $result ) . '}';
	}
	

	
	/**
	 * Create a link if the string is a valid URL
	 * @param string The string to linkify
	 * @return A link to the URL if string is a URL
	 */
	static function linkIfMatch($string) {
		if( preg_match( '/^[a-z+]+\:\/\/[a-zA-Z0-9$-_.+?&=!*\'()%]+$/', $string ) )
			return "<a style=\"white-space: nowrap\" href=\"$string\">$string</a>";
		else
			return $string;
	}
	
	/**
	 * Create a link if the string is a valid URL
	 * @param string The string to linkify
	 * @return A link to the URL if string is a URL
	 */
	/*static function mailtoIfMatch($string) {
		if( preg_match( '/^[a-z+]+\:\/\/[a-zA-Z0-9$-_.+?&=!*\'()%]+$/', $string ) )
			return "<a href=\"$string\">$string</a>";
		else
			return $string;
	}*/
	
	/**
	 * Simple conversion of HTML to plaintext.
	 * 
	 * @param $data string
	 * @param $preserveLinks boolean
	 * @param $wordwrap array 
	 */
	static function html2raw($data, $preserveLinks = false, $wordWrap = 60, $config = null) {
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

		// sTRIp style and script
		/* $data = eregi_replace("<style(^A-Za-z0-9>][^>]*)?>.*</style[^>]*>","", $data);*/
		/* $data = eregi_replace("<script(^A-Za-z0-9>][^>]*)?>.*</script[^>]*>","", $data);*/
		
		$data = preg_replace("/<style(^A-Za-z0-9>][^>]*)?>.*?<\/style[^>]*>/i","", $data);
		$data = preg_replace("/<script(^A-Za-z0-9>][^>]*)?>.*?<\/script[^>]*>/i","", $data);
		// TODO Deal with attributes inside tags
		if($config['ReplaceBoldAsterisk']) {
			$data = str_ireplace(
				array('<strong>','</strong>','<b>','</b>'),
				'*',
				$data
			);
		}
		// Expand hyperlinks
		if( !$preserveLinks && !isset($config['PreserveLink'])) {
			$data = preg_replace('/<a[^>]*href\s*=\s*"([^"]*)">(.*?)<\/a>/ie', "Convert::html2raw('\\2').'[\\1]'", $data);
			$data = preg_replace('/<a[^>]*href\s*=\s*([^ ]*)>(.*?)<\/a>/ie', "Convert::html2raw('\\2').'[\\1]'", $data);
			
			/* $data = eregi_replace('<a[^>]*href *= *"([^"]*)">([^<>]*)</a>', '\\2 [\\1]', $data); */
			/* $data = eregi_replace('<a[^>]*href *= *([^ ]*)>([^<>]*)</a>', '\\2 [\\1]', $data); */
		}
	
		// Replace images with their alt tags
		if($config['ReplaceImagesWithAlt']) {
			$data = eregi_replace('<img[^>]*alt *= *"([^"]*)"[^>]*>', ' \\1 ', $data);
			$data = eregi_replace('<img[^>]*alt *= *([^ ]*)[^>]*>', ' \\1 ', $data);
		}
	
		// Compress whitespace
		if($config['CompressWhitespace']) {
			$data = ereg_replace("[\n\r\t ]+", " ", $data);
		}
		
		// Parse newline tags
		$data = ereg_replace("[ \n\r\t]*<[Hh][1-6]([^A-Za-z0-9>][^>]*)?> *", "\n\n", $data);
		$data = ereg_replace("[ \n\r\t]*<[Pp]([^A-Za-z0-9>][^>]*)?> *", "\n\n", $data);
		$data = ereg_replace("[ \n\r\t]*<[Dd][Ii][Vv]([^A-Za-z0-9>][^>]*)?> *", "\n\n", $data);
		$data = ereg_replace("\n\n\n+","\n\n", $data);
		
		$data = ereg_replace("<[Bb][Rr]([^A-Za-z0-9>][^>]*)?> *", "\n", $data);
		$data = ereg_replace("<[Tt][Rr]([^A-Za-z0-9>][^>]*)?> *", "\n", $data);
		$data = ereg_replace("</[Tt][Dd]([^A-Za-z0-9>][^>]*)?> *", "    ", $data);
		$data = preg_replace('/<\/p>/i', "\n\n", $data );
		
	
		// Replace HTML entities
		//$data = preg_replace("/&#([0-9]+);/e", 'chr(\1)', $data);
		//$data = str_replace(array("&lt;","&gt;","&amp;","&nbsp;"), array("<", ">", "&", " "), $data);
		$data = html_entity_decode($data, ENT_COMPAT , 'UTF-8');
		// Remove all tags (but optionally keep links)
		
		// strip_tags seemed to be restricting the length of the output
		// arbitrarily. This essentially does the same thing.
		if( !$preserveLinks ) {
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
	static function raw2mailto($data) {
		return str_ireplace(
			array("\n",'?','=',' ','(',')','&','@','"','\'',';'),
			array('%0A','%3F','%3D','%20','%28','%29','%26','%40','%22','%27','%3B'),
			$data
		);
	}

}

?>