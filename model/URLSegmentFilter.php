<?php
/**
 * @package framework
 * @subpackage model
 */

/**
 * Filter certain characters from "URL segments" (also called "slugs"), for nicer (more SEO-friendly) URLs.
 * Uses {@link SS_Transliterator} to convert non-ASCII characters to meaningful ASCII representations.
 * Use {@link $default_allow_multibyte} to allow a broader range of characters without transliteration.
 * 
 * Caution: Should not be used on full URIs with domains or query parameters.
 * In order to retain forward slashes in a path, each individual segment needs to be filtered individually.
 * 
 * See {@link FileNameFilter} for similar implementation for filesystem-based URLs.
 */
class URLSegmentFilter extends Object {
	
	/**
	 * @config
	 * @var Boolean
	 */
	private static $default_use_transliterator = true;
	
	/**
	 * @config
	 * @var Array See {@link setReplacements()}.
	 */
	private static $default_replacements = array(
		'/&amp;/u' => '-and-',
		'/&/u' => '-and-',
		'/\s|\+/u' => '-', // remove whitespace/plus
		'/[_.]+/u' => '-', // underscores and dots to dashes
		'/[^A-Za-z0-9\-]+/u' => '', // remove non-ASCII chars, only allow alphanumeric and dashes
		'/[\-]{2,}/u' => '-', // remove duplicate dashes
		'/^[\-_]/u' => '', // Remove all leading dashes or underscores
	);
	
	/**
	 * Doesn't try to replace or transliterate non-ASCII filters.
	 * Useful for character sets that have little overlap with ASCII (e.g. far eastern),
	 * as well as better search engine optimization for URLs.
	 * @see http://www.ietf.org/rfc/rfc3987
	 *
	 * @config
	 * @var boolean
	 */
	private static $default_allow_multibyte = false;
	
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
	public function filter($name) {
		if(!$this->getAllowMultibyte()) {
			// Only transliterate when no multibyte support is requested
			$transliterator = $this->getTransliterator();
			if($transliterator) $name = $transliterator->toASCII($name);
		}
		
		$name = mb_strtolower($name);
		$replacements = $this->getReplacements();
		
		// Unset automated removal of non-ASCII characters, and don't try to transliterate
		if($this->getAllowMultibyte() && isset($replacements['/[^A-Za-z0-9\-]+/u'])) {
			unset($replacements['/[^A-Za-z0-9\-]+/u']);
		}
		
		foreach($replacements as $regex => $replace) {
			$name = preg_replace($regex, $replace, $name);
		}

		// Multibyte URLs require percent encoding to comply to RFC 3986.
		// Without this setting, the "remove non-ASCII chars" regex takes care of that.
		if($this->getAllowMultibyte()) $name = rawurlencode($name);
		
		return $name;
	}
	
	/**
	 * @param Array Map of find/replace used for preg_replace().
	 */
	public function setReplacements($r) {
		$this->replacements = $r;
	}
	
	/**
	 * @return Array
	 */
	public function getReplacements() {
		return ($this->replacements) ? $this->replacements : (array)$this->config()->default_replacements;
	}
		
	/**
	 * @var SS_Transliterator
	 */
	protected $transliterator;
	
	/**
	 * @return SS_Transliterator|NULL
	 */
	public function getTransliterator() {
		if($this->transliterator === null && $this->config()->default_use_transliterator) {
			$this->transliterator = SS_Transliterator::create();
		} 
		return $this->transliterator;
	}
	
	/**
	 * @param SS_Transliterator|FALSE
	 */
	public function setTransliterator($t) {
		$this->transliterator = $t;
	}
	
	/**
	 * @var boolean
	 */
	protected $allowMultibyte;
	
	/**
	 * @param boolean
	 */
	public function setAllowMultibyte($bool) {
		$this->allowMultibyte = $bool;
	}
	
	/**
	 * @return boolean
	 */
	public function getAllowMultibyte() {
		return ($this->allowMultibyte !== null) ? $this->allowMultibyte : $this->config()->default_allow_multibyte;
	}
}
