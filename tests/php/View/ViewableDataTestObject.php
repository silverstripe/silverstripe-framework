<?php

namespace SilverStripe\View\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ViewableDataTestObject extends DataObject implements TestOnly
{
    private function privateMethod(): string
    {
        return 'Private function';
    }

    public function publicMethod(): string
    {
        return 'Public function';
    }
}
