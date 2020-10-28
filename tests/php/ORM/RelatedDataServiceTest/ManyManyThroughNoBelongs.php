<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class ManyManyThroughNoBelongs extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ManyManyThroughNoBelongs';

    private static $many_many = [
        'Hubs' => [
            'through' => ThroughObjectMMTNB::class,
            // note: you cannot swap from/to around, Silverstripe MMT expects 'from' to be
            // the type of class that defines the many_many_through relationship
            // i.e. this class
            'from' => 'MMTNBObj',
            'to' => 'HubObj',
        ],
    ];
}
