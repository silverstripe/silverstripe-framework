<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

// No belong_many_many on RelatedDataServiceTest_Hub
class ManyManyNoBelongs extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ManyManyNoBelongs';

    private static $many_many = [
        'Hubs' => Hub::class
    ];
}
