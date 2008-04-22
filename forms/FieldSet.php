<?php

/**
 * @package forms
 * @subpackage fields-structural
 */

/**
 * DataObjectSet designed for form fields.
 * It extends the DataObjectSet with the ability to get a sequential set of fields.
 * @package forms
 * @subpackage fields-structural
 */
class FieldSet extends DataObjectSet {
	protected $sequentialSet;
	
	/**
	 * Return a sequential set of all fields that have data.  This excludes wrapper composite fields
	 * as well as heading / help text fields.
	 */
	public function dataFields() {
		if(!$this->sequentialSet) $this->collateDataFields($this->sequentialSet);
		return $this->sequentialSet;
	}

	protected function collateDataFields(&$list) {
		foreach($this as $field) {

			if($field->isComposite()) $field->collateDataFields($list);
			if($field->hasData()) {
				$name = $field->Name();
				if(isset($list[$name])) {
					if($this->form) $errSuffix = " in your '{$this->form->class}' form called '" . $this->form->Name() . "'";
					else $errSuffix = '';
					user_error("collateDataFields() I noticed that a field called '$name' appears twice$errSuffix.", E_USER_ERROR);
				}
				$list[$name] = $field;
			}
		}
	}
	
	/**
	 * Add an extra field to a tab within this fieldset.
	 * This is most commonly used when overloading getCMSFields()
	 * @param tabName The name of the tab or tabset.  Subtabs can be referred to as TabSet.Tab or TabSet.Tab.Subtab.
	 * This function will create any missing tabs.
	 * @param field The {@link FormField} object to add to the end of that tab.
	 * @param insertBefore The name of the field to insert before.  Optional.
	 */
	public function addFieldToTab($tabName, $field, $insertBefore = null) {
		// This is a cache that must be flushed
		$this->sequentialSet = null;

		// Find the tab
		$tab = $this->findOrMakeTab($tabName);
		
		// Add the field to the end of this set
		if($insertBefore) $tab->insertBefore($field, $insertBefore);
		else $tab->push($field);
	}

	/**
	 * Remove the given field from the given tab in the field.
	 */
	public function removeFieldFromTab($tabName, $fieldName) {
		// This is a cache that must be flushed
		$this->sequentialSet = null;

		// Find the tab
		$tab = $this->findOrMakeTab($tabName);
		$tab->removeByName($fieldName);
	}
	
	/**
	 * Remove a field from this fieldset by name.
	 * It musn't be buried in a composite field.--- changed
	 * It could be buried in a composite field now. --- 5/09/2006
	 */
	public function removeByName($fieldName) {
		foreach($this->items as $i => $child) {
			if(is_object($child) && ($child->Name() == $fieldName || $child->Title() == $fieldName)) {
				// unset($this->items[$i]);
				array_splice( $this->items, $i, 1 );
				break;
			}	else if($child->isComposite()) $child->removeByName($fieldName);
			
		}
	}
	
	/**
	 * Add a number of extra fields to a tab within this fieldset.
	 * This is most commonly used when overloading getCMSFields()
	 * @param tabName The name of the tab or tabset.  Subtabs can be referred to as TabSet.Tab or TabSet.Tab.Subtab.
	 * This function will create any missing tabs.
	 * @param fields An array of {@link FormField} objects.
	 */
	public function addFieldsToTab($tabName, $fields) {
		$this->sequentialSet = null;

		// Find the tab
		$tab = $this->findOrMakeTab($tabName);
		
		// Add the fields to the end of this set
		foreach($fields as $field) $tab->push($field);
	}	
	
