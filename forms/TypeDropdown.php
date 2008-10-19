<?php
/**
 * Create a dropdown from all instances of a class.
 *
 * @package forms
 * @subpackage fields-relational
 *
 * @deprecated 2.3 Misleading naming, and having an entire class dedicated
 * to just setting the source mapping for a DropdownField is overkill. Just
 * use a standard DropdownField instead.
 */
class TypeDropdown extends DropdownField {
	
	/**
	 * @var string $titleFieldName The name of the DataObject property used for the dropdown options
	 */
	protected $titleFieldName = "Title";

	/**
	 * @param string $name
	 * @param string $title
	 * @param string $className 
	 */
	function __construct( $name, $title, $className, $value = null, $form = null, $emptyString = null) {
		$options = DataObject::get($className);

		$optionArray = array( '0' => _t('TypeDropdown.NONE', 'None') );

		if($options) foreach( $options as $option ) {
			$optionArray[$option->ID] = $option->{$this->titleFieldName};
		}

		parent::__construct( $name, $title, $optionArray, $value, $form, $emptyString );
	}

	function setTitleFieldName($name) {
		$this->titleFieldName = $name;
	}
}
?>