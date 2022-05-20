<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

/**
 * @property string $Name
 * @method Deadline Deadline()
 * @method HasManyList Actions()
 */
class Project extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Project';

    private static $db = [
        'Name' => 'Varchar'
    ];

    private static $has_one = [
        'Deadline' => Deadline::class,
    ];

    private static $has_many = [
        'Actions' => Action::class,
    ];

    private static $searchable_fields = [
        'Name' => 'PartialMatchFilter',
        'Actions.SolutionArea' => 'ExactMatchFilter',
        'Actions.Description' => 'PartialMatchFilter'
    ];
}
