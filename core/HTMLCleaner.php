<?php
/**
 * @package framework
 * @subpackage misc
 */

/**
 * Base class for HTML cleaning implementations.
 */
abstract class HTMLCleaner extends Object {

	/**
	 * @var array
	 */
	protected $defaultConfig = array();

	/**
	 * @var $config Array configuration variables for HTMLCleaners that support configuration (like Tidy)
	 */
	public $config;

	/**
	 * @param Array The configuration for the cleaner, if necessary
	 */
	public function __construct($config = null) {
		if ($config) $this->config = array_merge($this->defaultConfig, $config);
		else $this->config = $this->defaultConfig;
	}

	/**
	 * @param Array
	 */
	public function setConfig($config) {
		$this->config = $config;
	}

	/**
	 * @return Array
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Passed a string, return HTML that has been tidied.
	 *
	 * @param String HTML
	 * @return String HTML, tidied
	 */
	abstract public function cleanHTML($content);

	/**
	 * Experimental inst class to create a default html cleaner class
	 *
	 * @return PurifierHTMLCleaner|TidyHTMLCleaner
	 */
	public static function inst() {
		if (class_exists('HTMLPurifier')) return new PurifierHTMLCleaner();
		elseif (class_exists('tidy')) return new TidyHTMLCleaner();
	}
}


/**
 * Cleans HTML using the HTMLPurifier package
 * http://htmlpurifier.org/
 */
class PurifierHTMLCleaner extends HTMLCleaner {

	public function cleanHTML($content) {
		$html = new HTMLPurifier();
		$doc = Injector::inst()->create('HTMLValue', $html->purify($content));
		return $doc->getContent();
	}
}

/**
 * Cleans HTML using the Tidy package
 * http://php.net/manual/en/book.tidy.php
 */
class TidyHTMLCleaner extends HTMLCleaner {

	protected $defaultConfig = array(
		'clean' => true,
		'output-xhtml' => true,
		'show-body-only' => true,
		'wrap' => 0,
		'doctype' => 'omit',
		'input-encoding' => 'utf8',
		'output-encoding' => 'utf8'
	);

	public function cleanHTML($content) {
		$tidy = new tidy();
		$output = $tidy->repairString($content, $this->config);

		// Clean leading/trailing whitespace
		return preg_replace('/(^\s+)|(\s+$)/', '', $output);
	}
}
