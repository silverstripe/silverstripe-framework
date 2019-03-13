<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class HouseVisit
 *
 * @property int $HouseID
 * @property int $VisitorID
 * @method House House()
 * @method Visitor Visitor()
 * @package SilverStripe\ORM\Tests\DataObjectTest
 */
class HouseVisit extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'DataObjectTest_HouseVisit';

    /**
     * @var array
     */
    private static $has_one = [
        'House' => House::class,
        'Visitor' => Visitor::class,
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class,
    ];
}
