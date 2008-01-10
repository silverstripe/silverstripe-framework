<?php

/**
 * @package forms
 * @subpackage fields-basic
 */

/**
 * Read-only complement of {@link DropdownField}.
 * Shows the "human value" of the dropdown field for the currently selected value.
 * @package forms
 * @subpackage fields-basic
 */
class LookupField extends DropdownField {

	/**
	 * Returns a readonly span containing the correct value.
	 */
	function Field() {
		if(trim($this->value)) {
			$this->value = trim($this->value);
			if(is_array($this->source)) {
				$mappedValue = isset($this->source[$this->value]) ? $this->source[$this->value] : null;
			} else {
				$mappedValue = $this->source->getItem($this->value);
			}
		}
		
		if(!isset($mappedValue)) {
			$mappedValue = "<i>(none)</i>";
		}

		if($this->value) {
			$val = $this->dontEscape
				? ($this->reserveNL?Convert::raw2xml($this->value):$this->value)
				: Convert::raw2xml($this->value);
		} else {
			$val = '<i>(none)</i>';
		}

		$valforInput = $this->value
			? Convert::raw2att($val)
			: "";

		return "<span class=\"readonly\" id=\"" . $this->id() .
			"\">$mappedValue</span><input type=\"hidden\" name=\"" . $this->name .
			"\" value=\"" . $valforInput . "\" />";
	}
	function performReadonlyTransformation() {
		return $this;
	}
	function Type() { 
		return "lookup readonly";
	}
}

?>
