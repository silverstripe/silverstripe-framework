<?php

/**
 * @package forms
 * @subpackage fields-basic
 */

/**
 * Single checkbox field, disabled
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxFieldDisabled extends CheckboxField {
	/**
	 * Returns a single checkbox field - used by templates.
	 */
	function Field() {
		$checked = '';
		if($this->value)
			$checked = " checked = \"checked\"";
		return "<input class=\"checkbox\" disabled=\"disabled\" type=\"checkbox\" id=\"" .
			$this->id() . "\" name=\"{$this->name}\"$checked />";
	}
}


?>