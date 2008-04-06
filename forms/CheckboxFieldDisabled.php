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
		$attributes = array(
			'type' => 'checkbox',
			'class' => $this->extraClass() . " text",
			'id' => $this->id(),
			'name' => $this->attrName(),
			'tabindex' => $this->getTabIndexHTML(),
			'checked' => ($this->value) ? 'checked' : false,
			'disabled' => 'disabled' 
		);
		
		return $this->createTag('input', $attributes);
	}
}


?>