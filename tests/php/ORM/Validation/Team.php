<?php

namespace SilverStripe\ORM\Tests\Validation;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * Class Team
 *
 * @property string $Title
 * @method HasManyList|Member[] Members()
 * @method HasManyList|Freelancer[] FreelancerMembers()
 * @method ManyManyThroughList|Member[] Freelancers()
 * @method ManyManyList|Hat[] ReserveHats()
 */
class Team extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'RelationValidationTest_Team';

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'Members' => Member::class . '.HomeTeam',
        'FreelancerMembers' => Freelancer::class . '.TemporaryTeam',
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'ReserveHats' => Hat::class,
        'Freelancers' => [
            'through' => Freelancer::class,
            'from' => 'TemporaryTeam',
            'to' => 'TemporaryMember',
        ],
    ];
}
