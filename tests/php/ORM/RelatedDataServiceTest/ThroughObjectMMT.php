<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class ThroughObjectMMT extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ThroughObjectMMT';

    private static $has_one = [
        'HubObj' => Hub::class,
        'MMTObj' => ManyManyThrough::class,
    ];
}
