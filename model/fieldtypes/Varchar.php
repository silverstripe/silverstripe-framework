<?php
/**
 * Class Varchar represents a variable-length string of up to 255 characters, designed to store raw text
 *
 * @see HTMLText
 * @see HTMLVarchar
 * @see Text
 *
 * @package framework
 * @subpackage model
 */
class Varchar extends StringField {

	private static $casting = array(
		"Initial" => "Text",
		"URL" => "Text",
	);

	protected $size;

	/**
 	 * Construct a new short text field
 	 *
 	 * @param $name string The name of the field
 	 * @param $size int The maximum size of the field, in terms of characters
 	 * @param $options array Optional parameters, e.g. array("nullifyEmpty"=>false).
 	 *                       See {@link StringField::setOptions()} for information on the available options
 	 * @return unknown_type
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
		$parts = array(
			'datatype'=>'varchar',
			'precision'=>$this->size,
			'character set'=>'utf8',
			'collate'=>'utf8_general_ci',
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
	 */
	public function Initial() {
		if($this->exists()) return $this->value[0] . '.';
	}

	/**
	 * Ensure that the given value is an absolute URL.
	 */
	public function URL() {
		if(preg_match('#^[a-zA-Z]+://#', $this->value)) return $this->value;
		else return "http://" . $this->value;
	}

	/**
	 * Return the value of the field in rich text format
	 * @return string
	 */
	public function RTF() {
		return str_replace("\n", '\par ', $this->value);
	}

	/**
	 * (non-PHPdoc)
	 * @see DBField::scaffoldFormField()
	 */
	public function scaffoldFormField($title = null, $params = null) {
		if(!$this->nullifyEmpty) {
			// Allow the user to select if it's null instead of automatically assuming empty string is
			return new NullableField(new TextField($this->name, $title));
		} else {
			// Automatically determine null (empty string)
			return parent::scaffoldFormField($title);
		}
	}
}


