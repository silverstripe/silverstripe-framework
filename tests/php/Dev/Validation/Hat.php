<?php

namespace SilverStripe\Dev\Tests\Validation;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * Class Hat
 *
 * @property string $Title
 * @method Member Hatter()
 * @method ManyManyList|Team[] TeamHats()
 */
class Hat extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'RelationValidationTest_Hat';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $belongs_to = [
        'Hatter' => Member::class . '.Hat',
    ];

    /**
     * @var array
     */
    private static $belongs_many_many = [
        'TeamHats' => Team::class . '.ReserveHats',
    ];
}
