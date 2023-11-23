<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ArrayData;

class ArrayDataWithID extends ArrayData implements TestOnly
{
    public function __construct($value = [])
    {
        $value['ID'] ??= 0;
        parent::__construct($value);
    }
}
