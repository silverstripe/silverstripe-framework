<?php

namespace SilverStripe\Forms;

/**
 * Readonly version of a checkbox field - "Yes" or "No".
 */
class CheckboxField_Readonly extends ReadonlyField
{

    public function performReadonlyTransformation()
    {
        return clone $this;
    }

    public function Value()
    {
        return $this->value ?
            _t('CheckboxField.YESANSWER', 'Yes') :
            _t('CheckboxField.NOANSWER', 'No');
    }

    public function getValueCast()
    {
        return 'Text';
    }
}
