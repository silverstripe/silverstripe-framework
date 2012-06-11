<?php
/**
 * Displays a set of checkboxes as a logical group.
 *
 * ASSUMPTION -> IF you pass your source as an array, you pass values as an array too. Likewise objects are handled the same.
 * 
 * Example:
 * <code php>
 * new CheckboxSetField(
 *    $name = "topics",
 *    $title = "I am interested in the following topics",
 *    $source = array(
 *       "1" => "Technology",
 *       "2" => "Gardening",
 *       "3" => "Cooking",
 *       "4" => "Sports"
 *    ),
 *    $value = "1"
 * )
 * </code>
 * 
 * <b>Saving</b>
 * The checkbox set field will save its data in one of ways:
 *   * If the field name matches a many-many join on the object being edited, that many-many join will be updated to link to the objects selected on the checkboxes.  In this case, the keys of your value map should be the IDs of the database records.
 *   * If the field name matches a database field, a comma-separated list of values will be saved to that field.  The keys can be text or numbers.
 * 
 * @todo Document the different source data that can be used
 * with this form field - e.g ComponentSet, ArrayList,
 * array. Is it also appropriate to accept so many different
 * types of data when just using an array would be appropriate?
 * 
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxSetField extends OptionsetField {
	
	/**
	 * @var array
	 */
	protected $defaultItems = array();
	
	/**
	 * @todo Explain different source data that can be used with this field,
	 * e.g. SQLMap, ArrayList or an array.
	 */
	function Field($properties = array()) {
		Requirements::css(FRAMEWORK_DIR . '/css/CheckboxSetField.css');

		$source = $this->source;
		$values = $this->value;
		$items = array();

		// Get values from the join, if available
		if(is_object($this->form)) {
			$record = $this->form->getRecord();
			if(!$values && $record && $record->hasMethod($this->name)) {
				$funcName = $this->name;
				$join = $record->$funcName();
				if($join) {
					foreach($join as $joinItem) {
						$values[] = $joinItem->ID;
					}
				}
			}
		}
		
		// Source is not an array
		if(!is_array($source) && !is_a($source, 'SQLMap')) {
			if(is_array($values)) {
				$items = $values;
			} else {
				// Source and values are DataObject sets.
				if($values && is_a($values, 'SS_List')) {
					foreach($values as $object) {
						if(is_a($object, 'DataObject')) {
							$items[] = $object->ID;
						}
				   }
				} elseif($values && is_string($values)) {
					$items = explode(',', $values);
					$items = str_replace('{comma}', ',', $items);
				}
			}
		} else {
			// Sometimes we pass a singluar default value thats ! an array && !SS_List
			if($values instanceof SS_List || is_array($values)) {
				$items = $values;
			} else {
				$items = explode(',', $values);
				$items = str_replace('{comma}', ',', $items);
			}
		}
			
		if(is_array($source)) {
			unset($source['']);
		}
		
		$odd = 0;
		$options = array();
		
		if ($source == null) $source = array();

		if($source) {
			foreach($source as $value => $item) {
				if($item instanceof DataObject) {
					$value = $item->ID;
					$title = $item->Title;
				} else {
					$title = $item;
				}

				$itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $value);
				$odd = ($odd + 1) % 2;
				$extraClass = $odd ? 'odd' : 'even';
				$extraClass .= ' val' . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $value);

				$options[] = new ArrayData(array(
					'ID' => $itemID,
					'Class' => $extraClass,
					'Name' => "{$this->name}[{$value}]",
					'Value' => $value,
					'Title' => $title,
					'isChecked' => in_array($value, $items) || in_array($value, $this->defaultItems),
					'isDisabled' => $this->disabled || in_array($value, $this->disabledItems)
				));
			}
		}

		$properties = array_merge($properties, array('Options' => new ArrayList($options)));

		return $this->customise($properties)->renderWith($this->getTemplates());
	}
	
	/**
	 * Default selections, regardless of the {@link setValue()} settings.
	 * Note: Items marked as disabled through {@link setDisabledItems()} can still be
	 * selected by default through this method.
	 * 
	 * @param Array $items Collection of array keys, as defined in the $source array
	 */
	function setDefaultItems($items) {
		$this->defaultItems = $items;
		return $this;
	}
	
	/**
	 * @return Array
	 */
	function getDefaultItems() {
		return $this->defaultItems;
	}
	
	/**
	 * Load a value into this CheckboxSetField
	 */
	function setValue($value, $obj = null) {
		// If we're not passed a value directly, we can look for it in a relation method on the object passed as a second arg
		if(!$value && $obj && $obj instanceof DataObject && $obj->hasMethod($this->name)) {
			$funcName = $this->name;
			$value = $obj->$funcName()->getIDList();
		}

		parent::setValue($value, $obj);

		return $this;
	}
	
	/**
	 * Save the current value of this CheckboxSetField into a DataObject.
	 * If the field it is saving to is a has_many or many_many relationship,
	 * it is saved by setByIDList(), otherwise it creates a comma separated
	 * list for a standard DB text/varchar field.
	 *
	 * @param DataObject $record The record to save into
	 */
	function saveInto(DataObjectInterface $record) {
		$fieldname = $this->name;
		$relation = ($fieldname && $record && $record->hasMethod($fieldname)) ? $record->$fieldname() : null;
		if($fieldname && $record && $relation && $relation instanceof RelationList) {
			$idList = array();
			if($this->value) foreach($this->value as $id => $bool) {
				if($bool) {
					$idList[] = $id;
				}
			}
			$relation->setByIDList($idList);
		} elseif($fieldname && $record) {
			if($this->value) {
				$this->value = str_replace(',', '{comma}', $this->value);
				$record->$fieldname = implode(',', (array) $this->value);
			} else {
				$record->$fieldname = '';
			}
		}
	}
	
	/**
	 * Return the CheckboxSetField value as a string 
	 * selected item keys.
	 * 
	 * @return string
	 */
	function dataValue() {
		if($this->value && is_array($this->value)) {
			$filtered = array();
			foreach($this->value as $item) {
				if($item) {
					$filtered[] = str_replace(",", "{comma}", $item);
				}
			}
			
			return implode(',', $filtered);
		}
		
		return '';
	}
	
	function performDisabledTransformation() {
		$clone = clone $this;
		$clone->setDisabled(true);
		
		return $clone;
	}
	
	/**
	 * Transforms the source data for this CheckboxSetField
	 * into a comma separated list of values.
	 * 
	 * @return ReadonlyField
	 */
	function performReadonlyTransformation() {
		$values = '';
		$data = array();
		
		$items = $this->value;
		if($this->source) {
			foreach($this->source as $source) {
				if(is_object($source)) {
					$sourceTitles[$source->ID] = $source->Title;
				}
			}
		}
		
		if($items) {
			// Items is a DO Set
			if($items instanceof SS_List) {
				foreach($items as $item) {
					$data[] = $item->Title;
				}
				if($data) $values = implode(', ', $data);
				
			// Items is an array or single piece of string (including comma seperated string)
			} else {
				if(!is_array($items)) {
					$items = preg_split('/ *, */', trim($items));
				}
				
				foreach($items as $item) {
					if(is_array($item)) {
						$data[] = $item['Title'];
					} elseif(is_array($this->source) && !empty($this->source[$item])) {
						$data[] = $this->source[$item];
					} elseif(is_a($this->source, 'SS_List')) {
						$data[] = $sourceTitles[$item];
					} else {
						$data[] = $item;
					}
				}
				
				$values = implode(', ', $data);
			}
		}
		
		$title = ($this->title) ? $this->title : '';
		
		$field = new ReadonlyField($this->name, $title, $values);
		$field->setForm($this->form);
		
		return $field;
	}

	function Type() {
		return 'optionset checkboxset';
	}
	
	function ExtraOptions() {
		return FormField::ExtraOptions();
	}
	
}
