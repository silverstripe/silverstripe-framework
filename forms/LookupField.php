<?php

/**
 * Read-only complement of {@link DropdownField}.
 *
 * Shows the "human value" of the dropdown field for the currently selected 
 * value.
 *
 * @package forms
 * @subpackage fields-basic
 */
class LookupField extends DropdownField {

	/**
	 * @var boolean $readonly
	 */
	protected $readonly = true;
	
	/**
	 * Returns a readonly span containing the correct value.
	 *
	 * @param array $properties
	 *
	 * @return string
	 */
	public function Field($properties = array()) {
		$source = $this->getSource();
		
		// Normalize value to array to simplify further processing
		if(is_array($this->value) || is_object($this->value)) {
			$values = $this->value;
		} else {
			$values = array(trim($this->value));
		}

		$mapped = array();

		if($source instanceof SQLMap) {
			foreach($values as $value) {
				$mapped[] = $source->getItem($value);
			}
		} else if($source instanceof ArrayAccess || is_array($source)) {
			$source = ArrayLib::flatten($source);
			
			foreach($values as $value) {
				if(isset($source[$value])) {
					$mapped[] = $source[$value];
				}
			}
		} else {
			$mapped = array();
		}

		// Don't check if string arguments are matching against the source,
		// as they might be generated HTML diff views instead of the actual values
		if($this->value && !is_array($this->value) && !$mapped) {
			$mapped = array(trim($this->value));
			$values = array();
		}
		
		if($mapped) {
			$attrValue = implode(', ', array_values($mapped));
			
			if(!$this->dontEscape) {
				$attrValue = Convert::raw2xml($attrValue);
			}

			$inputValue = implode(', ', array_values($values)); 
		} else {
			$attrValue = "<i>(none)</i>";
			$inputValue = '';
		}

		return "<span class=\"readonly\" id=\"" . $this->id() .
			"\">$attrValue</span><input type=\"hidden\" name=\"" . $this->name .
			"\" value=\"" . $inputValue . "\" />";
	}
	
	/**
	 * @return LookupField
	 */
	public function performReadonlyTransformation() {
		$clone = clone $this;

		return $clone;
	}

	/**
	 * @return string
	 */
	public function Type() {
		return "lookup readonly";
	}
	
	/**
	 * Override parent behavior by not merging arrays.
	 *
	 * @return array
	 */
	public function getSource() {
		return $this->source;
	}
}

