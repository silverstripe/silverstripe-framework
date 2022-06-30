<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

class Order extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Order';

    private static $db = [
        'Name' => 'Varchar',
    ];

    private static $has_one = [
        'Customer' => Customer::class,
        'ShippingAddress' => Address::class,
    ];

    private static $searchable_fields = [
        'CustomFirstName' => [
            'title' => 'First Name',
            'field' => TextField::class,
            'match_any' => [
                // Searching with the "First Name" field will show Orders matching either Name, Customer.FirstName, or ShippingAddress.FirstName
                'Name',
                'Customer.FirstName',
                'ShippingAddress.FirstName',
            ],
        ],
        'PartialMatchField' => [
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
            'match_any' => [
                'Name',
                'Customer.FirstName',
                'ShippingAddress.FirstName',
            ],
        ],
        'ExactMatchField' => [
            'field' => TextField::class,
            'filter' => 'ExactMatchFilter',
            'match_any' => [
                'Name',
                'Customer.FirstName',
                'ShippingAddress.FirstName',
            ],
        ],
    ];
}
