<?php
/**
 * @package forms
 * @subpackage fields-structural
 */

/**
 * Defines a set of tabs in a form.
 * The tabs are build with our standard tabstrip javascript library.  By default, the HTML is
 * generated using FieldHolder.
 * @package forms
 * @subpackage fields-structural
 */
class TabSet extends CompositeField {
	public function __construct($id) {
		$tabs = func_get_args();
		$this->id = array_shift($tabs);
		$this->title = $this->id;
		
		foreach($tabs as $tab) $tab->setTabSet($this);
		
		parent::__construct($tabs);
	}
	
	public function id() {
		if($this->tabSet) return $this->tabSet->id() . '_' . $this->id . '_set';
		else return $this->id;
	}
	
	/**
	 * Returns a tab-strip and the associated tabs.
	 * The HTML is a standardised format, containing a &lt;ul;
	 */
	public function FieldHolder() {
		Requirements::javascript("jsparty/loader.js");
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("jsparty/tabstrip/tabstrip.js");
		Requirements::css("jsparty/tabstrip/tabstrip.css");
		
		return $this->renderWith("TabSetFieldHolder");
	}
	
	/**
	 * Return a dataobject set of all this classes tabs
	 */
	public function Tabs() {
		return $this->children;
	}
	public function setTabs($children){
		$this->children = $children;
	}

	public function setTabSet($val) {
		$this->tabSet = $val;
	}
	public function getTabSet() {
		if(isset($this->tabSet)) return $this->tabSet;
	}
	
	/**
	 * Returns the named tab
	 */
	public function fieldByName($name) {
		foreach($this->children as $child) {
			if($name == $child->Name || $name == $child->id) return $child;
		}
	}

	/**
	 * Add a new child field to the end of the set.
	 */
	public function push($field) {
		parent::push($field);
		$field->setTabSet($this);
	}
	public function insertBefore($field, $insertBefore) {
		parent::insertBefore($field, $insertBefore);
		$field->setTabSet($this);
	}
	
	public function insertBeforeRecursive($field, $insertBefore, $level) {
		$level = parent::insertBeforeRecursive($field, $insertBefore, $level+1);
		if ($level === 0) $field->setTabSet($this);
		return $level;
	}
	
	public function removeByName( $tabName ) {
		parent::removeByName( $tabName );
	}
}
?>
