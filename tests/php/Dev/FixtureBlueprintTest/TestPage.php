<?php

namespace SilverStripe\Dev\Tests\FixtureBlueprintTest;

class TestPage extends TestSiteTree
{
    private static $table_name = 'FixtureBlueprintTest_TestPage';

    private static $db = array(
        'PublishDate' => 'Datetime'
    );
}
