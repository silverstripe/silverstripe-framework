<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @property string $Title
 * @method Bracket Parent()
 * @method Team First()
 * @method Team Second()
 * @method Team Winner()
 */
class Bracket extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectTest_Bracket';

    private static $db = [
        'Title' => 'Varchar(100)',
    ];

    private static $has_one = [
        'Next' => Bracket::class,
        'First' => Team::class,
        'Second' => Team::class,
        'Winner' => Team::class,
    ];

    private static $has_many = [
        'Previous' => Bracket::class,
    ];
}
