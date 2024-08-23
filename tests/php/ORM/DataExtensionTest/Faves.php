<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\Tests\DataExtensionTest\RelatedObject;

class Faves extends Extension implements TestOnly
{
    private static $many_many = [
        'Faves' => RelatedObject::class
    ];
}
