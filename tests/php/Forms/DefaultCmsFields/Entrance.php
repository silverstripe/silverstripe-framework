<?php

namespace SilverStripe\Forms\Tests\DefaultCmsFields;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class Entrance
 *
 * @property string $Title
 * @method House House()
 * @package SilverStripe\Forms\Tests\DefaultCmsFields
 */
class Entrance extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'TestEntrance';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $belongs_to = [
        'House' => House::class . '.Entrance',
    ];
}
