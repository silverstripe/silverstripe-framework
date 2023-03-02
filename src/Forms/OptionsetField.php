<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

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
 *  // returns a Map object containing an array of ID => Title
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
 */
class OptionsetField extends SingleSelectField
{
    protected $schemaComponent = 'OptionsetField';

    /**
     * Build a field option for template rendering
     *
     * @param mixed $value Value of the option
     * @param string $title Title of the option
     * @param boolean $odd True if this should be striped odd. Otherwise it should be striped even
     * @return ArrayData Field option
     */
    protected function getFieldOption($value, $title, $odd)
    {
        return new ArrayData([
            'ID' => $this->getOptionID($value),
            'Class' => $this->getOptionClass($value, $odd),
            'Role' => 'option',
            'Name' => $this->getOptionName(),
            'Value' => $value,
            'Title' => $title,
            'isChecked' => $this->isSelectedValue($value, $this->Value()),
            'isDisabled' => $this->isDisabledValue($value)
        ]);
    }

    /**
     * Generate an ID property for a single option
     *
     * @param string $value
     * @return string
     */
    protected function getOptionID($value)
    {
        return $this->ID() . '_' . Convert::raw2htmlid($value);
    }

    /**
     * Get the "name" property for each item in the list
     *
     * @return string
     */
    protected function getOptionName()
    {
        return $this->getName();
    }

    /**
     * Get extra classes for each item in the list
     *
     * @param string $value Value of this item
     * @param bool $odd If this item is odd numbered in the list
     * @return string
     */
    protected function getOptionClass($value, $odd)
    {
        $oddClass = $odd ? 'odd' : 'even';
        $valueClass = ' val' . Convert::raw2htmlid($value);
        return $oddClass . $valueClass;
    }


    public function Field($properties = [])
    {
        $options = [];
        $odd = false;

        // Add all options striped
        foreach ($this->getSourceEmpty() as $value => $title) {
            $odd = !$odd;
            $options[] = $this->getFieldOption($value, $title, $odd);
        }

        $properties = array_merge($properties, [
            'Options' => new ArrayList($options)
        ]);

        return FormField::Field($properties);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($validator)
    {
        if (!$this->Value()) {
            return $this->extendValidationResult(true, $validator);
        }

        return parent::validate($validator);
    }

    public function getAttributes()
    {
        $attributes = array_merge(
            parent::getAttributes(),
            ['role' => 'listbox']
        );

        unset($attributes['name']);
        unset($attributes['required']);
        return $attributes;
    }
}
