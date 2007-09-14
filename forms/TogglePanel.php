<?php

class TogglePanel extends CompositeField {
	protected $closed = false;
	
	function __construct($title, $children, $startClosed = false) {
		$this->title = $title;
		$this->closed = $startClosed;
		$this->name = ereg_replace('[^A-Za-z0-9]','',$this->title);
		parent::__construct($children);
	}
	
	public function FieldHolder() {
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("sapphire/javascript/TogglePanel.js");
		
		return $this->renderWith("TogglePanel");
	}	
	
	public function setClosed($closed) {
		$this->closed = $closed;
	}
	public function getClosed() {
		return $this->closed;
	}
	
	public function ClosedClass() {
		if($this->closed) return " closed";
	}
	public function ClosedStyle() {
		if($this->closed) return "style=\"display: none\"";
	}

}

?>