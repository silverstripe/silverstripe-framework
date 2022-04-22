<?php

namespace SilverStripe\Forms;

/**
 * Read-only field to display a non-editable value with a label.
 * Consider using an {@link LabelField} if you just need a label-less
 * value display.
 */
class ReadonlyField extends FormField
{

    protected $readonly = true;

    /**
     * Include a hidden field in the HTML for the readonly field
     * @var boolean
     */
    protected $includeHiddenField = false;

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_TEXT;

    /**
     * If true, a hidden field will be included in the HTML for the readonly field.
     *
     * This can be useful if you need to pass the data through on the form submission, as
     * long as it's okay than an attacker could change the data before it's submitted.
     *
     * This is disabled by default as it can introduce security holes if the data is not
     * allowed to be modified by the user.
     *
     * @param boolean $includeHiddenField
     * @return $this
     */
    public function setIncludeHiddenField($includeHiddenField)
    {
        $this->includeHiddenField = $includeHiddenField;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIncludeHiddenField()
    {
        return $this->includeHiddenField;
    }

    public function performReadonlyTransformation()
    {
        return clone $this;
    }

    public function Type()
    {
        return 'readonly';
    }

    public function castingHelper($field)
    {
        // Get dynamic cast for 'Value' field
        if (strcasecmp($field ?? '', 'Value') === 0) {
            return $this->getValueCast();
        }

        // Fall back to default casting
        return parent::castingHelper($field);
    }

    public function getSchemaStateDefaults()
    {
        $state = parent::getSchemaStateDefaults();
        $state['value'] = $this->dataValue();

        return $state;
    }


    /**
     * @return mixed|string
     */
    public function Value()
    {
        // Get raw value
        $value = $this->dataValue();
        if ($value) {
            return $value;
        }

        // "none" text
        $label = _t('SilverStripe\\Forms\\FormField.NONE', 'none');
        return "<i>('{$label}')</i>";
    }

    /**
     * Get custom cating helper for Value() field
     *
     * @return string
     */
    public function getValueCast()
    {
        // Casting class for 'none' text
        $value = $this->dataValue();
        if (empty($value)) {
            return 'HTMLFragment';
        }

        // Use default casting
        $casting = $this->config()->get('casting');
        return $casting['Value'];
    }
}
