<?php

/**
 * @package sapphire
 * @subpackage misc
 */

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
 * @usedby ViewableData::XML_val()
 * @package sapphire
 * @subpackage misc
 */
class Convert extends Object {
	// Convert raw to other formats
	
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
	 */
	static function raw2htmlatt($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2att($v);
			return $val;
			
		} else {
			$val = str_replace(array('&','"',"'",'<','>'),array('&amp;','&quot;','&#39;','&lt;','&gt;'),$val);
			$val = preg_replace('^[a-zA-Z0-9\-_]','_', $val);
			$val = preg_replace('^[0-9]*','', $val); //
			return $val;
		}
	}
	
	static function raw2xml($val) {		
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2xml($v);
			return $val;
			
		} else {
			return str_replace(array('&', '<', '>', "\n"), array('&amp;', '&lt;', '&gt;', '<br />'), $val);
		}
	}
	static function raw2js($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2js($v);
			return $val;
			
		} else {
			return str_replace(array("\\", '"',"\n","\r", "'"), array("\\\\", '\"','\n','\r', "\\'"), $val);
		}
	}
	
	// TODO Possible security risk: doesn't support arrays with more than one level - should be called recursively
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
	static function xml2js($val) {		
		return self::raw2js(self::html2raw($val));
	}
	static function xml2att($val) {		
		return self::raw2att(self::xml2raw($val));
	}
	static function xml2sql($val) {		
		return self::raw2sql(self::xml2raw($val));
	}
	
	// Convert JS to other formats
	static function js2raw($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::js2raw($v);
			return $val;
			
		} else {
			return str_replace(array('\"','\n','\r'), array('"',"\n","\r"), $val);
		}
	}
	static function js2xml($val) {		
		return self::raw2xml(self::js2raw($val));
	}
	static function js2att($val) {		
		return self::raw2att(self::js2raw($val));
	}
	static function js2sql($val) {		
		return self::raw2sql(self::js2raw($val));
	}
	
	static function xml2array($val) {
		return preg_split( '/\s*(<[^>]+>)|\s\s*/', $val, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	}

	static function array2json( $array ) {
		$result = array();
		
		// Debug::show($array);
		
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
		$data = preg_replace("/&#([0-9]+);/e", 'chr(\1)', $data);
		$data = str_replace(array("&lt;","&gt;","&amp;","&nbsp;"), array("<", ">", "&", " "), $data);
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
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	// Deprecated

	/**
	 * Converts the given val from a raw string into HTML.
	 * has support for multibyte charaters.
	 *
	 * @param  $val the string you wish to convert
	 * @return the HTML version of the string
	 * @deprecated
	 */
	static function raw2html($val) {
		user_error("Convert::raw2html is deprecated.  Used Convert::raw2xml instead", E_USER_NOTICE);
		return self::raw2xml($val);
	}
	
	/**
	 * @deprecated
	 */
	static function html2plain($val){
		user_error("html2plain is deprecated.  Use xml2raw instead.", E_USER_NOTICE);
		return self::html2raw($val);
	}
	
	/**
	 * @deprecated
	 */
	static function html2text($val, $preserveLinks = false) {
		user_error("html2text is deprecated.  Use xml2raw instead.", E_USER_NOTICE);
		return self::html2raw($val);
	}
	
	/**
	 * @deprecated
	 */
	static function raw2reserveNL($val){
		user_error("Convert::raw2reserveNL is deprecated.  Used Convert::raw2xml instead", E_USER_NOTICE);
		return self::raw2xml($val);
	}
	
	/**
	 * @deprecated
	 */
	static function raw2attr($val) {
		user_error("raw2attr is deprecated.  Use raw2att instead.", E_USER_WARNING);
		return self::raw2att($val);
	}


}

?>
