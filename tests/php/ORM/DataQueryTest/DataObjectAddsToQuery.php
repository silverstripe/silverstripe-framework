<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class DataObjectAddsToQuery extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_AddsToQuery';

    private static $db = [
        'FieldOne' => 'Text',
        'FieldTwo' => DBFieldAddsToQuery::class,
    ];
}
