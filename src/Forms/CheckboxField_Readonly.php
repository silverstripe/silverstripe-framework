<?php

namespace SilverStripe\Forms;

/**
 * Readonly version of a checkbox field - "Yes" or "No".
 */
class CheckboxField_Readonly extends ReadonlyField
{

    public function performReadonlyTransformation(): SilverStripe\Forms\CheckboxField_Readonly
    {
        return clone $this;
    }

    public function Value(): string
    {
        return $this->value ?
            _t('SilverStripe\\Forms\\CheckboxField.YESANSWER', 'Yes') :
            _t('SilverStripe\\Forms\\CheckboxField.NOANSWER', 'No');
    }

    public function getValueCast(): string
    {
        return 'Text';
    }
}
