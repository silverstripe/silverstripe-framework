<?php
/**
 * Represents a short text field.
 * @package sapphire
 * @subpackage model
 */
class Varchar extends StringField {
	
	protected $size;
	 
 	/**
 	 * Construct a new short text field
 	 * @param $name string The name of the field
 	 * @param $size int The maximum size of the field, in terms of characters
 	 * @param $options array Optional parameters, e.g. array("nullifyEmpty"=>false). See {@link StringField::setOptions()} for information on the available options
 	 * @return unknown_type
 	 */
 	function __construct($name, $size = 50, $options = array()) {
		$this->size = $size ? $size : 50;
		parent::__construct($name, $options);
	}
	
 	/**
 	 * (non-PHPdoc)
 	 * @see DBField::requireField()
 	 */
	function requireField() {
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
			
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	/**
	 * Return the first letter of the string followed by a .
	 */
	function Initial() {
		if($this->hasValue()) return $this->value[0] . '.';
	}
	
	/**
	 * Ensure that the given value is an absolute URL.
	 */
	function URL() {
		if(ereg('^[a-zA-Z]+://', $this->value)) return $this->value;
		else return "http://" . $this->value;
	}

	/**
	 * Return the value of the field in rich text format
	 * @return string
	 */
	function RTF() {
		return str_replace("\n", '\par ', $this->value);
	}

	/**
	 * Returns the value of the string, limited to the specified number of characters
	 * @param $limit int Character limit
	 * @param $add string Extra string to add to the end of the limited string
	 * @return string
	 */
	function LimitCharacters($limit = 20, $add = "...") {
		$value = trim($this->value);
		return (strlen($value) > $limit) ? substr($value, 0, $limit) . $add : $value;
	}

	/**
	 * (non-PHPdoc)
	 * @see DBField::scaffoldFormField()
	 */
	public function scaffoldFormField($title = null, $params = null) {
		if ( !$this->nullifyEmpty ) {
			// We can have an empty field so we need to let the user specifically set null value in the field.
			return new NullableField(new TextField($this->name, $title));
		} else {
			return parent::scaffoldFormField($title);
		}
	}
}

?>
