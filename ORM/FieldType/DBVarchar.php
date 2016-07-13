<?php

namespace SilverStripe\ORM\FieldType;

use NullableField;
use TextField;
use Config;
use SilverStripe\ORM\DB;

/**
 * Class Varchar represents a variable-length string of up to 255 characters, designed to store raw text
 *
 * @see DBHTMLText
 * @see DBHTMLVarchar
 * @see DBText
 *
 * @package framework
 * @subpackage orm
 */
class DBVarchar extends DBString {

	private static $casting = array(
		"Initial" => "Text",
		"URL" => "Text",
	);

	protected $size;

	/**
 	 * Construct a new short text field
 	 *
 	 * @param string $name The name of the field
 	 * @param int $size The maximum size of the field, in terms of characters
 	 * @param array $options Optional parameters, e.g. array("nullifyEmpty"=>false).
 	 *                       See {@link StringField::setOptions()} for information on the available options
 	 */
	public function __construct($name = null, $size = 50, $options = array()) {
		$this->size = $size ? $size : 50;
		parent::__construct($name, $options);
	}

	/**
	 * Allow the ability to access the size of the field programatically. This
	 * can be useful if you want to have text fields with a length limit that
	 * is dictated by the DB field.
	 *
	 * TextField::create('Title')->setMaxLength(singleton('SiteTree')->dbObject('Title')->getSize())
	 *
	 * @return int The size of the field
	 */
	public function getSize() {
		return $this->size;
	}

	/**
 	 * (non-PHPdoc)
 	 * @see DBField::requireField()
 	 */
	public function requireField() {
		$charset = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'charset');
		$collation = Config::inst()->get('SilverStripe\ORM\Connect\MySQLDatabase', 'collation');

		$parts = array(
			'datatype'=>'varchar',
			'precision'=>$this->size,
			'character set'=> $charset,
			'collate'=> $collation,
			'arrayValue'=>$this->arrayValue
		);

		$values = array(
			'type' => 'varchar',
			'parts' => $parts
		);

		DB::require_field($this->tableName, $this->name, $values);
	}

	/**
	 * Return the first letter of the string followed by a .
	 *
	 * @return string
	 */
	public function Initial() {
		if($this->exists()) {
			$value = $this->RAW();
			return $value[0] . '.';
		}
		return null;
	}

	/**
	 * Ensure that the given value is an absolute URL.
	 *
	 * @return string
	 */
	public function URL() {
		$value = $this->RAW();
		if(preg_match('#^[a-zA-Z]+://#', $value)) {
			return $value;
		} else {
			return "http://" . $value;
		}
	}

	/**
	 * Return the value of the field in rich text format
	 * @return string
	 */
	public function RTF() {
		return str_replace("\n", '\par ', $this->RAW());
	}

	public function scaffoldFormField($title = null) {
		if(!$this->nullifyEmpty) {
			// Allow the user to select if it's null instead of automatically assuming empty string is
			return new NullableField(new TextField($this->name, $title));
		} else {
			// Automatically determine null (empty string)
			return parent::scaffoldFormField($title);
		}
	}
}


