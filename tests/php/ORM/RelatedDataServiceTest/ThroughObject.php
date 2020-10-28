<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class ThroughObject extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_ThroughObject';

    private static $has_one = [
        'HubObj' => Hub::class,
        'NodeObj' => Node::class,
    ];
}
