<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class HasMany extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_HasMany';

    private static $has_one = [
        'Hub' => Hub::class
    ];
}
