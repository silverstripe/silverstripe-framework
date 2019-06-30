<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DBStringTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBString;

class MyStringField extends DBString implements TestOnly
{
    public function requireField()
    {
    }
}
