<?php

/**
 * @package cms
 */

/**
 * Displays a summary of instances of a form submitted to the website
 * @package cms
 */
class SubmittedFormReportField extends FormField {
	
	/**
	 * Displays the form (without defaults) submitted as it appears on the front of the site
	 * Users will use this instance of the form to filter results
	 */
	function Form() {
		/*return $this->form->Form();*/
	}
	
	function Field() {
		Requirements::css("sapphire/css/SubmittedFormReportField.css");
		
		return $this->renderWith("SubmittedFormReportField");
	}
	
	function Submissions() {
		return $this->form->getRecord()->Submissions();
	}
}
?>