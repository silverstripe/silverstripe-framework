<?php

/**
 * @package forms
 * @subpackage actions
 */

/**
 * Action that will only be shown if javascript is disabled.
 * @package forms
 * @subpackage actions
 */
class NoScriptFormAction extends FormAction {
	
	/**
	* @desc Overload the Field attribute to include noscript tags
	* Allows the input tags to only be shown if javascript is disabled.
	*/
	function Field(){
		return "<noscript>". parent::Field() . "</noscript>";
	}
	
}

?>