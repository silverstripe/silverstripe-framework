<?php

namespace SilverStripe\Forms\Tests\CheckboxSetFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Article extends DataObject implements TestOnly
{
    private static $table_name = 'CheckboxSetFieldTest_Article';

    private static $db = array(
        "Content" => "Text",
    );

    private static $many_many = array(
        "Tags" => Tag::class,
    );
}
