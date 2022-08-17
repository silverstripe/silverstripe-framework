<?php

namespace SilverStripe\Forms;

/**
 * Single checkbox field.
 */
class CheckboxField extends FormField
{

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_BOOLEAN;

    public function setValue(int|bool|float|string $value, array|DNADesign\Elemental\Models\ElementContent $data = null): SilverStripe\Forms\CheckboxField
    {
        $this->value = ($value) ? 1 : 0;
        return $this;
    }

    public function dataValue(): null|int
    {
        return ($this->value) ? 1 : null;
    }

    public function Value(): int
    {
        return ($this->value) ? 1 : 0;
    }

    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['value'] = 1;
        if ($this->Required()) {
            // Semantically, it doesn't make sense to have a required attribute
            // on a field in which both checked and unchecked are allowable.
            unset($attributes['aria-required']);
        }

        return array_merge(
            $attributes,
            [
                'checked' => ($this->Value()) ? 'checked' : null,
                'type' => 'checkbox',
            ]
        );
    }

    /**
     * Returns a readonly version of this field
     */
    public function performReadonlyTransformation(): SilverStripe\Forms\CheckboxField_Readonly
    {
        $field = new CheckboxField_Readonly($this->name, $this->title, $this->value);
        $field->setForm($this->form);
        return $field;
    }
}
