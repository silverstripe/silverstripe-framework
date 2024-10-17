<?php

namespace SilverStripe\View\Tests\CastingServiceTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestDataObject extends DataObject implements TestOnly
{
    private static string $table_name = 'CastingServiceTest_TestDataObject';

    private static array $db = [
        'HtmlField' => 'HTMLText',
        'DateField' => 'Date',
    ];

    private static array $casting = [
        'DateField' => 'Text', // won't override
        'TimeField' => 'Time',
        'ArrayAsText' => 'Text',
    ];

    public function castingHelper(string $field): ?string
    {
        if ($field === 'OverrideCastingHelper') {
            return 'Currency';
        }
        return parent::castingHelper($field);
    }
}
