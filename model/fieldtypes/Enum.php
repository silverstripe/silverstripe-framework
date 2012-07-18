<?php
/**
 * Class Enum represents an enumeration of a set of strings.
 *
 * See {@link DropdownField} for a {@link FormField} to select enum values.
 * 
 * @package framework
 * @subpackage model
 */
class Enum extends StringField {
	
	protected $enum, $default;
	
	public static $default_search_filter_class = 'ExactMatchFilter';
	
	/**
	 * Create a new Enum field.
	 * 
	 * Example usage in {@link DataObject::$db} with comma-separated string 
	 * notation ('Val1' is default)
	 *
	 * <code>
	 *  "MyField" => "Enum('Val1, Val2, Val3', 'Val1')"
	 * </code>
	 * 
	 * Example usage in in {@link DataObject::$db} with array notation 
	 * ('Val1' is default)
	 *
	 * <code>
	 * "MyField" => "Enum(array('Val1', 'Val2', 'Val3'), 'Val1')"
	 * </code>
	 * 
	 * @param enum: A string containing a comma separated list of options or an 
	 *				array of Vals.
	 * @param string The default option, which is either NULL or one of the 
	 *				 items in the enumeration.
	 */
	public function __construct($name = null, $enum = NULL, $default = NULL) {
		if($enum) {
			if(!is_array($enum)) {
				$enum = preg_split("/ *, */", trim($enum));
			}

			$this->enum = $enum;
			
			// If there's a default, then 		
			if($default) {
				if(in_array($default, $enum)) {
					$this->default = $default;
				} else {
					user_error("Enum::__construct() The default value '$default' does not match any item in the enumeration", E_USER_ERROR);
				}
				
			// By default, set the default value to the first item
			} else {
				$this->default = reset($enum);
			}
		}

		parent::__construct($name);
	}
	
	/**
	 * @return void
	 */
	public function requireField() {
		$parts = array(
			'datatype' => 'enum', 
			'enums' => Convert::raw2sql($this->enum), 
			'character set' => 'utf8', 
			'collate' => 'utf8_general_ci', 
			'default' => Convert::raw2sql($this->default), 
			'table' => $this->tableName, 
			'arrayValue' => $this->arrayValue
		);
		
		$values = array(
			'type' => 'enum', 
			'parts' => $parts
		);

		DB::requireField($this->tableName, $this->name, $values);
	}
	
	/**
	 * Return a dropdown field suitable for editing this field.
	 *
	 * @return DropdownField
	 */
	public function formField($title = null, $name = null, $hasEmpty = false, $value = "", $form = null, $emptyString = null) {
		if(!$title) $title = $this->name;
		if(!$name) $name = $this->name;

		$field = new DropdownField($name, $title, $this->enumValues($hasEmpty), $value, $form);
		$field->setEmptyString($emptyString);

		return $field;
	}

	/**
	 * @return DropdownField
	 */
	public function scaffoldFormField($title = null, $params = null) {
		return $this->formField($title);
	}

	/**
	 * @param string
	 *
	 * @return DropdownField
	 */
	public function scaffoldSearchField($title = null) {
		$anyText = _t('Enum.ANY', 'Any');
		return $this->formField($title, null, false, '', null, "($anyText)");
	}
	
	/**
	 * Returns the values of this enum as an array, suitable for insertion into 
	 * a {@link DropdownField}
	 *
	 * @param boolean
	 *
	 * @return array
	 */
	public function enumValues($hasEmpty = false) {
		return ($hasEmpty) ? array_merge(array('' => ''), ArrayLib::valuekey($this->enum)) : ArrayLib::valuekey($this->enum);
	}
}