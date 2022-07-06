<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;

class InjectedDataObject extends OverriddenDataObject implements TestOnly
{
    private static $db = [
        'NewField' => 'Varchar',
    ];
}
