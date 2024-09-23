<?php

namespace SilverStripe\Model\Tests\List\PaginatedListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Member;

class Player extends Member implements TestOnly
{
    private static $table_name = 'PaginatedListTest_Player';

    private static $db = [
        'IsRetired' => 'Boolean',
        'ShirtNumber' => 'Varchar',
    ];

    private static $searchable_fields = [
        'IsRetired',
        'ShirtNumber'
    ];
}
