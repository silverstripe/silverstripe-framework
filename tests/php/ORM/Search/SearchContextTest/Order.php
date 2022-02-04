<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;

class Order extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Order';

    private static $has_one = [
        'Customer' => Customer::class,
        'ShippingAddress' => Address::class,
    ];

    private static $searchable_fields = [
        'CustomFirstName' => [
            'title' => 'First Name',
            'field' => TextField::class,
            'filter' => 'PartialMatchFilter',
            'match_any' => [
                // Searching with "First Name" will show Orders with matching Customer or Address names
                'Customer.FirstName',
                'ShippingAddress.FirstName',
            ]
        ]
    ];
}