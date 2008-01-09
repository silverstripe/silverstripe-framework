<?php

/**
 * @package forms
 * @subpackage validators
 */

/**
 * Custom javascript validation
 * @package forms
 * @subpackage validators
 * @deprecated How is this better than / different from {@link CustomRequiredFields}?
 */
class CustomValidator extends Validator {
	protected $javascriptCode;
	function __construct($javascriptCode) {
		$this->javascriptCode = $javascriptCode;
	}
	
	function javascript() {
		return $this->javascriptCode;
	}
	
	function php($data) {
		$valid = true;
		foreach($this->form->Fields() as $field) {
			$valid = ($field->validate($this) && $valid);
		}
		return $valid;
	}
}

?>