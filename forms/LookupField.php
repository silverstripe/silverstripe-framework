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
	function Field() {
		
		if(trim($this->value) || $this->value === '0') {
			$this->value = trim($this->value);
			$source = $this->getSource();
			if(is_array($source)) {
				$mappedValue = isset($source[$this->value]) ? $source[$this->value] : null;
			} elseif($source instanceof SQLMap) {
				$mappedValue = $source->getItem($this->value);
			}
		}
		
		if(!isset($mappedValue)) $mappedValue = "<i>(none)</i>";

		if($this->value) {
			$val = $this->dontEscape
				? ($this->reserveNL?Convert::raw2xml($this->value):$this->value)
				: Convert::raw2xml($this->value);
		} else {
			$val = '<i>(none)</i>';
		}

		$valforInput = $this->value ? Convert::raw2att($val) : "";

		return "<span class=\"readonly\" id=\"" . $this->id() .
			"\">$mappedValue</span><input type=\"hidden\" name=\"" . $this->name .
			"\" value=\"" . $valforInput . "\" />";
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

?>