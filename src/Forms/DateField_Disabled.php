<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBDate;

/**
 * Disabled version of {@link DateField}.
 * Allows dates to be represented in a form, by showing in a user friendly format, eg, dd/mm/yyyy.
 */
class DateField_Disabled extends DateField
{

    protected $disabled = true;

    public function Field($properties = [])
    {
        // Default display value
        $displayValue = '<i>(' . _t('SilverStripe\\Forms\\DateField.NOTSET', 'not set') . ')</i>';

        $value = $this->dataValue();

        if ($value) {
            $value = $this->tidyInternal($value);
            $df = new DBDate($this->name);
            $df->setValue($value);

            if ($df->IsToday()) {
                // e.g. 2018-06-01 (today)
                $format = '%s (%s)';
                $infoComplement = _t('SilverStripe\\Forms\\DateField.TODAY', 'today');
            } else {
                // e.g. 2018-06-01, 5 days ago
                $format = '%s, %s';
                $infoComplement = $df->Ago();
            }

            // Render the display value with some complement of info
            $displayValue = Convert::raw2xml(sprintf(
                $format ?? '',
                $this->Value(),
                $infoComplement
            ));
        }

        return sprintf(
            "<span class=\"readonly\" id=\"%s\">%s</span>",
            $this->ID(),
            $displayValue
        );
    }

    public function Type()
    {
        return "date_disabled readonly " . parent::Type();
    }

    public function getHTML5()
    {
        // Always disable HTML5 feature when using the readonly field.
        return false;
    }
}
