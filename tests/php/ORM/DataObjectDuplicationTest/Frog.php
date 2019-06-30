<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * @method ManyManyList Children()
 * @method ManyManyList Parents()
 */
class Frog extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Frog';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $belongs_to = [
        'Parent' => Elephant::class,
    ];
}
