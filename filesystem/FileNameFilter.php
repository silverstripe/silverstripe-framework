<?php
/**
 * @package framework
 * @subpackage filesystem
 */

/**
 * Filter certain characters from file name, for nicer (more SEO-friendly) URLs
 * as well as better filesystem compatibility. Can be used for files and folders.
 *
 * Caution: Does not take care of full filename sanitization in regards to directory traversal etc.,
 * please use PHP's built-in basename() for this purpose.
 *
 * The default sanitizer is quite conservative regarding non-ASCII characters,
 * in order to achieve maximum filesystem compatibility.
 * In case your filesystem supports a wider character set,
 * or is case sensitive, you might want to relax these rules
 * via overriding {@link FileNameFilter_DefaultFilter::$default_replacements}.
 *
 * To leave uploaded filenames as they are (being aware of filesystem restrictions),
 * add the following code to your YAML config:
 * <code>
 * FileNameFilter:
 *   default_use_transliterator: false
 *   default_replacements:
 * </code>
 *
 * See {@link URLSegmentFilter} for a more generic implementation.
 */
class FileNameFilter extends Object {

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
		'/\s/' => '-', // remove whitespace
		'/_/' => '-', // underscores to dashes
		'/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
		'/[\-]{2,}/' => '-', // remove duplicate dashes
		'/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
	);

	/**
	 * @var Array See {@link setReplacements()}
	 */
	public $replacements = array();

	/**
	 * Depending on the applied replacement rules, this method
	 * might result in an empty string. In this case, {@link getDefaultName()}
	 * will be used to return a randomly generated file name, while retaining its extension.
	 *
	 * @param String Filename including extension (not path).
	 * @return String A filtered filename
	 */
	public function filter($name) {
		$ext = pathinfo($name, PATHINFO_EXTENSION);

		$transliterator = $this->getTransliterator();
		if($transliterator) $name = $transliterator->toASCII($name);
		foreach($this->getReplacements() as $regex => $replace) {
			$name = preg_replace($regex, $replace, $name);
		}

		// Safeguard against empty file names
		$nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
		if(empty($nameWithoutExt)) $name = $this->getDefaultName() . '.' . $ext;

		return $name;
	}

	/**
	 * Take care not to add replacements which might invalidate the file structure,
	 * e.g. removing dots will remove file extension information.
	 *
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
	 * @return String File name without extension
	 */
	public function getDefaultName() {
		return (string)uniqid();
	}
}
