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
	 * @param string $className (Deprecated: use addExtraClass())
	 * @param bool $allowHTML (Deprecated: use setAllowHTML())
	 * @param Form $form
	 */
	function __construct($name, $title, $className = null, $allowHTML = false, $form = null) {
		// legacy handling for old parameters: $title, $heading, ...
		// instead of new handling: $name, $title, $heading, ...
		$args = func_get_args();
		if(!isset($args[1])) {
			$title = (isset($args[0])) ? $args[0] : null;
			$name = $title;
			$classname = (isset($args[1])) ? $args[1] : null;
			$allowHTML = (isset($args[2])) ? $args[2] : null;
			$form = (isset($args[3])) ? $args[3] : null;
		} 
		
		parent::__construct($name, $title, null, $allowHTML, $form);
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
			($this->getAllowHTML() ? $this->title : htmlentities($this->title))
		);
	}
}
?>