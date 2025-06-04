<?php

namespace SilverStripe\Forms;

use SilverStripe\Dev\Deprecation;

/**
 * Validates the internal state of all fields in the form.
 *
 * @deprecated 5.4.0 Will be replaced with functionality inside SilverStripe\Forms\Form::validate() in a future major release
 */
class FieldsValidator extends Validator
{
    public function __construct()
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be replaced with functionality inside SilverStripe\Forms\Form::validate() in a future major release',
            Deprecation::SCOPE_CLASS
        );
        parent::__construct();
    }

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
