<?php
/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Filter certain characters from "URL segments" (also called "slugs"), for nicer (more SEO-friendly) URLs.
 * Uses {@link Transliterator} to convert non-ASCII characters to meaningful ASCII representations.
 * Use {@link $default_allow_multibyte} to allow a broader range of characters without transliteration.
 * 
 * Caution: Should not be used on full URIs with domains or query parameters.
 * In order to retain forward slashes in a path, each individual segment needs to be filtered individually.
 * 
 * See {@link FileNameFilter} for similar implementation for filesystem-based URLs.
 */
class URLSegmentFilter {
	
	/**
	 * @var Boolean
	 */
	static $default_use_transliterator = true;
	
	/**
	 * @var Array See {@link setReplacements()}.
	 */
	static $default_replacements = array(
		'/&amp;/u' => '-and-',
		'/&/u' => '-and-',
		'/\s/u' => '-', // remove whitespace
		'/_/u' => '-', // underscores to dashes
		'/[^A-Za-z0-9+.-]+/u' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
		'/[\-]{2,}/u' => '-', // remove duplicate dashes
		'/^[\.\-_]/u' => '', // Remove all leading dots, dashes or underscores
	);
	
	/**
	 * Doesn't try to replace or transliterate non-ASCII filters.
	 * Useful for character sets that have little overlap with ASCII (e.g. far eastern),
	 * as well as better search engine optimization for URLs.
	 * @see http://www.ietf.org/rfc/rfc3987
	 * 
	 * @var boolean
	 */
	static $default_allow_multibyte = false;
	
	/**
	 * @var Array See {@link setReplacements()}
	 */
	public $replacements = array();
	
	/**
	 * Note: Depending on the applied replacement rules, this method might result in an empty string. 
	 * 
	 * @param String URL path (without domain or query parameters), in utf8 encoding
	 * @return String A filtered path compatible with RFC 3986
	 */
	function filter($name) {
		if(!$this->getAllowMultibyte()) {
			// Only transliterate when no multibyte support is requested
			$transliterator = $this->getTransliterator();
			if($transliterator) $name = $transliterator->toASCII($name);
		}
		
		$name = (function_exists('mb_strtolower')) ? mb_strtolower($name) : strtolower($name);
		$replacements = $this->getReplacements();
		if($this->getAllowMultibyte()) {
			// unset automated removal of non-ASCII characters, and don't try to transliterate
			if(isset($replacements['/[^A-Za-z0-9+.-]+/u'])) unset($replacements['/[^A-Za-z0-9+.-]+/u']);
		}
		foreach($replacements as $regex => $replace) {
			$name = preg_replace($regex, $replace, $name);
		}
		
		return $name;
	}
	
	/**
	 * @param Array Map of find/replace used for preg_replace().
	 */
	function setReplacements($r) {
		$this->replacements = $r;
	}
	
	/**
	 * @return Array
	 */
	function getReplacements() {
		return ($this->replacements) ? $this->replacements : self::$default_replacements;
	}
		
	/**
	 * @var Transliterator
	 */
	protected $transliterator;
	
	/**
	 * @return Transliterator|NULL
	 */
	function getTransliterator() {
		if($this->transliterator === null && self::$default_use_transliterator) {
			$this->transliterator = Object::create('Transliterator');
		} 
		return $this->transliterator;
	}
	
	/**
	 * @param Transliterator|FALSE
	 */
	function setTransliterator($t) {
		$this->transliterator = $t;
	}
	
	/**
	 * @var boolean
	 */
	protected $allowMultibyte;
	
	/**
	 * @param boolean
	 */
	function setAllowMultibyte($bool) {
		$this->allowMultibyte = $bool;
	}
	
	/**
	 * @return boolean
	 */
	function getAllowMultibyte() {
		return ($this->allowMultibyte !== null) ? $this->allowMultibyte : self::$default_allow_multibyte;
	}
}