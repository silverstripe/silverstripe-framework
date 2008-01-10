<?php

/**
 * @package forms
 * @subpackage fields-basic
 */

/**
 * Dropdown field, created from a <select> tag.
 * @package forms
 * @subpackage fields-basic
 */
class DropdownField extends FormField {
	protected $source;
	protected $isSelected, $disabled;
	
	/**
	 * Creates a new dropdown field.
	 * @param $name The field name
	 * @param $title The field title
	 * @param $source An map of the dropdown items
	 * @param $value The current value
	 * @param $form The parent form
	 * @param $emptyString mixed Add an empty selection on to of the {source}-Array 
	 * 	(can also be boolean, which results in an empty string)
	 */
	function __construct($name, $title = "", $source = array(), $value = "", $form = null, $emptyString = null) {
		if(is_string($emptyString)) {
			$source = is_array($source) ? array(""=>$emptyString) + $source : array(""=>$emptyString);
		} elseif($emptyString === true) {
			$source = is_array($source) ? array(""=>"") + $source : array(""=>"");
		}
		$this->source = $source;
	
		parent::__construct($name, $title, $value, $form);
	}
	
	/**
	 * Returns a <select> tag containing all the appropriate <option> tags
	 */
	function Field() {
		$classAttr = '';
		$options = '';
		if($extraClass = trim($this->extraClass())) {
			$classAttr = "class=\"$extraClass\"";
		}
		if($this->source) foreach($this->source as $value => $title) {
			$selected = $value == $this->value ? " selected=\"selected\"" : "";
			if($selected && $this->value != 0) {
				$this->isSelected = true;
			}
			$options .= "<option$selected value=\"$value\">$title</option>";
		}
	
		$id = $this->id();
		$disabled = $this->disabled ? " disabled=\"disabled\"" : "";
		
		return "<select $classAttr $disabled name=\"$this->name\" id=\"$id\">$options</select>";
	}
	
	function isSelected(){
		return $this->isSelected;
	}
  
	function getSource() {
		return $this->source;
	}
  
	function setSource($source) {
		$this->source = $source;
	}

	function performReadonlyTransformation() {
		$field = new LookupField($this->name, $this->title, $this->source);
		$field->setValue($this->value);
		$field->setForm($this->form);
		return $field;
	}
	
	function extraClass(){
		$ret = parent::extraClass();
		if($this->extraClass) $ret .= " $this->extraClass";
		return $ret;
	}
}

/**
 * Dropdown field with an add button to the right.
 * The class is originally designed to be used by RelatedDataEditor
 * However, it can potentially be used as a normal dropdown field with add links in a normal form
 * @package forms
 * @subpackage fields-basic
 */
class DropdownField_WithAdd extends DropdownField {
		
	protected $addText, $useExistingText, $addLink, $useExistingLink;
	public $editLink;
	
	function __construct($name, $title = "", $source = array(), $addLink=null, $useExistingLink=null, $addText="Add", $useExistingText="Use Existing", $value = "", $form = null){
		parent::__construct($name, $title, $source, $value, $form);
		$this->addText = $addText;
		$this->useExistingText = $useExistingText;	
		$this->addLink = $addLink;
		$this->useExistingLink = $useExistingLink;
	}
	
	function emptyString($string){
		
	}
	
	/**
	 * Returns a <select> tag containing all the appropriate <option> tags and with add/useExisting link
	 */
	function Field() {
		
		//Add these js file so that the DropdownField_WithAdd can work alone (in a webpage, rather than CMS).
		Requirements::javascript('jsparty/prototype.js');
		Requirements::javascript('jsparty/behaviour.js');
		Requirements::javascript('jsparty/prototype_improvements.js');
		Requirements::Javascript("sapphire/javascript/DropdownField_WithAdd.js");

		$dropdown = parent::Field();
		if($this->addLink) $addLink = <<<HTML
<a class="addlink link" id="{$this->name}_addLink" href="$this->addLink" style="display: inline; padding-left: 1em; text-decoration: underline;">$this->addText</a>
HTML;
		if($this->useExistingLink) $useExistingLink = <<<HTML
<a class="useExistinglink link" id="{$this->name}_useExistingLink" href="$this->useExistingLink" style="display: none; padding-left: 1em; text-decoration: underline;">$this->useExistingText</a>
HTML;

		if($this->editLink) $editLink = <<<HTML
<a class="editlink" id="{$this->name}_editLink" href="$this->editLink" style="display: inline; padding-left: 1em; text-decoration: underline;">edit</a>
HTML;

		return $dropdown . $addLink .  $useExistingLink . $editLink;
	}
	
	/**
	  * Add a class for this special label so that 
	  * it can have special styling
	  */
	function Title() {
		$title = parent::Title();
		if( $title ) {
			return <<<HTML
<span class="keylabel">$title</span>
HTML;
		}
		else
			return '';
	}
}
?>