<?php
/**
 * Read-only complement of {@link DropdownField}.
 * Shows the "human value" of the dropdown field for the currently selected value.
 * @package forms
 * @subpackage fields-basic
 */
class LookupField extends DropdownField {

	protected $readonly = true;
	
	/**
	 * Returns a readonly span containing the correct value.
	 */
	function Field($properties = array()) {
		$source = $this->getSource();
		
		
		// Normalize value to array to simplify further processing
		$values = (is_array($this->value) || is_object($this->value)) ? $this->value : array(trim($this->value));

		$mapped = array();
		if($source instanceof SQLMap) {
			foreach($values as $value) $mapped[] = $source->getItem($value);
		} else if($source instanceof ArrayAccess || is_array($source)) {
			foreach($values as $value) {
				if(isset($source[$value])) $mapped[] = $source[$value];
			}
		} else {
			$mapped = array();
		}

		// Don't check if string arguments are matching against the source,
		// as they might be generated HTML diff views instead of the actual values
		if($this->value && !$mapped) {
			$mapped = array(trim($this->value));
			$values = array();
		}
		
		if($mapped) {
			$attrValue = implode(', ', array_values($mapped));
			if(!$this->dontEscape) $attrValue = Convert::raw2xml($attrValue);
			$inputValue = implode(', ', array_values($values)); 
		} else {
			$attrValue = "<i>(none)</i>";
			$inputValue = '';
		}

		return "<span class=\"readonly\" id=\"" . $this->id() .
			"\">$attrValue</span><input type=\"hidden\" name=\"" . $this->name .
			"\" value=\"" . $inputValue . "\" />";
	}
	
	function performReadonlyTransformation() {
		$clone = clone $this;
		return $clone;
	}

	function Type() { 
		return "lookup readonly";
	}
	
	/**
	 * Override parent behaviour by not merging arrays.
	 */
	function getSource() {
		return $this->source;
	}
}

