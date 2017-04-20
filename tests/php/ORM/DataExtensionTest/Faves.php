<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\Tests\DataExtensionTest\RelatedObject;

class Faves extends DataExtension implements TestOnly
{

    private static $many_many = array(
        'Faves' => RelatedObject::class
    );
}
