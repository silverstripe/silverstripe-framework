<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents an multi-select enumeration field.
 * @package sapphire
 * @subpackage model
 */
class MultiEnum extends Enum {
	function __construct($name, $enum = NULL, $default = NULL) {
		// MultiEnum needs to take care of its own defaults
		parent::__construct($name, $enum, null);

		// Validate and assign the default
		$this->default = null;
		if($default) {
			$defaults = preg_split('/ *, */',trim($default));
			foreach($defaults as $thisDefault) {
				if(!in_array($thisDefault, $this->enum)) {
					user_error("Enum::__construct() The default value '$thisDefault' does not match "
						. "any item in the enumeration", E_USER_ERROR);
					return;
				}
			}
			$this->default = implode(',',$defaults);
		}
	}

	function requireField(){
		$defaultClause = $this->default ? " default '". Convert::raw2sql($this->default) . "'" : "";
		DB::requireField($this->tableName, $this->name, "set('" . implode("','", $this->enum) . "') character set utf8 collate utf8_general_ci$defaultClause");
	}
	
	
	/**
	 * Return a {@link CheckboxSetField} suitable for editing this field 
	 */
	function formField($title = null, $name = null, $hasEmpty = false, $value = "", $form = null) {
		if(!$title) $title = $this->name;
		if(!$name) $name = $this->name;

		$field = new CheckboxSetField($name, $title, $this->enumValues($hasEmpty), $value, $form);
			
		return $field;
	}
}

 ?>