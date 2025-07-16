<?php

namespace SilverStripe\ORM\Tests\DataObjectSchemaTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class AdditionalFieldsExtension extends Extension implements TestOnly
{
    private static array $db = [
        'SomeField' => 'Boolean',
    ];
}
