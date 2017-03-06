<?php
/**
 * Grouped dropdown, using <optgroup> tags.
 *
 * $source parameter (from DropdownField) must be a two dimensional array.
 * The first level of the array is used for the <optgroup>, and the second
 * level are the <options> for each group.
 *
 * Returns a <select> tag containing all the appropriate <option> tags, with
 * <optgroup> tags around the <option> tags as required.
 *
 * <b>Usage</b>
 *
 * <code>
 * new GroupedDropdownField(
 *    $name = "dropdown",
 *    $title = "Simple Grouped Dropdown",
 *    $source = array(
 *       "numbers" => array(
 *       		"1" => "1",
 *       		"2" => "2",
 *       		"3" => "3",
 *       		"4" => "4"
 *    		),
 *       "letters" => array(
 *       		"1" => "A",
 *       		"2" => "B",
 *       		"3" => "C",
 *       		"4" => "D",
 *       		"5" => "E",
 *       		"6" => "F"
 *    		)
 *    )
 * )
 * </code>
 *
 * <b>Disabling individual items</b>
 *
 * <code>
 * $groupedDrDownField->setDisabledItems(
 *    array(
 *       "numbers" => array(
 *       		"1" => "1",
 *       		"3" => "3"
 *    		),
 *       "letters" => array(
 *       		"3" => "C"
 *    		)
 *    )
 * )
 * </code>
 *
 * @package forms
 * @subpackage fields-basic
 */
class GroupedDropdownField extends DropdownField {

	public function Field($properties = array()) {
		$options = array();
		foreach($this->getSource() as $valueOrGroupTitle => $titleOrGroup) {
			// Option group
			if(is_array($titleOrGroup)) {
				$groupOptions = array();
				foreach($titleOrGroup as $optionValue => $optionTitle) {
					// Check sub-option disabled status
					$disabled = false;
					if( array_key_exists($valueOrGroupTitle, $this->disabledItems)
							&& is_array($this->disabledItems[$valueOrGroupTitle])
							&& in_array($optionValue, $this->disabledItems[$valueOrGroupTitle]) ){
						$disabled = true;
					}
					$groupOptions[] = array(
						'Value' => $optionValue,
						'Disabled' => $disabled,
						'Title' => $optionTitle,
						'Selected' => $optionValue == $this->value
					);
				}

				$options[] = array(
					'Title' => $valueOrGroupTitle,
					'Options' => new ArrayList($groupOptions),
				);
			} else {
				// Single option
				$disabled = in_array($valueOrGroupTitle, $this->disabledItems);

				$options[] = array(
					'Value' => $valueOrGroupTitle,
					'Disabled' => $disabled,
					'Title' => $titleOrGroup,
					'Selected' => $valueOrGroupTitle == $this->value
				);
			}
		}

		// Render
		$this->extend('onBeforeRender', $this);
		$properties = array_merge($properties, array(
			'Options' => new ArrayList($options)
		));
		return $this
			->customise($properties)
			->renderWith($this->getTemplates());
	}

	public function Type() {
		return 'groupeddropdown dropdown';
	}

	/**
	 * Validate this field
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {
		$valid = false;
		$source = $this->getSourceAsArray();
		$disabled = $this->getDisabledItems();

		if ($this->value) {
			foreach ($source as $value => $title) {
				if (is_array($title) && array_key_exists($this->value, $title)) {
					// Check that the set value is not in the list of disabled items
					if (!isset($disabled[$value]) || !in_array($this->value, $disabled[$value])) {
						$valid = true;
					}
				// Check that the value matches and is not disabled
				} elseif($this->value == $value && !in_array($this->value, $disabled)) {
					$valid = true;
				}
			}
		} elseif ($this->getHasEmptyDefault()) {
			$valid = true;
		}

		if (!$valid) {
			$validator->validationError(
				$this->name,
				_t(
					'DropdownField.SOURCE_VALIDATION',
					"Please select a value within the list provided. {value} is not a valid option",
					array('value' => $this->value)
				),
				"validation"
			);
			return false;
		}

		return true;
	}

}
