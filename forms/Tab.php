<?php

/**
 * @package forms
 * @subpackage fields-structural
 */

/**
 * Implements a single tab in a {@link TabSet}.
 * @package forms
 * @subpackage fields-structural
 */
class Tab extends CompositeField {
	protected $tabSet;
	
	public function __construct($title) {
		$args = func_get_args();
		$this->title = array_shift($args);
		$this->id = ereg_replace('[^0-9A-Za-z]+', '', $this->title);
		
		parent::__construct($args);
	}
	
	public function id() {
		return $this->tabSet->id() . '_' . $this->id;
	}
	
	public function Fields() {
		return $this->children;
	}
	
	public function setTabSet($val) {
		$this->tabSet = $val;
	}

	/**
	 * Returns the named field
	 */
	public function fieldByName($name) {
		foreach($this->children as $child) {
			if($name == $child->Name) return $child;
		}
	}
}




?>
