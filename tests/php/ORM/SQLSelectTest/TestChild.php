<?php

namespace SilverStripe\ORM\Tests\SQLSelectTest;

class TestChild extends TestBase
{
    private static $table_name = 'SQLSelectTestChild';

    private static $db = array(
        "Name" => "Varchar",
    );
}
