<?php
/**
 * Field that generates a heading tag.
 *
 * This can be used to add extra text in your forms.
 *
 * @package forms
 * @subpackage fields-dataless
 */
class HeaderField extends DatalessField {
	
	/**
	 * @var int $headingLevel The level of the <h1> to <h6> HTML tag. Default: 2
	 */
	protected $headingLevel = 2;
	
	function __construct($name, $title = null, $headingLevel = 2) {
		// legacy handling for old parameters: $title, $heading, ...
		// instead of new handling: $name, $title, $heading, ...
		$args = func_get_args();
		if(!isset($args[1]) || is_numeric($args[1])) {
			$title = (isset($args[0])) ? $args[0] : null;
			// Use "HeaderField(title)" as the default field name for a HeaderField; if it's just set to title then we risk
			// causing accidental duplicate-field creation.
			$name = 'HeaderField' . $title; // this means i18nized fields won't be easily accessible through fieldByName()
			$headingLevel = (isset($args[1])) ? $args[1] : null;
			$form = (isset($args[3])) ? $args[3] : null;
		} 
		
		if($headingLevel) $this->headingLevel = $headingLevel;
		
		parent::__construct($name, $title);
	}

	public function getHeadingLevel() {
		return $this->headingLevel;
	}
	
	public function setHeadingLevel($level) {
		$this->headingLevel = $level;
	}

	function getAttributes() {
		return array_merge(
			array(
				'id' => $this->ID(),
				'class' => $this->extraClass()
			),
			$this->attributes
		);
	}

	function Type() {
		return null;
	}

}
