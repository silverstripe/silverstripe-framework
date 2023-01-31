<?php

namespace SilverStripe\View\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ViewableDataTestObject extends DataObject implements TestOnly
{
    private string $privateProperty = 'private property';

    protected string $protectedProperty = 'protected property';

    public string $publicProperty = 'public property';

    private function privateMethod(): string
    {
        return 'Private function';
    }

    protected function protectedMethod(): string
    {
        return 'Protected function';
    }

    public function publicMethod(): string
    {
        return 'Public function';
    }
}
