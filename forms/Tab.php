<?php
/**
 * Implements a single tab in a {@link TabSet}.
 * @package forms
 * @subpackage fields-structural
 */
class Tab extends CompositeField {
	protected $tabSet;
	
	public function __construct($name) {
		$args = func_get_args();
		$name = array_shift($args);

		$this->id = preg_replace('/[^0-9A-Za-z]+/', '', $name);
		$this->title = preg_replace('/([a-z0-9])([A-Z])/', '\\1 \\2', $name);
		$this->name = $name;
		
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
			if($name == $child->Name()) return $child;
		}
	}
}




?>
