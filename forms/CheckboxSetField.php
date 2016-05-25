<?php
/**
 * Displays a set of checkboxes as a logical group.
 *
 * ASSUMPTION -> IF you pass your source as an array, you pass values as an array too. Likewise objects are handled
 * the same.
 *
 * Example:
 * <code>
 * new CheckboxSetField(
 *  $name = "topics",
 *  $title = "I am interested in the following topics",
 *  $source = array(
 *      "1" => "Technology",
 *      "2" => "Gardening",
 *      "3" => "Cooking",
 *      "4" => "Sports"
 *  ),
 *  $value = "1"
 * );
 * </code>
 *
 * <b>Saving</b>
 * The checkbox set field will save its data in one of ways:
 * - If the field name matches a many-many join on the object being edited, that many-many join will be updated to
 *   link to the objects selected on the checkboxes.  In this case, the keys of your value map should be the IDs of
 *   the database records.
 * - If the field name matches a database field, a comma-separated list of values will be saved to that field.  The
 *   keys can be text or numbers.
 *
 * @todo Document the different source data that can be used
 * with this form field - e.g ComponentSet, ArrayList,
 * array. Is it also appropriate to accept so many different
 * types of data when just using an array would be appropriate?
 *
 * @package forms
 * @subpackage fields-basic
 */

use SilverStripe\Model\ArrayList;
class CheckboxSetField extends MultiSelectField {

	protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_MULTISELECT;

	/**
	 * @todo Explain different source data that can be used with this field,
	 * e.g. SQLMap, ArrayList or an array.
	 */
	public function Field($properties = array()) {
		Requirements::css(FRAMEWORK_DIR . '/client/dist/styles/CheckboxSetField.css');

		$properties = array_merge($properties, array(
			'Options' => $this->getOptions()
		));

		return $this->customise($properties)->renderWith(
			$this->getTemplates()
		);
	}

	/**
	 * Gets the list of options to render in this formfield
	 *
	 * @return ArrayList
	 */
	public function getOptions() {
		$selectedValues = $this->getValueArray();
		$defaultItems = $this->getDefaultItems();

		// Generate list of options to display
		$odd = 0;
		$formID = $this->ID();
		$options = new ArrayList();
		foreach($this->getSource() as $itemValue => $title) {
			$itemID = Convert::raw2htmlid("{$formID}_{$itemValue}");
			$odd = ($odd + 1) % 2;
			$extraClass = $odd ? 'odd' : 'even';
			$extraClass .= ' val' . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $itemValue);

			$itemChecked = in_array($itemValue, $selectedValues) || in_array($itemValue, $defaultItems);
			$itemDisabled = $this->isDisabled() || in_array($itemValue, $defaultItems);

			$options->push(new ArrayData(array(
				'ID' => $itemID,
				'Class' => $extraClass,
				'Name' => "{$this->name}[{$itemValue}]",
				'Value' => $itemValue,
				'Title' => $title,
				'isChecked' => $itemChecked,
				'isDisabled' => $itemDisabled,
			)));
		}
		$this->extend('updateGetOptions', $options);
		return $options;
	}

	public function Type() {
		return 'optionset checkboxset';
	}

}
