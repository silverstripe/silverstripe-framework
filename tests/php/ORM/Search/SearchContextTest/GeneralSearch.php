<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

class GeneralSearch extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_GeneralSearch';

    private static $db = [
        'Name' => 'Varchar',
        'DoNotUseThisField' => 'Varchar',
        'HairColor' => 'Varchar',
        'ExcludeThisField' => 'Varchar',
        'ExactMatchField' => 'Varchar',
        'PartialMatchField' => 'Varchar',
        'MatchAny1' => 'Varchar',
        'MatchAny2' => 'Varchar',
    ];

    private static $has_one = [
        'Customer' => Customer::class,
        'ShippingAddress' => Address::class,
    ];

    private static $searchable_fields = [
        'Name',
        'HairColor',
        'ExcludeThisField' => [
            'general' => false,
        ],
        'ExactMatchField' => [
            'filter' => 'ExactMatchFilter',
        ],
        'PartialMatchField' => [
            'filter' => 'PartialMatchFilter',
        ],
        'MatchAny' => [
            'field' => TextField::class,
            'match_any' => [
                'MatchAny1',
                'MatchAny2',
                'Customer.MatchAny',
            ]
        ]
    ];
}
