<?php

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class UnescapedCaster extends ViewableData implements TestOnly
{
    protected $value;

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function forTemplate()
    {
        return Convert::raw2xml($this->value);
    }
}
