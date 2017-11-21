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
    protected $disabledItems = array();

    /**
     * Creates a new dropdown field.
     *
     * @param string $name The field name
     * @param string $title The field title
     * @param array $source An map of the dropdown items
     * @param string|array|null $value You can pass an array of values or a single value like a drop down to be selected
     * @param int $size Optional size of the select element
     */
    public function __construct($name, $title = '', $source = array(), $value = null, $size = null)
    {
        if ($size) {
            $this->setSize($size);
        }

        parent::__construct($name, $title, $source, $value);
    }

    /**
     * Returns a select tag containing all the appropriate option tags
     *
     * @param array $properties
     * @return string
     */
    public function Field($properties = array())
    {
        $properties = array_merge($properties, array(
            'Options' => $this->getOptions(),
        ));

        return FormField::Field($properties);
    }

    /**
     * Gets the list of options to render in this formfield
     *
     * @return ArrayList
     */
    public function getOptions()
    {
        // Loop through and figure out which values were selected.
        $options = array();
        $selectedValue = $this->getValueArray();
        foreach ($this->getSource() as $itemValue => $title) {
            $itemSelected = in_array($itemValue, $selectedValue)
                || in_array($itemValue, $this->getDefaultItems());
            $itemDisabled = $this->isDisabled()
                || in_array($itemValue, $this->getDisabledItems());
            $options[] = new ArrayData(array(
                'Title' => $title,
                'Value' => $itemValue,
                'Selected' => $itemSelected,
                'Disabled' => $itemDisabled,
            ));
        }

        $options = new ArrayList($options);
        $this->extend('updateGetOptions', $options);
        return $options;
    }

    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            array(
                'multiple' => 'true',
                'size' => $this->getSize(),
                'name' => $this->getName() . '[]'
            )
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
}
