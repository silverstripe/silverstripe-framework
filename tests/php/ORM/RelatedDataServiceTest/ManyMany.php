<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class ManyMany extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ManyMany';

    private static $many_many = [
        'Hubs' => Hub::class
    ];
}
