<?php

namespace SilverStripe\i18n\Tests\i18nTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Group;

class MyObject extends DataObject implements TestOnly
{
    private static $table_name = 'i18nTest_MyObject';

    private static $db = array(
        'FirstProperty' => 'Varchar',
        'SecondProperty' => 'Int'
    );

    private static $has_many = array(
        'Relation' => Group::class
    );

    private static $singular_name = "My Object";

    private static $plural_name = "My Objects";
}
