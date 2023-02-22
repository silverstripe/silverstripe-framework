<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Read-only complement of {@link MultiSelectField}.
 *
 * Shows the "human value" of the MultiSelectField for the currently selected
 * value.
 */
class LookupField extends MultiSelectField
{
    protected $schemaComponent = 'LookupField';

    /**
     * @var boolean $readonly
     */
    protected $readonly = true;

    /**
     * Returns a readonly span containing the correct value.
     *
     * @param array $properties
     *
     * @return string
     */
    public function Field($properties = [])
    {
        $source = ArrayLib::flatten($this->getSource());
        $values = $this->getValueArray();

        // Get selected values
        $mapped = [];
        foreach ($values as $value) {
            if (isset($source[$value])) {
                $mapped[] = Convert::raw2xml($source[$value]);
            }
        }

        // Don't check if string arguments are matching against the source,
        // as they might be generated HTML diff views instead of the actual values
        if ($this->value && is_string($this->value) && empty($mapped)) {
            $mapped[] = Convert::raw2xml(trim($this->value ?? ''));
            $values = [];
        }

        if ($mapped) {
            $attrValue = implode(', ', array_values($mapped ?? []));
            $inputValue = implode(', ', array_values($values ?? []));
        } else {
            $attrValue = '<i>(' . _t('SilverStripe\\Forms\\FormField.NONE', 'none') . ')</i>';
            $inputValue = '';
        }

        $properties = array_merge($properties, [
            'AttrValue' => DBField::create_field('HTMLFragment', $attrValue),
            'InputValue' => $inputValue
        ]);

        return parent::Field($properties);
    }

    /**
     * Ignore validation as the field is readonly
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        return $this->extendValidationResult(true, $validator);
    }

    /**
     * Stubbed so invalid data doesn't save into the DB
     *
     * @param DataObjectInterface $record DataObject to save data into
     */
    public function saveInto(DataObjectInterface $record)
    {
    }

    /**
     * @return LookupField
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        return $clone;
    }

    public function getHasEmptyDefault()
    {
        return false;
    }

    /**
     * @return string
     */
    public function Type()
    {
        return "lookup readonly";
    }
}
