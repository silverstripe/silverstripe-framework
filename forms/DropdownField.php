<?php
/**
 * Dropdown field, created from a <select> tag.
 * @package forms
 * @subpackage fields-basic
 */
class DropdownField extends FormField {
	
	/**
	 * @var boolean $source Associative or numeric array of all dropdown items,
	 * with array key as the submitted field value, and the array value as a
	 * natural language description shown in the interface element.
	 */
	protected $source;
	
	/**
	 * @var boolean $isSelected Determines if the field was selected
	 * at the time it was rendered, so if {@link $value} matches on of the array
	 * values specified in {@link $source}
	 */
	protected $isSelected;
	
	/**
	 * @var boolean $disabled
	 */
	protected $disabled;
	
	/**
	 * @var boolean $hasEmptyDefault Show the first <option> element as
	 * empty (not having a value), with an optional label defined through
	 * {@link $emptyString}. By default, the <select> element will be
	 * rendered with the first option from {@link $source} selected.
	 */
	protected $hasEmptyDefault = false;
	
	/**
	 * @var string $emptyString The title shown for an empty default selection,
	 * e.g. "Select...".
	 */
	protected $emptyString = '';
	
	/**
	 * Creates a new dropdown field.
	 * @param $name The field name
	 * @param $title The field title
	 * @param $source An map of the dropdown items
	 * @param $value The current value
	 * @param $form The parent form
	 * @param $emptyString mixed Add an empty selection on to of the {@link $source}-Array 
	 * 	(can also be boolean, which results in an empty string)
	 *  Argument is deprecated in 2.3, please use {@link setHasEmptyDefault()} and {@link setEmptyString()} instead.
	 */
	function __construct($name, $title = null, $source = array(), $value = "", $form = null, $emptyString = null) {
		$this->source = $source;
		
		if($emptyString) $this->setHasEmptyDefault(true);
		if(is_string($emptyString)) $this->setEmptyString($emptyString);
	
		parent::__construct($name, ($title===null) ? $name : $title, $value, $form);
	}
	
	/**
	 * Returns a <select> tag containing all the appropriate <option> tags.
	 * Makes use of {@link FormField->createTag()} to generate the <select>
	 * tag and option elements inside is as the content of the <select>.
	 * 
	 * @return string HTML tag for this dropdown field
	 */
	function Field() {
		$options = '';

		$source = $this->getSource();
		foreach($source as $value => $title) {
			$selected = ($value == $this->value) ? 'selected' : null;
			if($selected && $this->value != 0) {
				$this->isSelected = true;
			}
			
			$options .= $this->createTag(
				'option',
				array(
					'selected' => $selected,
					'value' => $value
				),
				$title
			);
		}
	
		$attributes = array(
			'class' => trim($this->extraClass()) ? $this->extraClass() : null,
			'id' => $this->id(),
			'name' => $this->name,
			'disabled' => $this->disabled ? 'disabled' : null,
			'tabindex' => $this->getTabIndex()
		);
		
		return $this->createTag('select', $attributes, $options);
	}
	
	/**
	 * @return boolean
	 */
	function isSelected(){
		return $this->isSelected;
	}
  
	/**
	 * Gets the source array including any empty default values.
	 * 
	 * @return array
	 */
	function getSource() {
		if($this->getHasEmptyDefault()) {
			return array(""=>$this->emptyString) + (array)$this->source;
		} else {
			return (array)$this->source;
		}
	}
  
	/**
	 * @param array $source
	 */
	function setSource($source) {
		$this->source = $source;
	}
	
	/**
	 * @param boolean $bool
	 */
	function setHasEmptyDefault($bool) {
		$this->hasEmptyDefault = $bool;
	}
	
	/**
	 * @return boolean
	 */
	function getHasEmptyDefault() {
		return $this->hasEmptyDefault;
	}
	
	/**
	 * Set the default selection label, e.g. "select...".
	 * Defaults to an empty string. Automatically sets
	 * {@link $hasEmptyDefault} to true.
	 * 
	 * @param string $str
	 */
	function setEmptyString($str) {
		$this->setHasEmptyDefault(true);
		$this->emptyString = $str;
	}
	
	/**
	 * @return string
	 */
	function getEmptyString() {
		return $this->emptyString;
	}

	function performReadonlyTransformation() {
		$field = new LookupField($this->name, $this->title, $this->source);
		$field->setValue($this->value);
		$field->setForm($this->form);
		$field->setReadonly(true);
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
		Requirements::javascript(THIRDPARTY_DIR . '/prototype.js');
		Requirements::javascript(THIRDPARTY_DIR . '/behaviour.js');
		Requirements::javascript(THIRDPARTY_DIR . '/prototype_improvements.js');
		Requirements::Javascript(SAPPHIRE_DIR . "/javascript/DropdownField_WithAdd.js");

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