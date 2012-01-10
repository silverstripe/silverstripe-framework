<?php
/**
 * An abstract base class for the string field types (i.e. Varchar and Text)
 * @package sapphire
 * @subpackage model
 * @author Pete Bacon Darwin
 *
 */
abstract class StringField extends DBField {
	protected $nullifyEmpty = true;

	/**
	 * Construct a string type field with a set of optional parameters
	 * @param $name string The name of the field
	 * @param $options array An array of options e.g. array('nullifyEmpty'=>false).  See {@link StringField::setOptions()} for information on the available options
	 */
	function __construct($name = null, $options = array()) {
		// Workaround: The singleton pattern calls this constructor with true/1 as the second parameter, so we must ignore it
		if(is_array($options)){
			$this->setOptions($options);
		}
		parent::__construct($name);
	}
	
	/**
	 * Update the optional parameters for this field.
	 * @param $options array of options
	 * The options allowed are:
	 *   <ul><li>"nullifyEmpty"
	 *       This is a boolean flag.
	 *       True (the default) means that empty strings are automatically converted to nulls to be stored in the database.
	 *       Set it to false to ensure that nulls and empty strings are kept intact in the database.
	 *   </li></ul>
	 * @return unknown_type
	 */
	function setOptions(array $options = array()) {
		if(array_key_exists("nullifyEmpty", $options)) {
			$this->nullifyEmpty = $options["nullifyEmpty"] ? true : false;
		}
	}
	
	/**
	 * Set whether this field stores empty strings rather than converting them to null
	 * @param $value boolean True if empty strings are to be converted to null
	 */
	function setNullifyEmpty($value) {
		$this->nullifyEmpty = ($value ? true : false);
	}
	/**
	 * Get whether this field stores empty strings rather than converting them to null
	 * @return bool True if empty strings are to be converted to null
	 */
	function getNullifyEmpty() {
		return $this->nullifyEmpty;
	}

	/**
	 * (non-PHPdoc)
	 * @see core/model/fieldtypes/DBField#hasValue()
	 */
	function hasValue() {
		return ($this->value || $this->value == '0') || ( !$this->nullifyEmpty && $this->value === '');
	}

	/**
	 * (non-PHPdoc)
	 * @see core/model/fieldtypes/DBField#prepValueForDB($value)
	 */
	function prepValueForDB($value) {
		if ( !$this->nullifyEmpty && $value === '' ) {
			return "'" . Convert::raw2sql($value) . "'";
		} else {
			return parent::prepValueForDB($value);
		}
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
	function LimitCharacters($limit = 20, $add = '...') {
		$value = trim($this->value);
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
	 * Return another DBField object with this value in lowercase.
	 */
	function Lower() {
		return DBField::create(get_class($this), mb_strtolower($this->value), $this->name);
	}

	/**
	 * Return another DBField object with this value in uppercase.
	 */
	function Upper() {
		return DBField::create(get_class($this), mb_strtoupper($this->value), $this->name);
	}

}
