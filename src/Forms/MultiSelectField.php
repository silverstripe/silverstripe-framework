<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBMultiEnum;
use SilverStripe\ORM\Relation;

/**
 * Represents a SelectField that may potentially have multiple selections, and may have
 * a {@link ManyManyList} as a data source.
 */
abstract class MultiSelectField extends SelectField
{

    /**
     * List of items to mark as checked, and may not be unchecked
     *
     * @var array
     */
    protected $defaultItems = [];

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_MULTISELECT;

    /**
     * Extracts the value of this field, normalised as an array.
     * Scalar values will return a single length array, even if empty
     *
     * @return array List of values as an array
     */
    public function getValueArray()
    {
        return $this->getListValues($this->Value());
    }

    /**
     * Default selections, regardless of the {@link setValue()} settings.
     * Note: Items marked as disabled through {@link setDisabledItems()} can still be
     * selected by default through this method.
     *
     * @param array $items Collection of array keys, as defined in the $source array
     * @return $this Self reference
     */
    public function setDefaultItems($items)
    {
        $this->defaultItems = $this->getListValues($items);
        return $this;
    }

    /**
     * Default selections, regardless of the {@link setValue()} settings.
     *
     * @return array
     */
    public function getDefaultItems()
    {
        return $this->defaultItems;
    }

    /**
     * Load a value into this MultiSelectField
     *
     * @param mixed $value
     * @param null|array|DataObject $obj {@see Form::loadDataFrom}
     * @return $this
     */
    public function setValue($value, $obj = null)
    {
        // If we're not passed a value directly,
        // we can look for it in a relation method on the object passed as a second arg
        if ($obj instanceof DataObject) {
            $this->loadFrom($obj);
        } else {
            parent::setValue($value);
        }
        return $this;
    }

    /**
     * Load the value from the dataobject into this field
     *
     * @param DataObject|DataObjectInterface $record
     */
    public function loadFrom(DataObjectInterface $record)
    {
        $fieldName = $this->getName();
        if (empty($fieldName) || empty($record)) {
            return;
        }

        $relation = $record->hasMethod($fieldName)
            ? $record->$fieldName()
            : null;

        // Detect DB relation or field
        if ($relation instanceof Relation) {
            // Load ids from relation
            $value = array_values($relation->getIDList() ?? []);
            parent::setValue($value);
        } elseif ($record->hasField($fieldName)) {
            // Load dataValue from field... a CSV for DBMultiEnum
            if ($record->obj($fieldName) instanceof DBMultiEnum) {
                $value = $this->csvDecode($record->$fieldName);

            // ... JSON-encoded string for other fields
            } else {
                $value = $this->stringDecode($record->$fieldName);
            }

            parent::setValue($value);
        }
    }


    /**
     * Save the current value of this MultiSelectField into a DataObject.
     * If the field it is saving to is a has_many or many_many relationship,
     * it is saved by setByIDList(), otherwise it creates a comma separated
     * list for a standard DB text/varchar field.
     *
     * @param DataObject|DataObjectInterface $record The record to save into
     */
    public function saveInto(DataObjectInterface $record)
    {
        $fieldName = $this->getName();
        if (empty($fieldName) || empty($record)) {
            return;
        }

        $relation = $record->hasMethod($fieldName)
            ? $record->$fieldName()
            : null;

        // Detect DB relation or field
        $items = $this->getValueArray();
        if ($relation instanceof Relation) {
            // Save ids into relation
            $relation->setByIDList($items);
        } elseif ($record->hasField($fieldName)) {
            // Save dataValue into field... a CSV for DBMultiEnum
            if ($record->obj($fieldName) instanceof DBMultiEnum) {
                $record->$fieldName = $this->csvEncode($items);

            // ... JSON-encoded string for other fields
            } else {
                $record->$fieldName = $this->stringEncode($items);
            }
        }
    }

    /**
     * Encode a list of values into a string, or null if empty (to simplify empty checks)
     *
     * @param array $value
     * @return string|null
     */
    public function stringEncode($value)
    {
        return $value
            ? json_encode(array_values($value))
            : null;
    }

    /**
     * Extract a string value into an array of values
     *
     * @param string $value
     * @return array
     */
    protected function stringDecode($value)
    {
        // Handle empty case
        if (empty($value)) {
            return [];
        }

        // If json deserialisation fails, then fallover to legacy format
        $result = json_decode($value ?? '', true);
        if ($result !== false) {
            return $result;
        }

        throw new \InvalidArgumentException("Invalid string encoded value for multi select field");
    }

    /**
     * Encode a list of values into a string as a comma separated list.
     * Commas will be stripped from the items passed in
     *
     * @param array $value
     * @return string|null
     */
    protected function csvEncode($value)
    {
        if (!$value) {
            return null;
        }
        return implode(
            ',',
            array_map(
                function ($x) {
                    return str_replace(',', '', $x ?? '');
                },
                array_values($value ?? [])
            )
        );
    }

    /**
     * Decode a list of values from a comma separated string.
     * Spaces are trimmed
     *
     * @param string $value
     * @return array
     */
    protected function csvDecode($value)
    {
        if (!$value) {
            return [];
        }

        return preg_split('/\s*,\s*/', trim($value ?? ''));
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $values = $this->getValueArray();
        $validValues = $this->getValidValues();

        // Filter out selected values not in the data source
        $self = $this;
        $invalidValues = array_filter(
            $values ?? [],
            function ($userValue) use ($self, $validValues) {
                foreach ($validValues as $formValue) {
                    if ($self->isSelectedValue($formValue, $userValue)) {
                        return false;
                    }
                }
                return true;
            }
        );

        $result = true;
        if (!empty($invalidValues)) {
            $result = false;
            // List invalid items
            $validator->validationError(
                $this->getName(),
                _t(
                    'SilverStripe\\Forms\\MultiSelectField.SOURCE_VALIDATION',
                    "Please select values within the list provided. Invalid option(s) {value} given",
                    ['value' => implode(',', $invalidValues)]
                ),
                "validation"
            );
        }

        return $this->extendValidationResult($result, $validator);
    }

    /**
     * Transforms the source data for this CheckboxSetField
     * into a comma separated list of values.
     *
     * @return ReadonlyField
     */
    public function performReadonlyTransformation()
    {
        $field = $this->castedCopy('SilverStripe\\Forms\\LookupField');
        $field->setSource($this->getSource());
        $field->setReadonly(true);

        // Pass through default items
        if (!$this->getValueArray() && $this->getDefaultItems()) {
            $field->setValue($this->getDefaultItems());
        }

        return $field;
    }
}
