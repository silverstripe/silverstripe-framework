<?php

namespace SilverStripe\Forms\Tests\FormTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\Form;

/**
 * @skipUpgrade
 */
class ExtraFieldsForm extends Form implements TestOnly
{
    public function getExtraFields()
    {
        $fields = parent::getExtraFields();

        $fields->push(new CheckboxField('ExtraFieldCheckbox', 'Extra Field Checkbox', 1));

        return $fields;
    }
}
