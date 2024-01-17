<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;
use SilverStripe\View\ArrayData;

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
 */
class CheckboxSetField extends MultiSelectField
{

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_MULTISELECT;

    /**
     * @param array $properties
     * @return DBHTMLText
     */
    public function Field($properties = [])
    {
        $properties = array_merge($properties, [
            'Options' => $this->getOptions()
        ]);

        return FormField::Field($properties);
    }

    /**
     * Gets the list of options to render in this formfield
     *
     * @return ArrayList<ArrayData>
     */
    public function getOptions()
    {
        $selectedValues = $this->getValueArray();
        $defaultItems = $this->getDefaultItems();
        $disabledItems = $this->getDisabledItems();

        // Generate list of options to display
        $odd = false;
        $formID = $this->ID();
        $options = new ArrayList();
        foreach ($this->getSource() as $itemValue => $title) {
            $itemID = Convert::raw2htmlid("{$formID}_{$itemValue}");
            $odd = !$odd;
            $extraClass = $odd ? 'odd' : 'even';
            $extraClass .= ' val' . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $itemValue ?? '');

            $itemChecked = in_array($itemValue, $selectedValues ?? []) || in_array($itemValue, $defaultItems ?? []);
            $itemDisabled = $this->isDisabled() || in_array($itemValue, $disabledItems ?? []);

            $options->push(new ArrayData([
                'ID' => $itemID,
                'Class' => $extraClass,
                'Role' => 'option',
                'Name' => "{$this->name}[{$itemValue}]",
                'Value' => $itemValue,
                'Title' => $title,
                'isChecked' => $itemChecked,
                'isDisabled' => $itemDisabled,
            ]));
        }
        $this->extend('updateGetOptions', $options);
        return $options;
    }

    public function Type()
    {
        return 'optionset checkboxset';
    }

    public function getAttributes()
    {
        $attributes = array_merge(
            parent::getAttributes(),
            ['role' => 'listbox']
        );

        // Remove invalid attributes from wrapper.
        unset($attributes['name']);
        unset($attributes['required']);
        unset($attributes['aria-required']);
        return $attributes;
    }
}
