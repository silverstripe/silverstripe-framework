<?php

/**
 * @package forms
 * @subpackage fields-dataless
 */

/**
 * Abstract class for all fields without data.
 * Labels, headings and the like should extend from this.
 * @package forms
 * @subpackage fields-dataless
 */
class DatalessField extends FormField {
	/**
	 * Function that returns whether this field contains data.
	 * Always returns false. 
	 */
	function hasData() { return false; }
	
	/**
	 * Returns the field's representation in the form.
	 * For dataless fields, this defaults to $Field.
	 */
	function FieldHolder() {
		return $this->Field();
	}

	/**
	 * Returns the field's representation in a field group.
	 * For dataless fields, this defaults to $Field.
	 */
	function SmallFieldHolder() {
		return $this->Field();
	}

	/**
	 * Returns a readonly version of this field
	 */
	function performReadonlyTransformation() {
		return $this;
	}
}
?>