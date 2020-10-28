<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class ThroughObjectMMTNB extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ThroughObjectMMTNB';

    private static $has_one = [
        'HubObj' => Hub::class,
        'MMTNBObj' => ManyManyThroughNoBelongs::class,
    ];
}
