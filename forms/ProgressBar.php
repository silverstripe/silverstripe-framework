<?php

/**
 * @package forms
 * @subpackage fields-dataless
 */

/**
 * Displays a progress bar in a form.
 * These are currently only linked to Batch Processes.
 * @see BatchProcess
 * @package forms
 * @subpackage fields-dataless
 */
class ProgressBar extends FormField {

	function FieldHolder() {
		Requirements::javascript('sapphire/javascript/ProgressBar.js');
		Requirements::css('sapphire/css/ProgressBar.css');
		
		return $this->renderWith('ProgressBar');
	}

}
?>
