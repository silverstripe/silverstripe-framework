<?php
/**
 * Grouped dropdown, using <optgroup> tags.
 * 
 * $source parameter (from DropdownField) must be a two dimensional array.
 * The first level of the array is used for the <optgroup>, and the second
 * level are the <options> for each group.
 * 
 * Returns a <select> tag containing all the appropriate <option> tags, with
 * <optgroup> tags around the <option> tags as required.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * new GroupedDropdownField(
 *    $name = "dropdown",
 *    $title = "Simple Grouped Dropdown",
 *    $source = array(
 *       "numbers" => array(
 *       		"1" => "1",
 *       		"2" => "2",
 *       		"3" => "3",
 *       		"4" => "4"
 *    		),
 *       "letters" => array(
 *       		"1" => "A",
 *       		"2" => "B",
 *       		"3" => "C",
 *       		"4" => "D",
 *       		"5" => "E",
 *       		"6" => "F"
 *    		)
 *    )
 * )
 * </code>
 * 
 * @package forms
 * @subpackage fields-basic
 */
class GroupedDropdownField extends DropdownField {

	function Field($properties = array()) {
		$options = '';
		foreach($this->getSource() as $value => $title) {
			if(is_array($title)) {
				$options .= "<optgroup label=\"$value\">";
				foreach($title as $value2 => $title2) {
					$selected = $value2 == $this->value ? " selected=\"selected\"" : "";
					$options .= "<option$selected value=\"$value2\">$title2</option>";
				}
				$options .= "</optgroup>";
			} else { // Fall back to the standard dropdown field
				$selected = $value == $this->value ? " selected=\"selected\"" : "";
				$options .= "<option$selected value=\"$value\">$title</option>";
			}
		}

		return $this->createTag('select', $this->getAttributes(), $options);
	}

	function Type() {
		return 'groupeddropdown dropdown';
	}
	
}

