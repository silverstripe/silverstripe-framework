<?php
/**
 * Allows visibility of a group of fields to be toggled using '+' and '-' icons
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
	
	public function FieldHolder() {
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/prototype_improvements.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/ToggleCompositeField.js");
		
		return $this->renderWith("ToggleCompositeField");
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
	 * @return String
	 */
	public function HeadingLevel() {
		return $this->headingLevel;
	}

	public function Type() {
		return ' toggleCompositeField';
	}
}

?>