<?php

namespace SilverStripe\Dev\Tests\Validation;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Class Freelancer
 *
 * @property string $Title
 * @method Team TemporaryTeam()
 * @method Member TemporaryMember()
 */
class Freelancer extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'RelationValidationTest_Freelancer';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'TemporaryTeam' => Team::class,
        'TemporaryMember' => Member::class,
    ];
}
