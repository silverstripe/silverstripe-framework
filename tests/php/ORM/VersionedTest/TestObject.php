<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @method TestObject Parent()
 * @method HasManyList Children()
 * @method ManyManyList Related()
 * @mixin Versioned
 */
class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'VersionedTest_DataObject';

    private static $db = array(
        "Name" => "Varchar",
        'Title' => 'Varchar',
        'Content' => 'HTMLText',
    );

    private static $extensions = array(
        Versioned::class,
    );

    private static $has_one = array(
        'Parent' => TestObject::class,
    );

    private static $has_many = array(
        'Children' => TestObject::class,
    );

    private static $many_many = array(
        'Related' => RelatedWithoutversion::class,
    );


    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return true;
    }
}
