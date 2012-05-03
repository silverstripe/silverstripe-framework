<?php
/**
 * Simple label tag. This can be used to add extra text in your forms.
 * Consider using a {@link ReadonlyField} if you need to display a label
 * AND a value.
 * 
 * @package forms
 * @subpackage fields-dataless
 */
class LabelField extends DatalessField {
	
	/**
	 * @param string $name
	 * @param string $title
	 * @param Form $form
	 */
	function __construct($name, $title) {
		// legacy handling for old parameters: $title, $heading, ...
		// instead of new handling: $name, $title, $heading, ...
		$args = func_get_args();
		if(!isset($args[1])) {
			$title = (isset($args[0])) ? $args[0] : null;
			$name = $title;
			$form = (isset($args[3])) ? $args[3] : null;
		} 
		
		parent::__construct($name, $title);
	}

}
