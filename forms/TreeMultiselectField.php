<?php
/**
 * This formfield represents many-many joins using a tree selector shown in a dropdown styled element
 * which can be added to any form usually in the CMS.
 * 
 * This form class allows you to represent Many-Many Joins in a handy single field. The field has javascript which generates a AJAX tree of the site structure allowing you to save selected options to a component set on a given {@link DataObject}.
 * 
 * <b>Saving</b>
 * 
 * This field saves a {@link ComponentSet} object which is present on the {@link DataObject} passed by the form, returned by calling a function with the same name as the field. The Join is updated by running setByIDList on the {@link ComponentSet}
 * 
 * <b>Customizing Save Behaviour</b>
 * 
 * Before the data is saved, you can modify the ID list sent to the {@link ComponentSet} by specifying a function on the {@link DataObject} called "onChange[fieldname](&items)". This will be passed by reference the IDlist (an array of ID's) from the Treefield to be saved to the component set. 
 * Returning false on this method will prevent treemultiselect from saving to the {@link ComponentSet} of the given {@link DataObject}
 * 
 * <code>
 * // Called when we try and set the Parents() component set
 * // by Tree Multiselect Field in the administration.
 * function onChangeParents(&$items) {
 *  // This ensures this DataObject can never be a parent of itself
 * 	if($items){
 * 		foreach($items as $k => $id){
 * 			if($id == $this->ID){
 * 				unset($items[$k]);
 * 			}
 * 		}
 * 	}	
 * 	return true;
 * }
 * </code> 
 * 
 * @see TreeDropdownField for the sample implementation, but only allowing single selects
 * 
 * @package forms
 * @subpackage fields-relational
 */
class TreeMultiselectField extends TreeDropdownField {
	function __construct($name, $title, $sourceObject = "Group", $keyField = "ID", $labelField = "Title") {
		parent::__construct($name, $title, $sourceObject, $keyField, $labelField);
		$this->value = 'unchanged';
	}

	/**
	 * Return this field's linked items
	 */
	function getItems() {
		// If the value has been set, use that
		if($this->value != 'unchanged' && is_array($this->sourceObject)) {
			$items = array();
			$values = is_array($this->value) ? $this->value : preg_split('/ *, */', trim($this->value));
			foreach($values as $value) {
				$item = new stdClass;
				$item->ID = $value;
				$item->Title = $this->sourceObject[$value];
				$items[] = $item;
			}
			return $items;
			
		// Otherwise, look data up from the linked relation
		} if($this->value != 'unchanged' && is_string($this->value)) {
			$items = new DataObjectSet();
			$ids = explode(',', $this->value);
			foreach($ids as $id) {
				if(!is_numeric($id)) continue;
				$item = DataObject::get_by_id($this->sourceObject, $id);
				if($item) $items->push($item);
			}
			return $items;
		} else if($this->form) {
			$fieldName = $this->name;
			$record = $this->form->getRecord();
			if(is_object($record) && $record->hasMethod($fieldName)) 
				return $record->$fieldName();
		}	
	}
	/**
	 * We overwrite the field attribute to add our hidden fields, as this 
	 * formfield can contain multiple values.
	 */
	function Field() {
		$value = '';
		$itemList = '';
		
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');

		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/tree/tree.js');
		// needed for errorMessage()
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/LeftAndMain.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/TreeSelectorField.js');

		Requirements::css(SAPPHIRE_DIR . '/javascript/tree/tree.css');
		Requirements::css(SAPPHIRE_DIR . '/css/TreeDropdownField.css');


		$items = $this->getItems();

		if($items && count($items)) {
			foreach($items as $id => $item) {
				$titleArray[] = $item->Title;
				$idArray[] = $item->ID;
			}
				
			if(isset($titleArray)) {
				$itemList = implode(", ", $titleArray);
				$value = implode(",", $idArray);
			}
		} 

		$id = $this->id();
		
		return <<<HTML
			<div class="TreeDropdownField multiple" href="{$this->Link()}"><input id="$id" type="hidden" name="$this->name" value="$value" /><span class="items">$itemList</span><a href="#" title="open" class="editLink">&nbsp;</a></div>		
HTML;
	}

	/**
	 * Save the results into the form
	 * Calls function $record->onChange($items) before saving to the assummed 
	 * Component set.
	 */
	function saveInto(DataObject $record) {
		// Detect whether this field has actually been updated
		if($this->value !== 'unchanged') {
			$items = array();
			
			$fieldName = $this->name;
			$saveDest = $record->$fieldName();
			if(!$saveDest) user_error("TreeMultiselectField::saveInto() Field '$fieldName' not found on $record->class.$record->ID", E_USER_ERROR);
			
			if($this->value) {
				$items = preg_split("/ *, */", trim($this->value));
			}
					
			// Allows you to modify the items on your object before save
			$funcName = "onChange$fieldName";
			if($record->hasMethod($funcName)){
				$result = $record->$funcName($items);
				if(!$result){
					return;
				}
			}

			$saveDest->setByIDList($items);
		}
	}
	
	/**
	 * Changes this field to the readonly field.
	 */
	function performReadonlyTransformation() {
		$field = new TreeMultiselectField_Readonly($this->name, $this->title, $this->sourceObject, $this->keyField, $this->labelField);
		$field->addExtraClass($this->extraClass());
		$field->setForm($this->form);
		$field->setValue($this->value);
		return $field;
	}
}

/**
 * @package forms
 * @subpackage fields-relational
 */
class TreeMultiselectField_Readonly extends TreeMultiselectField {
	
	protected $readonly = true;
	
	function Field() {
		$titleArray = $itemIDs = array();
		$titleList = $itemIDsList = "";
		if($items = $this->getItems()) {
			foreach($items as $item) $titleArray[] = $item->Title;
			foreach($items as $item) $itemIDs[] = $item->ID;
			if($titleArray) $titleList = implode(", ", $titleArray);
			if($itemIDs) $itemIDsList = implode(",", $itemIDs);
		}
		
		$field = new ReadonlyField($this->name.'_ReadonlyValue', $this->title);
		$field->setValue($titleList);
		$field->setForm($this->form);
		
		$valueField = new HiddenField($this->name);
		$valueField->setValue($itemIDsList);
		$valueField->setForm($this->form);
		
		return $field->Field().$valueField->Field();
	}
}	