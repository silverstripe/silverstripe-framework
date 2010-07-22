<?php
/**
 * Dropdown field, created from a <select> tag.
 * 
 * <b>Setting a $has_one relation</b>
 * 
 * Using here an example of an art gallery, with Exhibition pages, 
 * each of which has a Gallery they belong to.  The Gallery class is also user-defined.
 * <code>
 * 	static $has_one = array(
 * 		'Gallery' => 'Gallery',
 * 	);
 * 
 * 	public function getCMSFields() {
 * 		$fields = parent::getCMSFields();
 * 		$galleries = DataObject::get('Gallery');
 * 		if ($galleries) {
 * 			$galleries = $galleries->toDropdownMap('ID', 'Title', '(Select one)', true);
 * 		}
 * 		$fields->addFieldToTab('Root.Content.Main', new DropdownField('GalleryID', 'Gallery', $galleries), 'Content');
 * </code>
 * 
 * As you see, you need to put "GalleryID", rather than "Gallery" here.
 * 
 * <b>Populate with Array</b>
 * 
 * Example model defintion:
 * <code>
 * class MyObject extends DataObject {
 *   static $db = array(
 *     'Country' => "Varchar(100)"
 *   );
 * }			
 * </code>
 * 
 * Exampe instantiation:
 * <code>
 * new DropdownField(
 *   'Country',
 *   'Country',
 *   array(
 *     'NZ' => 'New Zealand',
 *     'US' => 'United States'
 *     'GEM'=> 'Germany'
 *   )
 * );
 * </code>
 * 
 * <b>Populate with Enum-Values</b>
 * 
 * You can automatically create a map of possible values from an {@link Enum} database column.
 * 
 * Example model definition:
 * <code>
 * class MyObject extends DataObject {
 *   static $db = array(
 *     'Country' => "Enum('New Zealand,United States,Germany','New Zealand')"
 *   );
 * }			
 * </code>
 * 
 * Field construction:
 * <code>
 * new DropdownField(
 *   'Country',
 *   'Country',
 *   singleton('MyObject')->dbObject('Country')->enumValues()
 * );
 * </code>
 * 
 * @see CheckboxSetField for multiple selections through checkboxes instead.
 * @see ListboxField for a single <select> box (with single or multiple selections).
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 * 
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
		if($source) {
			// For SQLMap sources, the empty string needs to be added specially
			if(is_object($source) && $this->emptyString) {
				$options .= $this->createTag('option', array('value' => ''), $this->emptyString);
			}
			
			foreach($source as $value => $title) {
				
				// Blank value of field and source (e.g. "" => "(Any)")
				if($value === '' && ($this->value === '' || $this->value === null)) {
					$selected = 'selected';
				} else {
					// Normal value from the source
					if($value) {
						$selected = ($value == $this->value) ? 'selected' : null;
					} else {
						// Do a type check comparison, we might have an array key of 0
						$selected = ($value === $this->value) ? 'selected' : null;
					}
					
					$this->isSelected = ($selected) ? true : false;
				}
				
				$options .= $this->createTag(
					'option',
					array(
						'selected' => $selected,
						'value' => $value
					),
					Convert::raw2xml($title)
				);
			}
		}
		
		$attributes = array(
			'class' => ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->name,
			'tabindex' => $this->getTabIndex()
		);
		
		if($this->disabled) $attributes['disabled'] = 'disabled';

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
		if(is_array($this->source) && $this->getHasEmptyDefault()) {
			return array(""=>$this->emptyString) + (array)$this->source;
		} else {
			return $this->source;
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
	
	/**
	 * Set form being disabled
	 */
	function setDisabled($disabled = true) {
		$this->disabled = $disabled;
	}
}