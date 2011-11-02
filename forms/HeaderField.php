<?php
/**
 * Field that generates a heading tag.
 * This can be used to add extra text in your forms.
 * @package forms
 * @subpackage fields-dataless
 */
class HeaderField extends DatalessField {
	
	/**
	 * @var int $headingLevel The level of the <h1> to <h6> HTML tag. Default: 2
	 */
	protected $headingLevel = 2;
	
	function __construct($name, $title = null, $headingLevel = 2, $allowHTML = false, $form = null) {
		// legacy handling for old parameters: $title, $heading, ...
		// instead of new handling: $name, $title, $heading, ...
		$args = func_get_args();
		if(!isset($args[1]) || is_numeric($args[1])) {
			$title = (isset($args[0])) ? $args[0] : null;
			// Use "HeaderField(title)" as the default field name for a HeaderField; if it's just set to title then we risk
			// causing accidental duplicate-field creation.
			$name = 'HeaderField' . $title; // this means i18nized fields won't be easily accessible through fieldByName()
			$headingLevel = (isset($args[1])) ? $args[1] : null;
			$allowHTML = (isset($args[2])) ? $args[2] : null;
			$form = (isset($args[3])) ? $args[3] : null;
		} 
		
		if($headingLevel) $this->headingLevel = $headingLevel;
		$this->allowHTML = $allowHTML;
		
		parent::__construct($name, $title, null, $allowHTML, $form);
	}
	
	function Field() {
		$attributes = array(
			'class' => $this->extraClass(),
			'id' => $this->id()
		);
		return $this->createTag(
			"h{$this->headingLevel}",
			$attributes,
			($this->getAllowHTML() ? $this->title : Convert::raw2xml($this->title))
		);
	}
}
?>