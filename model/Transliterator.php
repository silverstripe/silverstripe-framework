<?php

/**
 * Support class for converting unicode strings into a suitable 7-bit ASCII equivalent.
 * 
 * Usage:
 * 
 * <code>
 * $tr = new SS_Transliterator();
 * $ascii = $tr->toASCII($unicode);
 * </code>
 * 
 * @package framework
 * @subpackage model
 */
class SS_Transliterator extends Object {
	/**
	 * Allow the use of iconv() to perform transliteration.  Set to false to disable.
	 * Even if this variable is true, iconv() won't be used if it's not installed.
	 */
	static $use_iconv = false;
		
	/**
	 * Convert the given utf8 string to a safe ASCII source
	 */
	function toASCII($source) {
		if(function_exists('iconv') && self::$use_iconv) return $this->useIconv($source);
		else return $this->useStrTr($source);
	}

	/**
	 * Transliteration using strtr() and a lookup table
	 */
	protected function useStrTr($source) {
		$table = array(
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'Ae', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'Oe', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'Ue', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'ae', 'å'=>'a', 'æ'=>'ae', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'oe', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'ue', 'ý'=>'y', 'ý'=>'y',
			'þ'=>'b', 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
			'Ā'=>'A', 'ā'=>'a', 'Ē'=>'E', 'ē'=>'e', 'Ī'=>'I', 'ī'=>'i', 'Ō'=>'O', 'ō'=>'o', 'Ū'=>'U', 'ū'=>'u',
			'œ'=>'oe', 'ß'=>'ss', 'ĳ'=>'ij', 
			'ą'=>'a','ę'=>'e', 'ė'=>'e', 'į'=>'i','ų'=>'u','ū'=>'u', 'Ą'=>'A','Ę'=>'E', 'Ė'=>'E', 'Į'=>'I','Ų'=>'U','Ū'=>'u'
		);

		return strtr($source, $table);
	}
	
	/**
	 * Transliteration using iconv()
	 */
	protected function useIconv($source) {
 		return iconv("utf-8", "us-ascii//IGNORE//TRANSLIT", $source);
	}
}
