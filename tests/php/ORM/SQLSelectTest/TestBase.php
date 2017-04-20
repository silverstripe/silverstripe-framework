<?php

namespace SilverStripe\ORM\Tests\SQLSelectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestBase extends DataObject implements TestOnly
{
    private static $table_name = 'SQLSelectTestBase';

    private static $db = array(
        "Title" => "Varchar",
    );
}
