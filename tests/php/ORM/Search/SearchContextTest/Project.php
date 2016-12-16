<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Project extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Project';

    private static $db = array(
        'Name' => 'Varchar'
    );

    private static $has_one = array(
        'Deadline' => Deadline::class,
    );

    private static $has_many = array(
        'Actions' => Action::class,
    );

    private static $searchable_fields = array(
        'Name' => 'PartialMatchFilter',
        'Actions.SolutionArea' => 'ExactMatchFilter',
        'Actions.Description' => 'PartialMatchFilter'
    );
}
