<?php
/**
 * Multi-line listbox field, created from a <select> tag.
 *
 * <b>Usage</b>
 *
 * <code>
 * new ListboxField(
 *    $name = "pickanumber",
 *    $title = "Pick a number",
 *    $source = array(
 *       "1" => "one",
 *       "2" => "two",
 *       "3" => "three"
 *    ),
 *    $value = 1
 * )
 * </code>
 *
 * @see DropdownField for a simple <select> field with a single element.
 * @see CheckboxSetField for multiple selections through checkboxes.
 * @see OptionsetField for single selections via radiobuttons.
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 *
 * @package forms
 * @subpackage fields-basic
 */
class ListboxField extends DropdownField {

	/**
	 * The size of the field in rows.
	 * @var int
	 */
	protected $size;

	/**
	 * Should the user be able to select multiple
	 * items on this dropdown field?
	 *
	 * @var boolean
	 */
	protected $multiple = false;

	/**
	 * @var Array
	 */
	protected $disabledItems = array();

	/**
	 * @var Array
	 */
	protected $defaultItems = array();

	/**
	 * Creates a new dropdown field.
	 *
	 * @param string $name The field name
	 * @param string $title The field title
	 * @param array $source An map of the dropdown items
	 * @param string|array $value You can pass an array of values or a single value like a drop down to be selected
	 * @param int $size Optional size of the select element
	 * @param form The parent form
	 */
	public function __construct($name, $title = '', $source = array(), $value = '', $size = null, $multiple = false) {
		if($size) $this->size = $size;
		if($multiple) $this->multiple = $multiple;

		parent::__construct($name, $title, $source, $value);
	}

