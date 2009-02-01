<?php
/**
 * Multi-line listbox field, created from a <select> tag.
 * @package forms
 * @subpackage fields-basic
 */
class ListboxField extends DropdownField {
	protected $source;
	protected $size;
	protected $multiple;
	
	/**
	 * Creates a new dropdown field.
	 * @param name The field name
	 * @param title The field title
	 * @param source An map of the dropdown items
	 * @param value You can pass an array of values or a single value like a drop down to be selected
	 * @param form The parent form
	 */
	function __construct($name, $title = "", $source = array(), $value = array(), $size = null, $multiple = null, $form = null) {
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
	 * Sets the number of rows high this field should be
	 */
	function setSize($i){
		$this->size = $i;
	}
	
	/** 
	* Sets this field to have a muliple select attribute
	*/
	function setMultiple($bool){
		$this->multiple = $bool;
	}
}

?>