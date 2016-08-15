<?php
/**
 * Set of radio buttons designed to emulate a dropdown.
 *
 * This field allows you to ensure that a form element is submitted is not optional and is part of a fixed set of
 * data. This field uses the input type of radio. It's a direct subclass of {@link DropdownField},
 * so the constructor and arguments are in the same format.
 *
 * <b>Usage</b>
 *
 * <code>
 * new OptionsetField(
 *    $name = "Foobar",
 *    $title = "FooBar's optionset",
 *    $source = array(
 *       "1" => "Option 1",
 *       "2" => "Option 2",
 *       "3" => "Option 3",
 *       "4" => "Option 4",
 *       "5" => "Option 5"
 *    ),
 *    $value = "1"
 * );
 * </code>
 *
 * You can use the helper functions on data object set to create the source array. eg:
 *
 * <code>
 * //Database request for the object
 * $map = FooBar::get()->map();
 *  // returns an SS_Map object containing an array of ID => Title
 *
 * // Instantiate the OptionsetField
 * $FieldList = new FieldList(
 *   new OptionsetField(
 *    $name = "Foobar",
 *    $title = "FooBar's optionset",
 *    $source = $map,
 *    $value = $map[0]
 *   )
 * );
 *
 * // Pass the fields to the form constructor. etc
 * </code>
 *
 * @see CheckboxSetField for multiple selections through checkboxes instead.
 * @see DropdownField for a simple <select> field with a single element.
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 *
 * @package forms
 * @subpackage fields-basic
 */
class OptionsetField extends DropdownField {

	/**
	 * {@inheritdoc}
	 */
	public function Field($properties = array()) {
		$source = $this->getSource();
		$odd = 0;
		$options = array();

		if($source) {
			foreach($source as $value => $title) {
				// Ensure $title is safely cast
				if ( !($title instanceof DBField) ) {
					$title = DBField::create_field('Text', $title);
				}

				$itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $value);
				$odd = ($odd + 1) % 2;
				$extraClass = $odd ? 'odd' : 'even';
				$extraClass .= ' val' . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $value);

				$options[] = new ArrayData(array(
					'ID' => $itemID,
					'Class' => $extraClass,
					'Name' => $this->name,
					'Value' => $value,
					'Title' => $title,
					'isChecked' => $value == $this->value,
					'isDisabled' => $this->disabled || in_array($value, $this->disabledItems),
				));
			}
		}

		$properties = array_merge($properties, array(
			'Options' => new ArrayList($options)
		));

		return FormField::Field($properties);
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate($validator) {
		if (!$this->value) {
			return true;
		}

		return parent::validate($validator);
	}

	public function ExtraOptions() {
		return new ArrayList();
	}
	
	public function getAttributes() {
		$attributes = parent::getAttributes();
		unset($attributes['name']);
		unset($attributes['required']);
		unset($attributes['role']);
		
		return $attributes;
	}
}
