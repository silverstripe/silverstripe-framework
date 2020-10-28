<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

// The "Hub" acts similar to how a Page would normally behave
// Though it's been kept as a DataObject to keep things more abstract
// All the other 'RelatedDataServiceTest_*' classes represent Files (excluding _ExtText_*)
use SilverStripe\Assets\Shortcodes\FileLink;
use SilverStripe\ORM\DataObject;

class Hub extends Base
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_Hub';

    private static $has_one = [
        'HO' => Node::class,
        'Parent' => DataObject::class, // Will create a ParentID column + ParentColumn Enum column
    ];

    private static $has_many = [
        'HM' => HasMany::class
    ];

    private static $many_many = [
        // has belongs_many_many on the other end
        'MMtoBMM' => Belongs::class,
        // does not have belong_many_many on the other end
        'MMtoNoBMM' => Node::class,
        // manyManyThrough
        'MMT' => [
            'through' => ThroughObject::class,
            'from' => 'HubObj',
            'to' => 'NodeObj',
        ],
        // manyManyThrough Polymorphic
        'MMTP' => [
            'through' => ThroughObjectPolymorphic::class,
            'from' => 'Parent',
            'to' => 'NodeObj',
        ]
    ];

    private static $belongs_many_many = [
        // has many_many on the other end
        'BMMtoMM' => ManyMany::class,
        'BMMtoMMT' => ManyManyThrough::class
        // Not testing the following, will throw this Silverstripe error:
        // belongs_many_many relation ... points to ... without matching many_many
        // does not have many_many on the other end
        // 'BMMtoNoMM' => Node::class
    ];
}
