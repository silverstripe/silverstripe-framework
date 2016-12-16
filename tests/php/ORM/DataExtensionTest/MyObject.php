<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject implements TestOnly
{
    private static $table_name = 'DataExtensionTest_MyObject';

    private static $db = array(
        'Title' => 'Varchar',
    );

    private static $extensions = [
        Extension1::class,
        Extension2::class,
        Faves::class,
        AllMethodNames::class
    ];

    public function canOne($member = null)
    {
        // extended access checks
        $results = $this->extend('canOne', $member);
        if ($results && is_array($results)) {
            if (!min($results)) {
                return false;
            }
        }

        return false;
    }

    public function canTwo($member = null)
    {
        // extended access checks
        $results = $this->extend('canTwo', $member);
        if ($results && is_array($results)) {
            if (!min($results)) {
                return false;
            }
        }

        return true;
    }

    public function canThree($member = null)
    {
        // extended access checks
        $results = $this->extend('canThree', $member);
        if ($results && is_array($results)) {
            if (!min($results)) {
                return false;
            }
        }

        return true;
    }
}
