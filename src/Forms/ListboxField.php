<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

/**
 * Multi-line listbox field, created from a select tag.
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
 * @see DropdownField for a simple select field with a single element.
 * @see CheckboxSetField for multiple selections through checkboxes.
 * @see OptionsetField for single selections via radiobuttons.
 * @see TreeDropdownField for a rich and customizeable UI that can visualize a tree of selectable elements
 */
class ListboxField extends MultiSelectField
{

    /**
     * The size of the field in rows.
     *
     * @var int
     */
    protected $size;

    /**
     * @var array
     */
    protected $disabledItems = [];


    protected $schemaComponent = 'ListboxField';

    /**
     * Creates a new dropdown field.
     *
     * @param string $name The field name
     * @param string $title The field title
     * @param array $source An map of the dropdown items
     * @param string|array|null $value You can pass an array of values or a single value like a drop down to be selected
     * @param int $size Optional size of the select element
     */
    public function __construct($name, $title = '', $source = [], $value = null, $size = null)
    {
        if ($size) {
            $this->setSize($size);
        }

        $this->addExtraClass('ss-listbox-field');

        parent::__construct($name, $title, $source, $value);
    }

    /**
     * Returns a select tag containing all the appropriate option tags
     *
     * @param array $properties
     * @return string
     */
    public function Field($properties = [])
    {
        $properties = array_merge($properties, [
            'Options' => $this->getOptions(),
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
        // Loop through and figure out which values were selected.
        $options = [];
        $selectedValue = $this->getValueArray();
        foreach ($this->getSource() as $itemValue => $title) {
            $itemSelected = in_array($itemValue, $selectedValue ?? [])
                || in_array($itemValue, $this->getDefaultItems() ?? []);
            $itemDisabled = $this->isDisabled()
                || in_array($itemValue, $this->getDisabledItems() ?? []);
            $options[] = new ArrayData([
                'Title' => $title,
                'Value' => $itemValue,
                'Selected' => $itemSelected,
                'Disabled' => $itemDisabled,
            ]);
        }

        $options = new ArrayList($options);
        $this->extend('updateGetOptions', $options);

        return $options;
    }

    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            [
                'multiple' => 'true',
                'size' => $this->getSize(),
                'name' => $this->getName() . '[]'
            ]
        );
    }

    /**
     * Get the size of this dropdown in rows.
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the size of this dropdown in rows.
     *
     * @param int $size The height in rows (e.g. 3)
     * @return $this Self reference
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Mark certain elements as disabled,
     * regardless of the {@link setDisabled()} settings.
     *
     * @param array $items Collection of array keys, as defined in the $source array
     * @return $this Self reference
     */
    public function setDisabledItems($items)
    {
        $this->disabledItems = $items;
        return $this;
    }

    /**
     * @return array
     */
    public function getDisabledItems()
    {
        return $this->disabledItems;
    }

     /**
     * Provide ListboxField data to the JSON schema for the frontend component
     *
     * @return array
     */
    public function getSchemaDataDefaults()
    {
        $options = $this->getOptions();
        $selected = $options->filter('Selected', true);
        $name = $this->getName();
        $schema = array_merge(
            parent::getSchemaDataDefaults(),
            [
                'name' => $name,
                'lazyLoad' => false,
                'creatable' => false,
                'multi' => true,
                'value' => $selected->count() ? $selected->toNestedArray() : null,
                'disabled' => $this->isDisabled() || $this->isReadonly(),
            ]
        );

        $schema['options'] = array_values($options->toNestedArray() ?? []);

        return $schema;
    }

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();

        // Add options to 'data'
        $data['lazyLoad'] = false;
        $data['multi'] = true;
        $data['creatable'] = false;
        $options = $this->getOptions()->filter('Selected', true);
        $data['value'] = $options->count() ? $options->toNestedArray() : null;

        return $data;
    }

    /**
     * Returns array of arrays representing tags.
     *
     * @param  string $term
     * @return array
     */
    protected function getOptionsArray($term)
    {
        $source = $this->getSourceList();
        if (!$source) {
            return [];
        }

        $titleField = $this->getTitleField();

        $query = $source
            ->filter($titleField . ':PartialMatch:nocase', $term)
            ->sort($titleField);

        // Map into a distinct list
        $items = [];
        $titleField = $this->getTitleField();

        foreach ($query->map('ID', $titleField)->values() as $title) {
            $items[$title] = [
                'Title' => $title,
                'Value' => $title,
            ];
        }

        return array_values($items ?? []);
    }

    public function getValueArray()
    {
        $value = $this->Value();
        $validValues = $this->getValidValues();
        if (empty($validValues)) {
            return [];
        }

        $canary = reset($validValues);
        $targetType = gettype($canary);
        if (is_array($value) && count($value) > 0) {
            $first = reset($value);
            // sanity check the values - make sure strings get strings, ints get ints etc
            if ($targetType !== gettype($first)) {
                $replaced = [];
                foreach ($value as $item) {
                    if (!is_array($item)) {
                        $item = json_decode($item, true);
                    }

                    if ($targetType === gettype($item)) {
                        $replaced[] = $item;
                    } elseif (isset($item['Value'])) {
                        $replaced[] = $item['Value'];
                    }
                }

                $value = $replaced;
            }
        }

        return $this->getListValues($value);
    }
}
