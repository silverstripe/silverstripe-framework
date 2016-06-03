<?php

namespace SilverStripe\ORM\FieldType;

use Convert;

/**
 * An abstract base class for the string field types (i.e. Varchar and Text)
 *
 * @package framework
 * @subpackage orm
 */
abstract class DBString extends DBField {

	/**
	 * @var boolean
	 */
	protected $nullifyEmpty = true;

	/**
	 * @var array
	 */
	private static $casting = array(
		"LimitCharacters" => "Text",
		"LimitCharactersToClosestWord" => "Text",
		'LimitWordCount' => 'Text',
		"LowerCase" => "Text",
		"UpperCase" => "Text",
		'NoHTML' => 'Text',
	);

	/**
	 * Construct a string type field with a set of optional parameters.
	 *
	 * @param string $name string The name of the field
	 * @param array $options array An array of options e.g. array('nullifyEmpty'=>false).  See
	 *                       {@link StringField::setOptions()} for information on the available options
	 */
	public function __construct($name = null, $options = array()) {
		$options = $this->parseConstructorOptions($options);
		if($options) {
			$this->setOptions($options);
		}

		parent::__construct($name);
	}

	/**
	 * Parses the "options" parameter passed to the constructor. This could be a
	 * string value, or an array of options. Config specification might also
	 * encode "key=value" pairs in non-associative strings.
	 *
	 * @param mixed $options
	 * @return array The list of parsed options, or empty if there are none
	 */
	protected function parseConstructorOptions($options) {
		if(is_string($options)) {
			$options = [$options];
		}
		if(!is_array($options)) {
			return [];
		}
		$parsed = [];
		foreach($options as $option => $value) {
			// Workaround for inability for config args to support associative arrays
			if(is_numeric($option) && strpos($value, '=') !== false) {
				list($option, $value) = explode('=', $value);
				$option = trim($option);
				$value = trim($value);
			}
			// Convert bool values
			if(strcasecmp($value, 'true') === 0) {
				$value = true;
			} elseif(strcasecmp($value, 'false') === 0) {
				$value = false;
			}
			$parsed[$option] = $value;
		}
		return $parsed;
	}

	/**
	 * Update the optional parameters for this field.
	 *
	 * @param array $options Array of options
	 * The options allowed are:
	 *   <ul><li>"nullifyEmpty"
	 *       This is a boolean flag.
	 *       True (the default) means that empty strings are automatically converted to nulls to be stored in
	 *       the database. Set it to false to ensure that nulls and empty strings are kept intact in the database.
	 *   </li></ul>
	 * @return $this
	 */
	public function setOptions(array $options = array()) {
		if(array_key_exists("nullifyEmpty", $options)) {
			$this->nullifyEmpty = $options["nullifyEmpty"] ? true : false;
		}
		return $this;
	}

	/**
	 * Set whether this field stores empty strings rather than converting
	 * them to null.
	 *
	 * @param $value boolean True if empty strings are to be converted to null
	 */
	public function setNullifyEmpty($value) {
		$this->nullifyEmpty = ($value ? true : false);
	}

	/**
	 * Get whether this field stores empty strings rather than converting
	 * them to null
	 *
	 * @return boolean True if empty strings are to be converted to null
	 */
	public function getNullifyEmpty() {
		return $this->nullifyEmpty;
	}

	/**
	 * (non-PHPdoc)
	 * @see core/model/fieldtypes/DBField#exists()
	 */
	public function exists() {
		$value = $this->RAW();
		return $value // All truthy values exist
			|| (is_string($value) && strlen($value)) // non-empty strings exist ('0' but not (int)0)
			|| (!$this->getNullifyEmpty() && $value === ''); // Remove this stupid exemption in 4.0
	}

	/**
	 * (non-PHPdoc)
	 * @see core/model/fieldtypes/DBField#prepValueForDB($value)
	 */
	public function prepValueForDB($value) {
		if(!$this->nullifyEmpty && $value === '') {
			return $value;
		} else {
			return parent::prepValueForDB($value);
		}
	}

	/**
	 * @return string
	 */
	public function forTemplate() {
		return nl2br(parent::forTemplate());
	}

	/**
	 * Limit this field's content by a number of characters.
	 * This makes use of strip_tags() to avoid malforming the
	 * HTML tags in the string of text.
	 *
	 * @param int $limit Number of characters to limit by
	 * @param string $add Ellipsis to add to the end of truncated string
	 * @return string
	 */
	public function LimitCharacters($limit = 20, $add = '...') {
		$value = trim($this->RAW());
		if($this->stat('escape_type') == 'xml') {
			$value = strip_tags($value);
			$value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');
			$value = (mb_strlen($value) > $limit) ? mb_substr($value, 0, $limit) . $add : $value;
			// Avoid encoding all multibyte characters as HTML entities by using htmlspecialchars().
			$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		} else {
			$value = (mb_strlen($value) > $limit) ? mb_substr($value, 0, $limit) . $add : $value;
		}
		return $value;
	}

	/**
	 * Limit this field's content by a number of characters and truncate
	 * the field to the closest complete word. All HTML tags are stripped
	 * from the field.
	 *
	 * @param int $limit Number of characters to limit by
	 * @param string $add Ellipsis to add to the end of truncated string
	 * @return string
	 */
	public function LimitCharactersToClosestWord($limit = 20, $add = '...') {
		// Strip HTML tags if they exist in the field
		$value = strip_tags($this->RAW());

		// Determine if value exceeds limit before limiting characters
		$exceedsLimit = mb_strlen($value) > $limit;

		// Limit to character limit
		$value = DBField::create_field(get_class($this), $value)->LimitCharacters($limit, '');

		// If value exceeds limit, strip punctuation off the end to the last space and apply ellipsis
		if($exceedsLimit) {
			$value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');

			$value = rtrim(mb_substr($value, 0, mb_strrpos($value, " ")), "/[\.,-\/#!$%\^&\*;:{}=\-_`~()]\s") . $add;

			$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
		}

		return $value;
	}

	/**
	 * Limit this field's content by a number of words.
	 *
	 * @param int $numWords Number of words to limit by.
	 * @param string $add Ellipsis to add to the end of truncated string.
	 *
	 * @return string
	 */
	public function LimitWordCount($numWords = 26, $add = '...') {
		$value = trim(Convert::xml2raw($this->RAW()));
		$ret = explode(' ', $value, $numWords + 1);

		if(count($ret) <= $numWords - 1) {
			$ret = $value;
		} else {
			array_pop($ret);
			$ret = implode(' ', $ret) . $add;
		}

		return $ret;
	}

	/**
	 * Converts the current value for this StringField to lowercase.
	 *
	 * @return string
	 */
	public function LowerCase() {
		return mb_strtolower($this->RAW());
	}

	/**
	 * Converts the current value for this StringField to uppercase.
	 *
	 * @return string
	 */
	public function UpperCase() {
		return mb_strtoupper($this->RAW());
	}

	/**
	 * Plain text version of this string
	 *
	 * @return string Plain text
	 */
	public function NoHTML() {
		return $this->RAW();
	}
}
