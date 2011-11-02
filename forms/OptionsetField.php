<?php
/**
 * Set of radio buttons designed to emulate a dropdown.
 * It even uses the same constructor as a dropdown field.
 * 
 * This field allows you to ensure that a form element is submitted is not optional and is part of a fixed set of 
 * data. This field uses the input type of radio. It's a direct subclass of {@link DropdownField}, 
 * so the constructor and arguments are in the same format.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * new OptionsetField(
 *    $name = "Foobar",
 *    $title = "FooBar's optionset",
 *    $source = array(
 *       "1" => "Option 1",
 *       "2" => "Option 2",
 *       "3" => "Option 3",
 *       "4" => "Option 4",
 *       "5" => "Option 5"
 *    ),
 *    $value = "1"
 * );
 * </code>
 * 
 * You can use the helper functions on data object set to create the source array. eg: 
 * 
 * <code>
 * //Database request for the object
 * $myDoSet = DataObject::get("FooBars","");
 * if($myDoSet){
 *  // This returns an array of ID => Title
 *  $map = $myDoSet->toDropDownMap();
 *  
 *   // Instantiate the OptionsetField 
 *   $fieldset = new Fieldset(
 *     new OptionsetField(
 *      $name = "Foobar",
 *      $title = "FooBar's optionset",
 *      $source = $map,
 *      $value = $map[0]
 *     )
 *   );
 * }
 * 
 * // Pass the fields to the form constructor. etc
 * </code>
 * 
 * @see CheckboxSetField for multiple selections through checkboxes instead.
 * @see DropdownField for a simple <select> field with a single element.
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 * 
 * @package forms
 * @subpackage fields-basic
 */
class OptionsetField extends DropdownField {
	
	/**
	 * @var Array
	 */
	protected $disabledItems = array();
	
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
			$disabled = ($this->disabled || in_array($key, $this->disabledItems)) ? 'disabled="disabled"' : '';
			
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
	
	/**
	 * Mark certain elements as disabled,
	 * regardless of the {@link setDisabled()} settings.
	 * 
	 * @param array $items Collection of array keys, as defined in the $source array
	 */
	function setDisabledItems($items) {
		$this->disabledItems = $items;
	}
	
	/**
	 * @return Array
	 */
	function getDisabledItems() {
		return $this->disabledItems;
	}
	
	function ExtraOptions() {
		return new DataObjectSet();
	}
}
?>