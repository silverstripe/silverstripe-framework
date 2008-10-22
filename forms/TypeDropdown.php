<?php

/**
 * @package forms
 * @subpackage fields-relational
 */

/**
 * Create a dropdown from all instances of a class
 * @package forms
 * @subpackage fields-relational
 */
class TypeDropdown extends DropdownField {
	/** 
	 * Create a type dropdown field. 
	 * @param string $name The field name 
	 * @param string $title The field title 
	 * @param string $className The class name of the related class 
	 * @param int $value The current value 
	 * @param Form $form The parent form 
	 * @param boolean $includeNone Include 'None' in the dropdown 
	 * @param string $titleField The field on the object to use as the title in the dropdown 
	 */
	 function __construct($name, $title, $className, $value = null, $form = null, $includeNone = true, $titleField = 'Title') {
		$options = DataObject::get($className);
		
		$optionArray = $includeNone ? array('0' => _t('TypeDropdown.NONE', 'None')) : array();
		
		if($options) foreach($options as $option) {
			$optionArray[$option->ID] = $option->$titleField;
		}
			
		parent::__construct( $name, $title, $optionArray, $value, $form );
	}
}
?>
