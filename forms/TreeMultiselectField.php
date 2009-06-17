<?php
/**
 * This formfield represents many-many joins using a tree selector shown in a dropdown styled element
 * which can be added to any form usually in the CMS. 
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
		if($this->form) {
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
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/TreeSelectorField.js");
		
		$items = $this->getItems();
		if($items) {
			foreach($items as $item) {
				$titleArray[] =$item->Title;
				$idArray[] = $item->ID;
			}
			if(isset($titleArray)) {
				$itemList = implode(", ", $titleArray);
				$value = implode(",", $idArray);
			}
		}

		$id = $this->id();
		
		return <<<HTML
			<div class="TreeDropdownField multiple"><input id="$id" type="hidden" name="$this->name" value="$value" /><span class="items">$itemList</span><a href="#" title="open" class="editLink">&nbsp;</a></div>		
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
				$items = split(" *, *", trim($this->value));
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
		return new TreeMultiselectField_Readonly($this->name, $this->title, $this->sourceObject, $this->keyField, $this->labelField);
	}
}

class TreeMultiselectField_Readonly extends TreeMultiselectField {
	
	protected $readonly = true;
	
	function Field() {
		$titleArray = array();
		$titleList = array();
		if($items = $this->getItems()) {
			foreach($items as $item) $titleArray[] = $item->Title;
			if($titleArray) $titleList = implode(", ", $titleArray);
		}

		$field = new ReadonlyField($this->name, $this->title);
		$field->setValue($titleList);
		$field->setForm($this->form);
		return $field->Field();
	}
}	