<?php

namespace SilverStripe\ORM\Tests\SQLUpdateTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestBase extends DataObject implements TestOnly
{
    private static $table_name = 'SQLUpdateTestBase';

    private static $db = array(
        'Title' => 'Varchar(255)',
        'Description' => 'Text'
    );
}
