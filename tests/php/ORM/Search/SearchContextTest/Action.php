<?php

namespace SilverStripe\ORM\Tests\Search\SearchContextTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Action extends DataObject implements TestOnly
{
    private static $table_name = 'SearchContextTest_Action';

    private static $db = array(
        'Description' => 'Text',
        'SolutionArea' => 'Varchar'
    );

    private static $has_one = array(
        'Project' => Project::class,
    );
}
