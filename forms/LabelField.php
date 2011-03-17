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
	function __construct($name, $title, $form = null) {
		// legacy handling for old parameters: $title, $heading, ...
		// instead of new handling: $name, $title, $heading, ...
		$args = func_get_args();
		if(!isset($args[1])) {
			$title = (isset($args[0])) ? $args[0] : null;
			$name = $title;
			$form = (isset($args[3])) ? $args[3] : null;
		} 
		
		parent::__construct($name, $title, $form);
	}
	
	/**
	 * Returns a label containing the title, and an HTML class if given.
	 */
	function Field() {
		$attributes = array(
			'class' => $this->extraClass(),
			'id' => $this->id()
		);
		return $this->createTag(
			'label',
			$attributes,
			($this->getAllowHTML() ? $this->title : Convert::raw2xml($this->title))
		);
	}
}
?>