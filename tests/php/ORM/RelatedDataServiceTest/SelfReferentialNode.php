<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

class SelfReferentialNode extends Node
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_SelfReferentialNode';

    private static $has_one = [
        'HOA' => Node::class,
        'HOB' => SelfReferentialNode::class
    ];

    private static $many_many = [
        'MMA' => Node::class,
        'MMB' => SelfReferentialNode::class
    ];
}
