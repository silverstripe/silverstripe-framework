<?php

namespace SilverStripe\Dev\Tests\Validation;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * Class Member
 *
 * @property string $Title
 * @method Team HomeTeam()
 * @method Hat Hat()
 * @method HasManyList|Freelancer[] TemporaryMembers()
 * @method ManyManyThroughList|Member[] FreelancerTeams()
 */
class Member extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'RelationValidationTest_Member';

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
        'HomeTeam' => Team::class,
        'Hat' => Hat::class,
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'TemporaryMembers' => Freelancer::class . '.TemporaryMember',
        'ManyTeams' => Team::class . '.SingleMember',
        'ManyMoreTeams' => Team::class . '.SingleMember',
    ];

    /**
     * @var array
     */
    private static $belongs_many_many = [
        'FreelancerTeams' => Team::class . '.Freelancers',
    ];
}
