<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class HubSub extends Hub
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_HubSub';

    private static $db = [
        'SubTitle' => 'Varchar'
    ];
}
