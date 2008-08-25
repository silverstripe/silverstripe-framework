<?php

/**
 * Grouped dropdown, using <optgroup> tags.
 * 
 * @deprecated - Please use DropdownField instead!
 * 
 * $source parameter (from DropdownField) must be a two dimensional array.
 * The first level of the array is used for the <optgroup>, and the second
 * level are the <options> for each group.
 * 
 * Returns a <select> tag containing all the appropriate <option> tags, with
 * <optgroup> tags around the <option> tags as required.
 * 
 * @package forms
 * @subpackage fields-basic
 */
class GroupedDropdownField extends DropdownField {

	function Field() {
		user_error('GroupedDropdownField is deprecated. Please use DropdownField instead.', E_USER_NOTICE);

		return parent::Field();
	}
}

?>