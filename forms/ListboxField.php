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
	function __construct($name, $title = '', $source = array(), $value = '', $size = null, $multiple = false) {
		if($size) $this->size = $size;
		if($multiple) $this->multiple = $multiple;
		parent::__construct($name, $title, $source, $value);
	}
	
	/**
	 * Returns a <select> tag containing all the appropriate <option> tags
	 */
	function Field($properties = array()) {
		if($this->multiple) {
			$this->name .= '[]';
		}
		
		$options = array();
		
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
				$options[] = new ArrayData(array(
					'Title' => $title,
					'Value' => $value,
					'Selected' => $selected,
				));
			}
		} else {
			// Listbox was based a singlular value, so treat it like a dropdown.
			foreach($this->getSource() as $value => $title) {
				$selected = $value == $this->value ? " selected=\"selected\"" : ""; 
				$options[] = new ArrayData(array(
					'Title' => $title,
					'Value' => $value,
					'Selected' => $selected,
				));
			}
		}
		
		$properties = array_merge($properties, array('Options' => new ArrayList($options)));
		return $this->customise($properties)->renderWith($this->getTemplate());
	}

	function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'multiple' => $this->multiple,
				'size' => $this->size
			)
		);
	}
	
	/** 
	 * Sets the size of this dropdown in rows.
	 * @param int $size The height in rows (e.g. 3)
	 */
	function setSize($size) {
		$this->size = $size;
		return $this;
	}
	
	/** 
	 * Sets this field to have a muliple select attribute
	 * @param boolean $bool
	 */
	function setMultiple($bool) {
		$this->multiple = $bool;
		return $this;
	}
	
	function setSource($source) {
		if($source) {
			$hasCommas = array_filter(array_keys($source), create_function('$key', 'return strpos($key, ",") !== FALSE;'));
			if($hasCommas) {
				throw new InvalidArgumentException('No commas allowed in $source keys');
			}
		}
		
		parent::setSource($source);

		return $this;
	}
	
	/**
	 * @return String
	 */
	function dataValue() {
		if($this->value && $this->multiple && is_array($this->value)) {
			return implode(',', $this->value);
		} else {
			return parent::dataValue();
		}
	}
	
	function setValue($val) {
		if($val) {
			if(!$this->multiple && is_array($val)) {
				throw new InvalidArgumentException('No array values allowed with multiple=false');
			}

			if($this->multiple) {
				$parts = (is_array($val)) ? $val : preg_split("/ *, */", trim($val));
				if(ArrayLib::is_associative($parts)) {
					throw new InvalidArgumentException('No associative arrays allowed multiple=true');
				}

				// Doesn't check against unknown values in order to allow for less rigid data handling.
				// They're silently ignored and overwritten the next time the field is saved.

				parent::setValue($parts);
			} else {
				if(!in_array($val, array_keys($this->source))) {
					throw new InvalidArgumentException(sprintf(
						'Invalid value "%s" for multiple=false', 
						Convert::raw2xml($val)
					));
				}

				parent::setValue($val);
			}
		} else {
			parent::setValue($val);
		}
		
		return $this;
	}
	
}
