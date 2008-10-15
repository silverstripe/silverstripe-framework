<?php
/**
 * Set of radio buttons designed to emulate a dropdown.
 * It even uses the same constructor as a dropdown field.
 * @package forms
 * @subpackage fields-basic
 */
class OptionsetField extends DropdownField {
	
	/**
	 * Creates a new optionset field.
	 * @param name The field name
	 * @param title The field title
	 * @param source An map of the dropdown items
	 * @param value The current value
	 * @param form The parent form
	 */
	function __construct($name, $title = "", $source = array(), $value = "", $form = null) {
		parent::__construct($name, $title, $source, $value, $form);
	}

	/**
	 * Create a UL tag containing sets of radio buttons and labels.  The IDs are set to
	 * FieldID_ItemKey, where ItemKey is the key with all non-alphanumerics removed.
	 * 
	 * @todo Should use CheckboxField FieldHolder rather than constructing own markup.
	 */
	function Field() {
		$options = '';
		$odd = 0;
		$source = $this->getSource();
		foreach($source as $key => $value) {
			$itemID = $this->id() . "_" . ereg_replace('[^a-zA-Z0-9]+','',$key);
		
			if($key == $this->value/* || $useValue */) {
				$useValue = false;
				$checked = " checked=\"checked\"";
			} else {
				$checked="";
			}
			
			$odd = ($odd + 1) % 2;
			$extraClass = $odd ? "odd" : "even";
			$extraClass .= " val" . preg_replace('/[^a-zA-Z0-9\-\_]/','_', $key);
			$disabled = $this->disabled ? 'disabled="disabled"' : '';
			
			$options .= "<li class=\"".$extraClass."\"><input id=\"$itemID\" name=\"$this->name\" type=\"radio\" value=\"$key\"$checked $disabled class=\"radio\" /> <label for=\"$itemID\">$value</label></li>\n";
		}
		$id = $this->id();
		return "<ul id=\"$id\" class=\"optionset {$this->extraClass()}\">\n$options</ul>\n";
	}
	
	protected $disabled = false;
	function setDisabled($val) {
		$this->disabled = $val;
	}
	
	function performReadonlyTransformation() {
		// Source and values are DataObject sets.
		$items = $this->getSource();
		$field = new LookupField($this->name,$this->title ? $this->title : "" ,$items,$this->value);
		$field->setForm($this->form);
		$field->setReadonly(true);
		return $field;
	}
	
	function ExtraOptions() {
		return new DataObjectSet();
	}
}
?>