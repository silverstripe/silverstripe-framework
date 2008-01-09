<?php

/**
 * @package forms
 * @subpackage fields-formattedinput
 */

/**
 * Duplicate of {@link PasswordField}.
 * @package forms
 * @subpackage fields-formattedinput Use {@link PasswordField}
 */
class EncryptField extends TextField {
	function Field() {
		return "<input class=\"text\" type=\"password\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />";
	}
}

?>