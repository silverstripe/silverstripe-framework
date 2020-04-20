<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class Team_Extension extends DataExtension implements TestOnly
{
    private static $summary_fields = [
        'Title' => 'Custom Title', // override non-associative 'Title'
    ];

    private static $db = [
        'ExtendedDatabaseField' => 'Varchar'
    ];

    private static $has_one = [
        'ExtendedHasOneRelationship' => Player::class
    ];

    public function getExtendedDynamicField()
    {
        return "extended dynamic field";
    }
}
