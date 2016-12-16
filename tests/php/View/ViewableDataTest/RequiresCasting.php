<?php

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class RequiresCasting extends ViewableData implements TestOnly
{

    public $test = 'overwritten';

    public function forTemplate()
    {
        return 'casted';
    }

    public function setValue()
    {
    }
}
