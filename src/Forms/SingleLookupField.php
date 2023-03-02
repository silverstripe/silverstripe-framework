<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\Map;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Read-only complement of {@link DropdownField}.
 *
 * Shows the "human value" of the dropdown field for the currently selected
 * value.
 */
class SingleLookupField extends SingleSelectField
{
    /**
     * @var bool
     */
    protected $readonly = true;

    /**
     * @return mixed|null
     */
    protected function valueToLabel()
    {
        $value = $this->value;
        $source = $this->getSource();
        $source = ($source instanceof Map) ? $source->toArray() : $source;

        if (array_key_exists($value, $source ?? [])) {
            return $source[$value];
        }

        return null;
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
     * @return SingleLookupField
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;

        return $clone;
    }

    /**
     * @return bool
     */
    public function getHasEmptyDefault()
    {
        return false;
    }

    /**
     * @return string
     */
    public function Type()
    {
        return 'single-lookup readonly';
    }

    /**
     * Note: we need to transform value in here because React fields do not use Field() to display value
     *
     * @return mixed
     */
    public function Value()
    {
        $label = $this->valueToLabel();
        if (!is_null($label)) {
            return $label;
        }

        return parent::Value();
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        // this field uses the same default template as LookupField
        return parent::getTemplate() ?: LookupField::class;
    }

    /**
     * Returns a readonly span containing the correct value.
     *
     * @param array $properties
     *
     * @return string
     */
    public function Field($properties = [])
    {
        $label = $this->valueToLabel();
        if (!is_null($label)) {
            $attrValue = Convert::raw2xml($label);
            $inputValue = $this->value;
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
}
