<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldFilterHeaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ArrayData;

class NonDataObject extends ArrayData implements TestOnly
{
    public function summaryFields()
    {
        return ['Title' => 'Title'];
    }
}
