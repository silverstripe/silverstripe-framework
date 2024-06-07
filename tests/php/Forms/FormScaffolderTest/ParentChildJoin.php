<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ParentChildJoin extends DataObject implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_ParentChildJoin';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Parent' => ParentModel::class,
        'Child' => Child::class,
    ];
}
