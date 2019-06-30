<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class Locale extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyThroughTest_Locale';

    /**
     * @config
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(100)',
        'Locale' => 'Varchar(10)',
        'URLSegment' => 'Varchar(100)',
        'IsGlobalDefault' => 'Boolean',
    ];

    private static $has_many = [
        'FallbackLocales' => FallbackLocale::class . '.Parent',
    ];

    private static $many_many = [
        'Fallbacks' => [
            'through' => FallbackLocale::class,
            'from' => 'Parent',
            'to' => 'Locale',
        ],
    ];

    private static $default_sort = '"ManyManyThroughTest_Locale"."Locale" ASC';
}
