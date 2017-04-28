<?php

namespace SilverStripe\Forms;

use ArrayAccess;

/**
 * Represents the base class for a single-select field
 */
abstract class SingleSelectField extends SelectField
{

    /**
     * Show the first <option> element as empty (not having a value),
     * with an optional label defined through {@link $emptyString}.
     * By default, the <select> element will be rendered with the
     * first option from {@link $source} selected.
     *
     * @var bool
     */
    protected $hasEmptyDefault = false;

    /**
     * The title shown for an empty default selection,
     * e.g. "Select...".
     *
     * @var string
     */
    protected $emptyString = '';

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_SINGLESELECT;

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();

        // Add options to 'data'
        $data['data']['hasEmptyDefault'] = $this->getHasEmptyDefault();
        $data['data']['emptyString'] = $this->getHasEmptyDefault() ? $this->getEmptyString() : null;

        $data['value'] = $this->getDefaultValue();

        return $data;
    }

    public function getDefaultValue()
    {
        $value = $this->Value();
        // assign value to field, such as first option available
        if ($value === null) {
            if ($this->getHasEmptyDefault()) {
                $value = '';
            } else {
                $values = $this->getValidValues();
                $value = array_shift($values);
            }
        }
        return $value;
    }

    /**
     * @param boolean $bool
     * @return self Self reference
     */
    public function setHasEmptyDefault($bool)
    {
        $this->hasEmptyDefault = $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHasEmptyDefault()
    {
        return $this->hasEmptyDefault;
    }

    /**
     * Set the default selection label, e.g. "select...".
     * Defaults to an empty string. Automatically sets
     * {@link $hasEmptyDefault} to true.
     *
     * @param string $string
     * @return $this
     */
    public function setEmptyString($string)
    {
        $this->setHasEmptyDefault(true);
        $this->emptyString = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmptyString()
    {
        return $this->emptyString;
    }

    /**
     * Gets the source array, including the empty string, if present
     *
     * @return array|ArrayAccess
     */
    public function getSourceEmpty()
    {
        // Inject default option
        if ($this->getHasEmptyDefault()) {
            return array('' => $this->getEmptyString()) + $this->getSource();
        } else {
            return $this->getSource();
        }
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Check if valid value is given
        $selected = $this->Value();
        if (strlen($selected)) {
            // Use selection rules to check which are valid
            foreach ($this->getValidValues() as $formValue) {
                if ($this->isSelectedValue($formValue, $selected)) {
                    return true;
                }
            }
        } else {
            if ($this->getHasEmptyDefault()) {
                // Check empty value
                return true;
            }
            $selected = '(none)';
        }

        // Fail
        $validator->validationError(
            $this->name,
            _t(
                'SilverStripe\\Forms\\DropdownField.SOURCE_VALIDATION',
                "Please select a value within the list provided. {value} is not a valid option",
                array('value' => $selected)
            ),
            "validation"
        );
        return false;
    }

    public function castedCopy($classOrCopy)
    {
        $field = parent::castedCopy($classOrCopy);
        if ($field instanceof SingleSelectField && $this->getHasEmptyDefault()) {
            $field->setEmptyString($this->getEmptyString());
        }
        return $field;
    }
}
