<?php
/**
 * DataObjectSet designed for form fields.
 * It extends the DataObjectSet with the ability to get a sequential set of fields.
 * @package forms
 * @subpackage fields-structural
 */
class FieldSet extends DataObjectSet {
	
	/**
	 * Cached flat representation of all fields in this set,
	 * including fields nested in {@link CompositeFields}.
	 *
	 * @uses self::collateDataFields()
	 * @var array
	 */
	protected $sequentialSet;
	
	/**
	 * @var array
	 */
	protected $sequentialSaveableSet;
	
	/**
	 * @todo Documentation
	 */
	protected $containerField;
	
	public function __construct($items = null) {
		// if the first parameter is not an array, or we have more than one parameter, collate all parameters to an array
		// otherwise use the passed array
		$itemsArr = (!is_array($items) || count(func_get_args()) > 1) ? func_get_args() : $items;
		parent::__construct($itemsArr);
		
		foreach($this->items as $item) {
			$item->setContainerFieldSet($this);
		}
		
	}
	
	/**
	 * Return a sequential set of all fields that have data.  This excludes wrapper composite fields
	 * as well as heading / help text fields.
	 */
	public function dataFields() {
		if(!$this->sequentialSet) $this->collateDataFields($this->sequentialSet);
		return $this->sequentialSet;
	}
	
	public function saveableFields() {
		if(!$this->sequentialSaveableSet) $this->collateDataFields($this->sequentialSaveableSet, true);
		return $this->sequentialSaveableSet;
	}
	
