<?php
/**
 * Defines a set of tabs in a form.
 * The tabs are build with our standard tabstrip javascript library.  
 * By default, the HTML is generated using FieldHolder.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * new TabSet(
 * 	$name = "TheTabSetName",
 * 	new Tab(
 * 		$title='Tab one',
 * 		new HeaderField("A header"),
 * 		new LiteralField("Lipsum","Lorem ipsum dolor sit amet enim.")
 * 	),
 * 	new Tab(
 * 		$title='Tab two',
 * 		new HeaderField("A second header"),
 * 		new LiteralField("Lipsum","Ipsum dolor sit amet enim.")
 * 	)
 * )
 * </code>
 * 
 * @package forms
 * @subpackage fields-structural
 */
class TabSet extends CompositeField {
	
	/**
	 * @param string $name Identifier
	 * @param string $title (Optional) Natural language title of the tabset
	 * @param Tab|TabSet $unknown All further parameters are inserted as children into the TabSet
	 */
	public function __construct($name) {
		$args = func_get_args();
		
		$name = array_shift($args);
		if(!is_string($name)) user_error('TabSet::__construct(): $name parameter to a valid string', E_USER_ERROR);
		$this->name = $name;
		
		$this->id = $name;
		
		// Legacy handling: only assume second parameter as title if its a string,
		// otherwise it might be a formfield instance
		if(isset($args[0]) && is_string($args[0])) {
			$title = array_shift($args);
		}
		$this->title = (isset($title)) ? $title : FormField::name_to_label($name);
		
		if($args) foreach($args as $tab) {
			$isValidArg = (is_object($tab) && (!($tab instanceof Tab) || !($tab instanceof TabSet)));
			if(!$isValidArg) user_error('TabSet::__construct(): Parameter not a valid Tab instance', E_USER_ERROR);
			
			$tab->setTabSet($this);
		}
		
		parent::__construct($args);
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
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/loader.js');
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/prototype_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . "/jquery/jquery.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/jquery_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . "/tabstrip/tabstrip.js");
		Requirements::css(THIRDPARTY_DIR . "/tabstrip/tabstrip.css");
		
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
		if(strpos($name,'.') !== false)	list($name, $remainder) = explode('.',$name,2);
		else $remainder = null;
		
		foreach($this->children as $child) {
			if(trim($name) == trim($child->Name) || $name == $child->id) {
				if($remainder) {
					if($child->isComposite()) {
						return $child->fieldByName($remainder);
					} else {
						user_error("Trying to get field '$remainder' from non-composite field $child->class.$name", E_USER_WARNING);
						return null;
					}
				} else {
					return $child;
				}
			}
		}
	}

	/**
	 * Add a new child field to the end of the set.
	 */
	public function push($field) {
		parent::push($field);
		$field->setTabSet($this);
	}
	
	/**
	 * Inserts a field before a particular field in a FieldSet.
	 *
	 * @param FormField $item The form field to insert
	 * @param string $name Name of the field to insert before
	 */
	public function insertBefore($field, $insertBefore) {
		parent::insertBefore($field, $insertBefore);
		if($field instanceof Tab) $field->setTabSet($this);
		$this->sequentialSet = null;
	}
	
	public function insertAfter($field, $insertAfter) {
		parent::insertAfter($field, $insertAfter);
		if($field instanceof Tab) $field->setTabSet($this);
		$this->sequentialSet = null;
	}
	
	public function removeByName( $tabName, $dataFieldOnly = false ) {
		parent::removeByName( $tabName, $dataFieldOnly );
	}
}
?>