<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class Team_Extension extends Extension implements TestOnly
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

    protected function augmentHydrateFields()
    {
        return [
            'CustomHydratedField' => true,
        ];
    }
}