	protected function collateDataFields(&$list, $saveableOnly = false) {
		foreach($this as $field) {
			if($field->isComposite()) $field->collateDataFields($list, $saveableOnly);

			if($saveableOnly) {
				$isIncluded =  ($field->hasData() && !$field->isReadonly() && !$field->isDisabled());
			} else {
				$isIncluded =  ($field->hasData());
			}
			if($isIncluded) {
				$name = $field->Name();
				if(isset($list[$name])) {
					$errSuffix = "";
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
	 * 
	 * @param string $tabName The name of the tab or tabset.  Subtabs can be referred to as TabSet.Tab or TabSet.Tab.Subtab.
	 * This function will create any missing tabs.
	 * @param FormField $field The {@link FormField} object to add to the end of that tab.
	 * @param string $insertBefore The name of the field to insert before.  Optional.
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
	 * Add a number of extra fields to a tab within this fieldset.
	 * This is most commonly used when overloading getCMSFields()
	 * 
	 * @param string $tabName The name of the tab or tabset.  Subtabs can be referred to as TabSet.Tab or TabSet.Tab.Subtab.
	 * This function will create any missing tabs.
	 * @param array $fields An array of {@link FormField} objects.
	 */
	public function addFieldsToTab($tabName, $fields) {
		$this->sequentialSet = null;

		// Find the tab
		$tab = $this->findOrMakeTab($tabName);
		
		// Add the fields to the end of this set
		foreach($fields as $field) {
			// Check if a field by the same name exists in this tab
			if($tab->fieldByName($field->Name())) {
				// It exists, so we need to replace the old one
				$this->replaceField($field->Name(), $field);
			} else {
				$tab->push($field);
			}
		}
	}	

	/**
	 * Remove the given field from the given tab in the field.
	 * 
	 * @param string $tabName The name of the tab
	 * @param string $fieldName The name of the field
	 */
	public function removeFieldFromTab($tabName, $fieldName) {
		// This is a cache that must be flushed
		$this->sequentialSet = null;

		// Find the tab
		$tab = $this->findOrMakeTab($tabName);
		$tab->removeByName($fieldName);
	}
	
	/**
	 * Removes a number of fields from a Tab/TabSet within this FieldSet.
	 *
	 * @param string $tabName The name of the Tab or TabSet field
	 * @param array $fields A list of fields, e.g. array('Name', 'Email')
	 */
	public function removeFieldsFromTab($tabName, $fields) {
		// This is a cache that must be flushed
		$this->sequentialSet = null;

		// Find the tab
		$tab = $this->findOrMakeTab($tabName);
		
		// Add the fields to the end of this set
		foreach($fields as $field) $tab->removeByName($field);
	}
	
	/**
	 * Remove a field from this FieldSet by Name.
	 * The field could also be inside a CompositeField.
	 * 
	 * @param string $fieldName The name of the field or tab
	 * @param boolean $dataFieldOnly If this is true, then a field will only
	 * be removed if it's a data field.  Dataless fields, such as tabs, will
	 * be left as-is.
	 */
	public function removeByName($fieldName, $dataFieldOnly = false) {
		foreach($this->items as $i => $child) {
			if(is_object($child) && ($child->Name() == $fieldName || $child->Title() == $fieldName) && (!$dataFieldOnly || $child->hasData())) {
				//if($child->class == 'Tab' && !$dataFieldOnly) Debug::backtrace();
				array_splice( $this->items, $i, 1 );
				break;
			} else if($child->isComposite()) {
				$child->removeByName($fieldName, $dataFieldOnly);
			}
		}
	}
	
	/**
	 * Replace a single field with another.
	 *
	 * @param string $fieldName The name of the field to replace
	 * @param FormField $newField The field object to replace with
	 * @return boolean TRUE field was successfully replaced
	 * 					 FALSE field wasn't found, nothing changed
	 */
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
	 * 
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
				if(is_a($parentPointer, 'TabSet')) {
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
	 * Returns a named field.
	 * 
	 * @todo Implement similiarly to dataFieldByName() to support nested sets - or merge with dataFields()
	 */
	public function fieldByName($name) {
		foreach($this->items as $child) {
			if($name == $child->Name() || $name == $child->id) return $child;
		}
	}

	/**
	 * Returns a named field in a sequential set.
	 * Use this if you're using nested FormFields.
	 * 
	 * @param string $name The name of the field to return
	 * @return FormField instance
	 */
	public function dataFieldByName($name) {
		if($dataFields = $this->dataFields()) {
			foreach($dataFields as $child) {
				if($name == $child->Name() || $name == $child->id) return $child;
			}
		}                                 
	}

	/**
	 * Inserts a field before a particular field in a FieldSet.
	 *
	 * @param FormField $item The form field to insert
	 * @param string $name Name of the field to insert before
	 */
	public function insertBefore($item, $name) {
		$this->beforeInsert($item);
		$item->setContainerFieldSet($this);
		
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
		$this->beforeInsert($item);
		$item->setContainerFieldSet($this);
		
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
	
	/**
	 * Inserts a field after a particular field in a FieldSet.
	 *
	 * @param FormField $item The form field to insert
	 * @param string $name Name of the field to insert after
	 */
	public function insertAfter($item, $name) {
		$this->beforeInsert($item);
		$item->setContainerFieldSet($this);
		
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
	
	/**
	 * Push a single field into this FieldSet instance.
	 *
	 * @param FormField $item The FormField to add
	 * @param string $key An option array key (field name)
	 */
	public function push($item, $key = null) {
		$this->beforeInsert($item);
		$item->setContainerFieldSet($this);
		return parent::push($item, $key = null);
	}

	/**
	 * Handler method called before the FieldSet is going to be manipulated.
	 */
	function beforeInsert($item) {
		if($this->sequentialSet) $this->sequentialSet = null;
		$this->rootFieldSet()->removeByName($item->Name(), true);
	}
		
	
	/**
	 * Set the Form instance for this FieldSet.
	 *
	 * @param Form $form The form to set this FieldSet to
	 */
	public function setForm($form) {
		foreach($this as $field) $field->setForm($form);
	}
	
	/**
	 * Load the given data into this form.
	 * 
	 * @param data An map of data to load into the FieldSet
	 */
	public function setValues($data) {
		foreach($this->dataFields() as $field) {
			$fieldName = $field->Name();
			if(isset($data[$fieldName])) $field->setValue($data[$fieldName]);
		}
	}
	
	/**
	 * Return all <input type="hidden"> fields
	 * in a form - including fields nested in {@link CompositeFields}.
	 * Useful when doing custom field layouts.
	 * 
	 * @return FieldSet
	 */
	function HiddenFields() {
		$hiddenFields = new FieldSet();
		$dataFields = $this->dataFields();
		
		if($dataFields) foreach($dataFields as $field) {
			if($field instanceof HiddenField) $hiddenFields->push($field);
		}
		
		return $hiddenFields;
	}

	/**
	 * Transform this FieldSet with a given tranform method,
	 * e.g. $this->transform(new ReadonlyTransformation())
	 * 
	 * @return FieldSet
	 */
	function transform($trans) {
		$this->sequentialSet = null;
		$newFields = new FieldSet();
		foreach($this as $field) {
			$newFields->push($field->transform($trans));
		}
		return $newFields;
	}

	/**
	 * Returns the root field set that this belongs to
	 */
	function rootFieldSet() {
		if($this->containerField) return $this->containerField->rootFieldSet();
		else return $this;
	}
	
	function setContainerField($field) {
		$this->containerField = $field;
	}
	
	/**
	 * Transforms this FieldSet instance to readonly.
	 *
	 * @return FieldSet
	 */
	function makeReadonly() {
		return $this->transform(new ReadonlyTransformation());
	}

	/**
	 * Transform the named field into a readonly feld.
	 */
	function makeFieldReadonly($fieldName) {
		// Iterate on items, looking for the applicable field
		foreach($this->items as $i => $field) {
			// Once it's found, use FormField::transform to turn the field into a readonly version of itself.
			if($field->Name() == $fieldName) {
				$this->items[$i] = $field->transform(new ReadonlyTransformation());
				
				// Clear an internal cache
				$this->sequentialSet = null;
			
				// A true results indicates that the field was foudn
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Change the order of fields in this FieldSet by specifying an ordered list of field names.
	 * This works well in conjunction with SilverStripe's scaffolding functions: take the scaffold, and
	 * shuffle the fields around to the order that you want.
	 * 
	 * Please note that any tabs or other dataless fields will be clobbered by this operation.
	 *
	 * Field names can be given as an array, or just as a list of arguments.
	 */
	function changeFieldOrder($fieldNames) {
		// Field names can be given as an array, or just as a list of arguments.
		if(!is_array($fieldNames)) $fieldNames = func_get_args();
		
		// Build a map of fields indexed by their name.  This will make the 2nd step much easier.
		$fieldMap = array();
		foreach($this->dataFields() as $field) $fieldMap[$field->Name()] = $field;
		
		// Iterate through the ordered list	of names, building a new array to be put into $this->items.
		// While we're doing this, empty out $fieldMap so that we can keep track of leftovers.
		// Unrecognised field names are okay; just ignore them
		$fields = array();
		foreach($fieldNames as $fieldName) {
			if(isset($fieldMap[$fieldName])) {
				$fields[] = $fieldMap[$fieldName];
				unset($fieldMap[$fieldName]);
			}
		}
		
		// Add the leftover fields to the end of the list.
		$fields = $fields + array_values($fieldMap);
		
		// Update our internal $this->items parameter.
		$this->items = $fields;
		
		// Re-set an internal cache
		$this->sequentialSet = null;
	}
	
}

?>