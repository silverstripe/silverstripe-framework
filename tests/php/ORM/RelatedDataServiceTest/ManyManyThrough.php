<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class ManyManyThrough extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ManyManyThrough';

    private static $many_many = [
        'Hubs' => [
            'through' => ThroughObjectMMT::class,
            'from' => 'MMTObj',
            'to' => 'HubObj',
        ],
    ];
}
