<?php

/**
 * @package forms
 * @subpackage fields-dataless
 */

/**
 * This field lets you put an arbitrary piece of HTML into your forms.
 * If there's not much behaviour around the HTML, it might not be worth going to the effort of
 * making a special field type for it.  So you can use LiteralField.  If you pass it a viewabledata object,
 * it will turn it into a string for you. 
 * @package forms
 * @subpackage fields-dataless
 */
class NoScriptField extends LiteralField {
	function Field() {
		return "<noscript>" . $this->FieldHolder() . "</noscript>";
	}
}

?>