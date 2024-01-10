<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Map;
use ArrayAccess;

/**
 * Represents a field that allows users to select one or more items from a list
 */
abstract class SelectField extends FormField
{

    /**
     * Associative or numeric array of all dropdown items,
     * with array key as the submitted field value, and the array value as a
     * natural language description shown in the interface element.
     *
     * @var array|ArrayAccess
     */
    protected $source;

    /**
     * The values for items that should be disabled (greyed out) in the dropdown.
     * This is a non-associative array
     *
     * @var array
     */
    protected $disabledItems = [];

    /**
     * @param string $name The field name
     * @param string $title The field title
     * @param array|ArrayAccess $source A map of the dropdown items
     * @param mixed $value The current value
     */
    public function __construct($name, $title = null, $source = [], $value = null)
    {
        $this->setSource($source);
        if (!isset($title)) {
            $title = $name;
        }
        parent::__construct($name, $title, $value);
    }

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();
        $disabled = $this->getDisabledItems();

        // Add options to 'data'
        $source = $this->getSource();
        $data['source'] = (is_array($source))
            ? array_map(function ($value, $title) use ($disabled) {
                return [
                    'value' => $value,
                    'title' => $title,
                    'disabled' => in_array($value, $disabled),
                ];
            }, array_keys($source), $source)
            : [];

        return $data;
    }

    /**
     * Mark certain elements as disabled,
     * regardless of the {@link setDisabled()} settings.
     *
     * These should be items that appear in the source list, not in addition to them.
     *
     * @param array|SS_List $items Collection of values or items
     * @return $this
     */
    public function setDisabledItems($items)
    {
        $this->disabledItems = $this->getListValues($items);
        return $this;
    }

    /**
     * Non-associative list of disabled item values
     *
     * @return array
     */
    public function getDisabledItems()
    {
        return $this->disabledItems;
    }

    /**
     * Check if the given value is disabled
     *
     * @param string $value
     * @return bool
     */
    protected function isDisabledValue($value)
    {
        if ($this->isDisabled()) {
            return true;
        }
        return in_array($value, $this->getDisabledItems() ?? []);
    }

    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            ['type' => null, 'value' => null]
        );
    }

    /**
     * Retrieve all values in the source array
     *
     * @return array
     */
    protected function getSourceValues()
    {
        return array_keys($this->getSource() ?? []);
    }

    /**
     * Gets all valid values for this field.
     *
     * Does not include "empty" value if specified
     *
     * @return array
     */
    public function getValidValues()
    {
        $valid = array_diff($this->getSourceValues() ?? [], $this->getDisabledItems());
        // Renumber indexes from 0
        return array_values($valid ?? []);
    }

    /**
     * Gets the source array not including any empty default values.
     *
     * @return array|ArrayAccess
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set the source for this list
     *
     * @param mixed $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $this->getListMap($source);
        return $this;
    }

    /**
     * Given a list of values, extract the associative map of id => title
     *
     * @param mixed $source
     * @return array Associative array of ids and titles
     */
    protected function getListMap($source)
    {
        // Extract source as an array
        if ($source instanceof SS_List) {
            $source = $source->map();
        }
        if ($source instanceof Map) {
            $source = $source->toArray();
        }
        if (!is_array($source) && !($source instanceof ArrayAccess)) {
            throw new \InvalidArgumentException('$source passed in as invalid type');
        }

        return $source;
    }

    /**
     * Given a non-array collection, extract the non-associative list of ids
     * If passing as array, treat the array values (not the keys) as the ids
     *
     * @param mixed $values
     * @return array Non-associative list of values
     */
    protected function getListValues($values)
    {
        // Empty values
        if (empty($values)) {
            return [];
        }

        // Direct array
        if (is_array($values)) {
            return array_values($values ?? []);
        }

        // Extract lists
        if ($values instanceof SS_List) {
            return $values->column('ID');
        }

        return [trim($values ?? '')];
    }

    /**
     * Determine if the current value of this field matches the given option value
     *
     * @param mixed $dataValue The value as extracted from the source of this field (or empty value if available)
     * @param mixed $userValue The value as submitted by the user
     * @return boolean True if the selected value matches the given option value
     */
    public function isSelectedValue($dataValue, $userValue)
    {
        if ($dataValue === $userValue) {
            return true;
        }

        // Allow null to match empty strings
        if ($dataValue === '' && $userValue === null) {
            return true;
        }

        // Safety check against casting arrays as strings in PHP>5.4
        if (is_array($dataValue) || is_array($userValue)) {
            return false;
        }

        // For non-falsey values do loose comparison
        if ($dataValue) {
            return $dataValue == $userValue;
        }

        // For empty values, use string comparison to perform visible value match
        return ((string) $dataValue) === ((string) $userValue);
    }

    public function performReadonlyTransformation()
    {
        $field = $this->castedCopy(LookupField::class);
        $field->setSource($this->getSource());
        $field->setReadonly(true);

        return $field;
    }

    public function performDisabledTransformation()
    {
        $clone = clone $this;
        $clone->setDisabled(true);
        return $clone;
    }

    public function castedCopy($classOrCopy)
    {
        $field = parent::castedCopy($classOrCopy);
        if ($field instanceof SelectField) {
            $field->setSource($this->getSource());
        }
        return $field;
    }
}
