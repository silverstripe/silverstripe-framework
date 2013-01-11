<?php
/**
 * Represents a number of fields which are selectable by a radio 
 * button that appears at the beginning of each item.  Using CSS, you can 
 * configure the field to only display its contents if the corresponding radio 
 * button is selected. Each item is defined through {@link SelectionGroup_Item}.
 *
 * @example <code>
 * $items = array(
 * 	new SelectionGroup_Item(
 * 		'one',
 * 		new LiteralField('one', 'one view'),
 * 		'one title'
 * 	),
 * 	new SelectionGroup_Item(
 * 		'two',
 * 		new LiteralField('two', 'two view'),
 * 		'two title'
 * 	),
 * );
 * $field = new SelectionGroup('MyGroup', $items);
 * </code>
 *
 * @package forms
 * @subpackage fields-structural
 */
class SelectionGroup extends CompositeField {
	
	/**
	 * Create a new selection group.
	 * 
	 * @param name The field name of the selection group.
	 * @param items The list of {@link SelectionGroup_Item}
	 */
	public function __construct($name, $items) {
		$this->name = $name;
		
		$selectionItems = array();
		foreach($items as $key => $item) {
			if($item instanceof SelectionGroup_Item) {
				$selectionItems[] = $item;
			} else {
				// Convert legacy format
				if(strpos($key,'//') !== false) {
					list($key,$title) = explode('//', $key,2);
				} else {
					$title = null;
				}	
				$selectionItems[] = new SelectionGroup_Item($key, $item, $title);
			}
		}

		parent::__construct($selectionItems);
		
		Requirements::css(FRAMEWORK_DIR . '/css/SelectionGroup.css');
	}

	public function FieldSet() {
		return $this->FieldList();
	}
	
	public function FieldList() {
		$items = parent::FieldList()->toArray();
		$count = 0;
		$newItems = array();
		
		foreach($items as $item) {
			if($this->value == $item->getValue()) {
				$firstSelected = " class=\"selected\"";
				$checked = true;
			} else {
				$firstSelected = "";
				$checked = false;
			}
			
			$itemID = $this->ID() . '_' . (++$count);
			$extra = array(
				"RadioButton" => FormField::create_tag(
					'input', 
					array(
						'class' => 'selector',
						'type' => 'radio',
						'id' => $itemID,
						'name' => $this->name,
						'value' => $item->getValue(),
						'checked' => $checked 
					)
				),
				"RadioLabel" => FormField::create_tag(
					'label', 
					array('for' => $itemID),
					$item->getTitle()
				),
				"Selected" => $firstSelected,
			);
			$newItems[] = $item->customise($extra);
		}
		
		return new ArrayList($newItems);
	}
	
	public function hasData() {
		return true;
	}
	
	public function FieldHolder($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR   . '/javascript/SelectionGroup.js');
		Requirements::css(FRAMEWORK_DIR . '/css/SelectionGroup.css');

		$obj = $properties ? $this->customise($properties) : $this;

		return $obj->renderWith($this->getTemplates());
	}
}

class SelectionGroup_Item extends CompositeField {

	/**
	 * @var String
	 */
	protected $value;

	/**
	 * @var String
	 */
	protected $title;

	/**
	 * @param String $value Form field identifier
	 * @param FormField $field Contents of the option
	 * @param String $title Title to show for the radio button option
	 */
	function __construct($value, $fields = null, $title = null) {
		$this->value = $value;
		$this->title = ($title) ? $title : $value;
		if($fields && !is_array($fields)) $fields = array($fields);

		parent::__construct($fields);
	}

	function getTitle() {
		return $this->title;
	}

	function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	function getValue() {
		return $this->value;
	}

	function setValue($Value) {
		$this->value = $Value;
		return $this;
	}

}