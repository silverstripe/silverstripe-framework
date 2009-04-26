<?php
/**
 * Represents an enumeration field.
 * @package sapphire
 * @subpackage model
 */
class Enum extends DBField {
	
	protected $enum, $default;
	
	/**
	 * Create a new Enum field.
	 * You should create an enum field like this: 
	 *      		"MyField" => "Enum('Val1, Val2, Val3', 'Val1')"
	 *	or: 		"MyField" => "Enum(Array('Val1', 'Val2', 'Val3'), 'Val1')"
	 *  but NOT: 	"MyField" => "Enum('Val1', 'Val2', 'Val3', 'Val1')"
	 * @param enum: A string containing a comma separated list of options or an array of Vals.
	 * @param default The default option, which is either NULL or one of the items in the enumeration.
	 */
	function __construct($name, $enum = NULL, $default = NULL) {
		if($enum) {
			if(!is_array($enum)){
				$enum = split(" *, *", trim($enum));
			}

			$this->enum = $enum;
			
			// If there's a default, then 		
			if($default) {
				if(in_array($default, $enum)) {
					$this->default = $default;
				} else {
					user_error("Enum::__construct() The default value does not match any item in the enumeration", E_USER_ERROR);
				}
				
			// By default, set the default value to the first item
			} else {
				$this->default = reset($enum);
			}
		}
		parent::__construct($name);
	}
	
	function requireField(){
		DB::requireField($this->tableName, $this->name, "enum('" . implode("','", $this->enum) . "') character set utf8 collate utf8_general_ci default '{$this->default}'");
	}
	
	/**
	 * Return a dropdown field suitable for editing this field 
	 */
	function formField($title = null, $name = null, $hasEmpty = false, $value = "", $form = null, $emptyString = null) {
		if(!$title) $title = $this->name;
		if(!$name) $name = $this->name;

		$field = new DropdownField($name, $title, $this->enumValues($hasEmpty), $value, $form, $emptyString);
			
		return $field;		
	}

	public function scaffoldFormField($title = null, $params = null) {
		return $this->formField($title);
	}
	
	function scaffoldSearchField($title = null) {
		$anyText = _t('Enum.ANY', 'Any');
		return $this->formField($title, null, false, '', null, "($anyText)");
	}
	
	/**
	 * Return the values of this enum, suitable for insertion into a dropdown field.
	 */
	function enumValues($hasEmpty = false) {
		return ($hasEmpty) ? array_merge(array('' => ''), ArrayLib::valuekey($this->enum)) : ArrayLib::valuekey($this->enum);
	}
}

?>