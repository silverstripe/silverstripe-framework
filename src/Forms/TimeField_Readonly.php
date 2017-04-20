<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;

/**
 * The readonly class for our {@link TimeField}.
 */
class TimeField_Readonly extends TimeField
{

    protected $readonly = true;

    public function Field($properties = array())
    {
        if ($this->valueObj) {
            $val = Convert::raw2xml($this->valueObj->toString($this->getConfig('timeformat')));
        } else {
            // TODO Localization
            $val = '<i>(not set)</i>';
        }

        return "<span class=\"readonly\" id=\"" . $this->ID() . "\">$val</span>";
    }
}
