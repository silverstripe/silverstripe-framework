<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Class Roof
 *
 * @property string $Title
 * @package SilverStripe\ORM\Tests\DataObjectTest
 */
class Roof extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'DataObjectTest_Roof';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(50)',
    ];

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class,
    ];
}