	public function replaceField($fieldName, $newField) {
		if($this->sequentialSet) $this->sequentialSet = null;
		foreach($this->items as $i => $field) {
			if(is_object($field)) {
				if($field->Name() == $fieldName) {
					$this->items[$i] = $newField;
					return true;
				
				} else if($field->isComposite()) {
					if($field->replaceField($fieldName, $newField)) return true;
				}
			}
		}
		return false;
	}
	
	
	/**
	 * Returns the specified tab object, creating it if necessary.
	 * @param tabName The tab to return, in the form "Tab.Subtab.Subsubtab"
	 */
	protected function findOrMakeTab($tabName) {
		$parts = explode('.',$tabName);

		// We could have made this recursive, but I've chosen to keep all the logic code within FieldSet rather than add it to TabSet and Tab too.
		$currentPointer = $this;
		foreach($parts as $part) {
			$parentPointer = $currentPointer;
			$currentPointer = $currentPointer->fieldByName($part);
			// Create any missing tabs
			if(!$currentPointer) {
				if(is_a($parentPointer,'TabSet')) {
					$currentPointer = new Tab($part);
					$parentPointer->push($currentPointer);
				} else {
					user_error("FieldSet::addFieldToTab() Tried to add a tab to a " . $parentPointer->class . " object - '$part' didn't exist.", E_USER_ERROR);
				}
			}
		}
		
		return $currentPointer;
  
	}

	/**
	 * Returns the named field
	 */
	public function fieldByName($name) {
		foreach($this->items as $child) {
			if($name == $child->Name() || $name == $child->id) return $child;
		}
	}

	/**
	 * Returns the named field in a sequential set.
	 * use this if your using nested formfields.
	 */
	public function dataFieldByName($name) {
		if($dataFields = $this->dataFields()){
			foreach($dataFields as $child) {
				if($name == $child->Name() || $name == $child->id) return $child;
			}
		}                                 
	}
	
	public function insertBefore($item, $name) {
		if($this->sequentialSet) $this->sequentialSet = null;
		
		$i = 0;
		
		foreach($this->items as $child) {
			if($name == $child->Name() || $name == $child->id) {
				array_splice($this->items, $i, 0, array($item));
			
				return;
			}
			
			$i++;
		}
		$this->items[] = $item;
	}
	

	/**
	 * Inserts an item before the item with name $name
	 * It can be buried in a composite field
	 * If no item with name $name is found, $item is inserted at the end of the FieldSet
	 *
	 * @param FormField $item The item to be inserted
	 * @param string $name The name of the item that $item should be before
	 * @param int $level For internal use only, should not be passed
	 */
	public function insertBeforeRecursive($item, $name, $level = 0) {
		if($this->sequentialSet) $this->sequentialSet = null;
		$i = 0;
		foreach($this->items as $child) {
			if($name == $child->Name() || $name == $child->id) {
				array_splice($this->items, $i, 0, array($item));
			
				return $level;
			} else if($child->isComposite()) {
				if($level = $child->insertBeforeRecursive($item,$name,$level+1)) return $level;
			}
			
			$i++;
		}
		if ($level === 0) {
		$this->items[] = $item;
			return 0;
		}
		return false;
	}
	
	public function insertAfter($item, $name) {
		if($this->sequentialSet) $this->sequentialSet = null;
		
		$i = 0;
		
		foreach($this->items as $child) {
			if($name == $child->Name() || $name == $child->id) {
				array_splice($this->items, $i + 1, 0, array($item));
			
				return;
			}
			
			$i++;
		}
		$this->items[] = $item;
	}
	
	public function push($item, $key = null) {
		if($this->sequentialSet) $this->sequentialSet = null;
		return parent::push($item, $key = null);
	}
	
	public function setForm($form) {
		foreach($this as $field) $field->setForm($form);
	}
	
	/**
	 * Load the given data into this form.
	 * @param data An map of data to load into the FieldSet.
	 */
	public function setValues($data) {
		foreach($this->dataFields() as $field) {
			$fieldName = $field->Name();
			if(isset($data[$fieldName])) $field->setValue($data[$fieldName]);
		}
	}

	/**
	 * Convert this form into a readonly form
	 */
	function transform($trans) {
		$this->sequentialSet = null;
		$newFields = new FieldSet();
		foreach($this as $field) {
			$newFields->push($field->transform($trans));
		}
		return $newFields;
	}
	
	function makeReadonly() {
		return $this->transform(new ReadonlyTransformation());
	}
	
	
}

?>
