<?php

namespace SilverStripe\Forms;

/**
 * Validates the internal state of all fields in the form.
 */
class FieldsValidator extends Validator
{
    public function php($data): bool
    {
        $valid = true;
        $fields = $this->form->Fields();

        foreach ($fields as $field) {
            $valid = ($field->validate($this) && $valid);
        }

        return $valid;
    }

    public function canBeCached(): bool
    {
        return true;
    }
}
