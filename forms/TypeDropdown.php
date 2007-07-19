<?php
/**
 * Create a dropdown from all instances of a class
 */
class TypeDropdown extends DropdownField {
	
	function __construct( $name, $title, $className, $value = null, $form = null ) {
		
		$options = DataObject::get( $className );
		
		$optionArray = array( '0' => 'None' );
		
		foreach( $options as $option )
			$optionArray[$option->ID] = $option->Title;
			
		parent::__construct( $name, $title, $optionArray, $value, $form );
	}
}
?>