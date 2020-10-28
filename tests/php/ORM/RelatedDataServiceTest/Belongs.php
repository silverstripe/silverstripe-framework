<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class Belongs extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_Belongs';

    private static $belongs_many_many = [
        'Hubs' => Hub::class
    ];
}
