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
class OptionsetField extends SingleSelectField {

	/**
	 * Build a field option for template rendering
	 *
	 * @param mixed $value Value of the option
	 * @param string $title Title of the option
	 * @param boolean $odd True if this should be striped odd. Otherwise it should be striped even
	 * @return ArrayData Field option
	 */
	protected function getFieldOption($value, $title, $odd) {
		// Check selection
		$selected = $this->isSelectedValue($value, $this->Value());
		$itemID = $this->ID() . '_' . Convert::raw2htmlid($value);
		$extraClass = $odd ? 'odd' : 'even';
		$extraClass .= ' val' . Convert::raw2htmlid($value);

		return new ArrayData(array(
			'ID' => $itemID,
			'Class' => $extraClass,
			'Name' => $this->getItemName(),
			'Value' => $value,
			'Title' => $title,
			'isChecked' => $selected,
			'isDisabled' => $this->isDisabled() || in_array($value, $this->getDisabledItems()),
		));
		}

	protected function getItemName() {
		return $this->getName();
	}

	public function Field($properties = array()) {
		$options = array();
		$odd = false;
		
		// Add all options striped
		foreach($this->getSourceEmpty() as $value => $title) {
			$odd = !$odd;
			$options[] = $this->getFieldOption($value, $title, $odd);
		}

		$properties = array_merge($properties, array(
			'Options' => new ArrayList($options)
		));

		return $this->customise($properties)->renderWith(
			$this->getTemplates()
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate($validator) {
		if (!$this->Value()) {
			return true;
		}

		return parent::validate($validator);
	}

	public function getAttributes() {
		$attributes = parent::getAttributes();
		unset($attributes['name']);
		unset($attributes['required']);
		unset($attributes['role']);

		return $attributes;
	}
}
