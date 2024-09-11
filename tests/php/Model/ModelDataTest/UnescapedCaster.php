<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class UnescapedCaster extends ModelData implements TestOnly
{
    protected $value;

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function forTemplate(): string
    {
        return Convert::raw2xml($this->value);
    }
}