	/**
	 * Returns a <select> tag containing all the appropriate <option> tags
	 */
	public function Field($properties = array()) {
		if($this->multiple) $this->name .= '[]';
		$options = array();

		// We have an array of values
		if(is_array($this->value)){
			// Loop through and figure out which values were selected.
			foreach($this->getSource() as $value => $title) {
				$options[] = new ArrayData(array(
					'Title' => $title,
					'Value' => $value,
					'Selected' => (in_array($value, $this->value) || in_array($value, $this->defaultItems)),
					'Disabled' => $this->disabled || in_array($value, $this->disabledItems),
				));
			}
		} else {
			// Listbox was based a singlular value, so treat it like a dropdown.
			foreach($this->getSource() as $value => $title) {
				$options[] = new ArrayData(array(
					'Title' => $title,
					'Value' => $value,
					'Selected' => ($value == $this->value || in_array($value, $this->defaultItems)),
					'Disabled' => $this->disabled || in_array($value, $this->disabledItems),
				));
			}
		}

		$properties = array_merge($properties, array(
			'Options' => new ArrayList($options)
		));

		return FormField::Field($properties);
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'multiple' => $this->multiple,
				'size' => $this->size
			)
		);
	}

	/**
	 * Sets the size of this dropdown in rows.
	 * @param int $size The height in rows (e.g. 3)
	 */
	public function setSize($size) {
		$this->size = $size;
		return $this;
	}

	/**
	 * Sets this field to have a muliple select attribute
	 * @param boolean $bool
	 */
	public function setMultiple($bool) {
		$this->multiple = $bool;
		return $this;
	}

	public function setSource($source) {
		if($source) {
			$hasCommas = array_filter(array_keys($source),
			function($key) {
			    return strpos($key, ",") !== FALSE;
			});
			if(!empty($hasCommas)) {
				throw new InvalidArgumentException('No commas allowed in $source keys');
			}
		}

		parent::setSource($source);

		return $this;
	}

	/**
	 * Return the CheckboxSetField value as a string
	 * selected item keys.
	 *
	 * @return string
	 */
	public function dataValue() {
		if($this->value && is_array($this->value) && $this->multiple) {
			$filtered = array();
			foreach($this->value as $item) {
				if($item) {
					$filtered[] = str_replace(",", "{comma}", $item);
				}
			}
			return implode(',', $filtered);
		} else {
			return parent::dataValue();
		}
	}

	/**
	 * Save the current value of this field into a DataObject.
	 * If the field it is saving to is a has_many or many_many relationship,
	 * it is saved by setByIDList(), otherwise it creates a comma separated
	 * list for a standard DB text/varchar field.
	 *
	 * @param DataObject $record The record to save into
	 */
	public function saveInto(DataObjectInterface $record) {
		if($this->multiple) {
			$fieldname = $this->name;
			$relation = ($fieldname && $record && $record->hasMethod($fieldname)) ? $record->$fieldname() : null;
			if($fieldname && $record && $relation &&
				($relation instanceof RelationList || $relation instanceof UnsavedRelationList)) {
				$idList = (is_array($this->value)) ? array_values($this->value) : array();
				$relation->setByIDList($idList);
			} elseif($fieldname && $record) {
				if($this->value) {
					$this->value = str_replace(',', '{comma}', $this->value);
					$record->$fieldname = implode(",", $this->value);
				} else {
					$record->$fieldname = null;
				}
			}
		} else {
			parent::saveInto($record);
		}
	}

	/**
	 * Load a value into this ListboxField
	 */
	public function setValue($val, $obj = null) {
		// If we're not passed a value directly,
		// we can look for it in a relation method on the object passed as a second arg
		if(!$val && $obj && $obj instanceof DataObject && $obj->hasMethod($this->name)) {
			$funcName = $this->name;
			$val = array_values($obj->$funcName()->getIDList());
		}

		if($val) {
			if(!$this->multiple && is_array($val)) {
				throw new InvalidArgumentException('Array values are not allowed (when multiple=false).');
			}

			if($this->multiple) {
				$parts = (is_array($val)) ? $val : preg_split("/ *, */", trim($val));
				if(ArrayLib::is_associative($parts)) {
					// This is due to the possibility of accidentally passing an array of values (as keys) and titles (as values) when only the keys were intended to be saved.
					throw new InvalidArgumentException('Associative arrays are not allowed as values (when multiple=true), only indexed arrays.');
				}

				// Doesn't check against unknown values in order to allow for less rigid data handling.
				// They're silently ignored and overwritten the next time the field is saved.
				parent::setValue($parts);
			} else {
				if(!in_array($val, array_keys($this->getSource()))) {
					throw new InvalidArgumentException(sprintf(
						'Invalid value "%s" for multiple=false',
						Convert::raw2xml($val)
					));
				}

				parent::setValue($val);
			}
		} else {
			parent::setValue($val);
		}

		return $this;
	}

	/**
	 * Mark certain elements as disabled,
	 * regardless of the {@link setDisabled()} settings.
	 *
	 * @param array $items Collection of array keys, as defined in the $source array
	 */
	public function setDisabledItems($items) {
		$this->disabledItems = $items;
		return $this;
	}

	/**
	 * @return Array
	 */
	public function getDisabledItems() {
		return $this->disabledItems;
	}

	/**
	 * Default selections, regardless of the {@link setValue()} settings.
	 * Note: Items marked as disabled through {@link setDisabledItems()} can still be
	 * selected by default through this method.
	 *
	 * @param Array $items Collection of array keys, as defined in the $source array
	 */
	public function setDefaultItems($items) {
		$this->defaultItems = $items;
		return $this;
	}

	/**
	 * @return Array
	 */
	public function getDefaultItems() {
		return $this->defaultItems;
	}

	/**
	 * Validate this field
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {
		$values = $this->value;
		if (!$values) {
			return true;
		}
		$source = $this->getSourceAsArray();
		if (is_array($values)) {
			if (!array_intersect_key($source,array_flip($values))) {
				$validator->validationError(
					$this->name,
					_t(
						"Please select a value within the list provided. {value} is not a valid option",
						array('value' => $this->value)
					),
					"validation"
				);
				return false;
			}
		} else {
			if (!array_key_exists($this->value, $source)) {
				$validator->validationError(
					$this->name,
					_t(
						'ListboxField.SOURCE_VALIDATION',
						"Please select a value within the list provided. %s is not a valid option",
						array('value' => $this->value)
					),
					"validation"
				);
				return false;
			}
		}
		return true;
	}

}
