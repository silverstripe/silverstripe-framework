<?php
/**
 * Multi-line listbox field, created from a <select> tag.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * new ListboxField(
 *    $name = "pickanumber",
 *    $title = "Pick a number",
 *    $source = array(
 *       "1" => "one",
 *       "2" => "two",
 *       "3" => "three"
 *    ),
 *    $value = 1
 * )
 * </code> 
 * 
 * @see DropdownField for a simple <select> field with a single element.
 * @see CheckboxSetField for multiple selections through checkboxes.
 * @see OptionsetField for single selections via radiobuttons.
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 * 
 * @package forms
 * @subpackage fields-basic
 */
class ListboxField extends DropdownField {

	/**
	 * The size of the field in rows.
	 * @var int
	 */
	protected $size;

	/**
	 * Should the user be able to select multiple
	 * items on this dropdown field?
	 * 
	 * @var boolean
	 */
	protected $multiple = false;
	
	/**
	 * Creates a new dropdown field.
	 * 
	 * @param string $name The field name
	 * @param string $title The field title
	 * @param array $source An map of the dropdown items
	 * @param string|array $value You can pass an array of values or a single value like a drop down to be selected
	 * @param int $size Optional size of the select element
	 * @param form The parent form
	 */
	function __construct($name, $title = '', $source = array(), $value = '', $size = null, $multiple = false, $form = null) {
		if($size) $this->size = $size;
		if($multiple) $this->multiple = $multiple;
		parent::__construct($name, $title, $source, $value, $form);
	}
	
	/**
	 * Returns a <select> tag containing all the appropriate <option> tags
	 */
	function Field() {
		$size = '';
		$multiple = '';
		
		if($this->size) $size = "size=\"$this->size\"";
		
		if($this->multiple) {
			$multiple = "multiple=\"multiple\"";
			$this->name .= '[]';
		}
		
		$options = "";
		
		// We have an array of values
		if(is_array($this->value)){
			// Loop through and figure out which values were selected.
					
			foreach($this->getSource() as $value => $title) {
				// Loop through the array of values to find out if this value is selected.
				$selected = "";
				foreach($this->value as $v){
					if($value == $v) {
						$selected = " selected=\"selected\"";
						break;
					}
				}
				$options .= "<option$selected value=\"$value\">$title</option>\n";
			}
		}else{
			// Listbox was based a singlular value, so treat it like a dropdown.
			foreach($this->getSource() as $value => $title) {
				$selected = $value == $this->value ? " selected=\"selected\"" : ""; 
				$options .= "<option$selected value=\"$value\">$title</option>";
			}
		}
		
		$id = $this->id();
		return "<select $size $multiple name=\"$this->name\" id=\"$id\">$options</select>";
	}
	
	/** 
	 * Sets the size of this dropdown in rows.
	 * @param int $size The height in rows (e.g. 3)
	 */
	function setSize($size) {
		$this->size = $size;
	}
	
	/** 
	 * Sets this field to have a muliple select attribute
	 * @param boolean $bool
	 */
	function setMultiple($bool) {
		$this->multiple = $bool;
	}
	
}
?>