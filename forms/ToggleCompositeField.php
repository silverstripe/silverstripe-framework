<?php
/**
 * Allows visibility of a group of fields to be toggled using '+' and '-' icons
 */
class ToggleCompositeField extends CompositeField {
	
	/**
	 * @var $headingLevel int
	 */
	public $headingLevel = 2;
	
	function __construct($title, $children) {
		$this->title = $title;
		$this->name = ereg_replace('[^A-Za-z0-9]','',$this->title);

		$this->startClosed(true);
		
		parent::__construct($children);
	}
	
	public function FieldHolder() {
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("sapphire/javascript/ToggleCompositeField.js");
		
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