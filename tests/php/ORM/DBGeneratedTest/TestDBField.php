<?php

namespace SilverStripe\ORM\Tests\DBGeneratedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\FieldType\DBField;

class TestDBField extends DBField implements TestOnly
{
    public function requireField(): void
    {
        // no-op
    }
}
