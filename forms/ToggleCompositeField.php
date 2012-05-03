<?php
/**
 * Allows visibility of a group of fields to be toggled using '+' and '-' icons
 *
 * @package forms
 * @subpackage fields-structural
 */
class ToggleCompositeField extends CompositeField {
	
	/**
	 * @var $headingLevel int
	 */
	public $headingLevel = 2;
	
	function __construct($name, $title, $children) {
		$this->name = $name;
		$this->title = $title;

		$this->startClosed(true);
		
		parent::__construct($children);
	}
	
	public function FieldHolder($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(FRAMEWORK_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(FRAMEWORK_DIR . "/javascript/ToggleCompositeField.js");
		
		$obj = $properties ? $this->customise($properties) : $this;
		
		return $obj->renderWith($this->getTemplates());
	}	
	
	/**
	 * Determines if the field should render open or closed by default.
	 * 
	 * @param boolean
	 */
	public function startClosed($bool) {
		($bool) ? $this->addExtraClass('startClosed') : $this->removeExtraClass('startClosed');
	}
	
	/**
	 * @return string
	 */
	public function HeadingLevel() {
		return $this->headingLevel;
	}

	public function Type() {
		return ' toggleCompositeField';
	}
}

