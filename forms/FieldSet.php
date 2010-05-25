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
		
		if(isset($this->items) && count($this->items)) {
			foreach($this->items as $item) {
				if(isset($item) && is_a($item, 'FormField')) {
					$item->setContainerFieldSet($this);
				}
			}
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
	
	protected function flushFieldsCache() {
		$this->sequentialSet = null;
		$this->sequentialSaveableSet = null;
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
		$this->flushFieldsCache();

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
		$this->flushFieldsCache();

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
		$this->flushFieldsCache();

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
		$this->flushFieldsCache();

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
		if(!$fieldName) {
			user_error('FieldSet::removeByName() was called with a blank field name.', E_USER_WARNING);
		}
		$this->flushFieldsCache();
		
		foreach($this->items as $i => $child) {
			if(is_object($child)){
				if(($child->Name() == $fieldName || $child->Title() == $fieldName) && (!$dataFieldOnly || $child->hasData())) {
					array_splice( $this->items, $i, 1 );
					break;
				} else if($child->isComposite()) {
					$child->removeByName($fieldName, $dataFieldOnly);
				}
			}
		}
	}
	
	/**
	 * Replace a single field with another.  Ignores dataless fields such as Tabs and TabSets
	 *
	 * @param string $fieldName The name of the field to replace
	 * @param FormField $newField The field object to replace with
	 * @return boolean TRUE field was successfully replaced
	 * 					 FALSE field wasn't found, nothing changed
	 */
	public function replaceField($fieldName, $newField) {
		$this->flushFieldsCache();
		foreach($this->items as $i => $field) {
			if(is_object($field)) {
				if($field->Name() == $fieldName && $field->hasData()) {
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
	 * Rename the title of a particular field name in this set.
	 *
	 * @param string $fieldName Name of field to rename title of
	 * @param string $newFieldTitle New title of field
	 * @return boolean
	 */
	function renameField($fieldName, $newFieldTitle) {
		$field = $this->dataFieldByName($fieldName);
		if(!$field) return false;
		
		$field->setTitle($newFieldTitle);
		
		return $field->Title() == $newFieldTitle;
	}
	
	/**
	 * @return boolean
	 */
	public function hasTabSet() {
		foreach($this->items as $i => $field) {
			if(is_object($field) && $field instanceof TabSet) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Returns the specified tab object, creating it if necessary.
	 * 
	 * @todo Support recursive creation of TabSets
	 * 
	 * @param string $tabName The tab to return, in the form "Tab.Subtab.Subsubtab".
	 *   Caution: Does not recursively create TabSet instances, you need to make sure everything
	 *   up until the last tab in the chain exists.
	 * @param string $title Natural language title of the tab. If {@link $tabName} is passed in dot notation,
	 *   the title parameter will only apply to the innermost referenced tab.
	 *   The title is only changed if the tab doesn't exist already.
	 * @return Tab The found or newly created Tab instance
	 */
	public function findOrMakeTab($tabName, $title = null) {
		$parts = explode('.',$tabName);
		
		// We could have made this recursive, but I've chosen to keep all the logic code within FieldSet rather than add it to TabSet and Tab too.
		$currentPointer = $this;
		foreach($parts as $k => $part) {
			$parentPointer = $currentPointer;
			$currentPointer = $currentPointer->fieldByName($part);
			// Create any missing tabs
			if(!$currentPointer) {
				if(is_a($parentPointer, 'TabSet')) {
					// use $title on the innermost tab only
					if($title && $k == count($parts)-1) {
						$currentPointer = new Tab($part, $title);
					} else {
						$currentPointer = new Tab($part);
					}
					$parentPointer->push($currentPointer);
				} else {
					$withName = ($parentPointer->hasMethod('Name')) ? " named '{$parentPointer->Name()}'" : null;
					user_error("FieldSet::addFieldToTab() Tried to add a tab to object '{$parentPointer->class}'{$withName} - '$part' didn't exist.", E_USER_ERROR);
				}
			}
		}
		
		return $currentPointer;
	}

	/**
	 * Returns a named field.
	 * You can use dot syntax to get fields from child composite fields
	 * 
	 * @todo Implement similiarly to dataFieldByName() to support nested sets - or merge with dataFields()
	 */
	public function fieldByName($name) {
		if(strpos($name,'.') !== false)	list($name, $remainder) = explode('.',$name,2);
		else $remainder = null;
		
		foreach($this->items as $child) {
			if(trim($name) == trim($child->Name()) || $name == $child->id) {
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
	 * Returns a named field in a sequential set.
	 * Use this if you're using nested FormFields.
	 * 
	 * @param string $name The name of the field to return
	 * @return FormField instance
	 */
	public function dataFieldByName($name) {
		if($dataFields = $this->dataFields()) {
			foreach($dataFields as $child) {
				if(trim($name) == trim($child->Name()) || $name == $child->id) return $child;
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
		$this->onBeforeInsert($item);
		$item->setContainerFieldSet($this);
		
		$i = 0;
		foreach($this->items as $child) {
			if($name == $child->Name() || $name == $child->id) {
				array_splice($this->items, $i, 0, array($item));
				return $item;
			} elseif($child->isComposite()) {
				$ret = $child->insertBefore($item, $name);
				if($ret) return $ret;
			}
			$i++;
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
		$this->onBeforeInsert($item);
		$item->setContainerFieldSet($this);
		
		$i = 0;
		foreach($this->items as $child) {
			if($name == $child->Name() || $name == $child->id) {
				array_splice($this->items, $i+1, 0, array($item));
				return $item;
			} elseif($child->isComposite()) {
				$ret = $child->insertAfter($item, $name);
				if($ret) return $ret;
			}
			$i++;
		}
		
		return false;
	}
	
	/**
	 * Push a single field into this FieldSet instance.
	 *
	 * @param FormField $item The FormField to add
	 * @param string $key An option array key (field name)
	 */
	public function push($item, $key = null) {
		$this->onBeforeInsert($item);
		$item->setContainerFieldSet($this);
		return parent::push($item, $key = null);
	}

	/**
	 * Handler method called before the FieldSet is going to be manipulated.
	 */
	protected function onBeforeInsert($item) {
		$this->flushFieldsCache();
		if($item->Name()) $this->rootFieldSet()->removeByName($item->Name(), true);
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
		$hiddenFields = new HiddenFieldSet();
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
		$this->flushFieldsCache();
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
	 * 
	 * @param string|FormField
	 */
	function makeFieldReadonly($field) {
		$fieldName = ($field instanceof FormField) ? $field->Name() : $field;
		$srcField = $this->dataFieldByName($fieldName);
		$this->replaceField($fieldName, $srcField->performReadonlyTransformation());
	}
	
	/**
	 * Change the order of fields in this FieldSet by specifying an ordered list of field names.
	 * This works well in conjunction with SilverStripe's scaffolding functions: take the scaffold, and
	 * shuffle the fields around to the order that you want.
	 * 
	 * Please note that any tabs or other dataless fields will be clobbered by this operation.
	 *
	 * @param array $fieldNames Field names can be given as an array, or just as a list of arguments.
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
		
		$this->flushFieldsCache();
	}
	
	/**
	 * Find the numerical position of a field within
	 * the children collection. Doesn't work recursively.
	 * 
	 * @param string|FormField
	 * @return Position in children collection (first position starts with 0). Returns FALSE if the field can't be found.
	 */
	function fieldPosition($field) {
		if(is_object($field)) $field = $field->Name();
		
		$i = 0;
		foreach($this->dataFields() as $child) {
			if($child->Name() == $field) return $i;
			$i++;
		}
		
		return false;
	}
	
}

/**
 * A fieldset designed to store a list of hidden fields.  When inserted into a template, only the
 * input tags will be included
 * 
 * @package forms
 * @subpackage fields-structural
 */
class HiddenFieldSet extends FieldSet {
	function forTemplate() {
		$output = "";
		foreach($this as $field) {
			$output .= $field->Field();
		}
		return $output;
	}
}

?>
