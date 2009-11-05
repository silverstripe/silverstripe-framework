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
		parent::__construct($name, $enum, $default);
		
		// MultiEnums are allowed blank defaults
		if(!$default) $this->default = $default;
	}

	function requireField(){
		DB::requireField($this->tableName, $this->name, "set('" . implode("','", $this->enum) . "') character set utf8 collate utf8_general_ci default '{$this->default}'");
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