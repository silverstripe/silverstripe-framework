<?php

namespace SilverStripe\Forms;

/**
 * Single checkbox field.
 */
class CheckboxField extends FormField
{

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_BOOLEAN;
    
    public function setValue($value)
    {
        $this->value = ($value) ? 1 : 0;
        return $this;
    }

    public function dataValue()
    {
        return ($this->value) ? 1 : null;
    }

    public function Value()
    {
        return ($this->value) ? 1 : 0;
    }

    public function getAttributes()
    {
        $attrs = parent::getAttributes();
        $attrs['value'] = 1;
        return array_merge(
            $attrs,
            array(
                'checked' => ($this->Value()) ? 'checked' : null,
                'type' => 'checkbox',
            )
        );
    }

    /**
     * Returns a readonly version of this field
     */
    public function performReadonlyTransformation()
    {
        $field = new CheckboxField_Readonly($this->name, $this->title, $this->value);
        $field->setForm($this->form);
        return $field;
    }
}
