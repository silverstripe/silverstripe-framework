<?php

class LookupField extends DropdownField {

	/**
	 * Returns a readonly span containing the correct value.
	 */
	function Field() {
		if(trim($this->value)) {
			$this->value = trim($this->value);
			if(is_array($this->source)) $mappedValue = $this->source[$this->value];
			else $mappedValue = $this->source->getItem($this->value);
		}
		
		if(!$mappedValue)
			$mappedValue = "<i>(none)</i>";

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