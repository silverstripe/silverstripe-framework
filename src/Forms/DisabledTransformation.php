<?php

namespace SilverStripe\Forms;

/**
 * Transformation that disables all the fields on the form.
 */
class DisabledTransformation extends FormTransformation
{
    public function transform(FormField $field): SilverStripe\Forms\TextField
    {
        return $field->performDisabledTransformation();
    }
}
