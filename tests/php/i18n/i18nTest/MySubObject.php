<?php

namespace SilverStripe\i18n\Tests\i18nTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Group;

class MySubObject extends MyObject implements TestOnly
{
    private static $table_name = 'i18nTest_MySubObject';

    private static $db = array(
        'SubProperty' => 'Varchar',
    );

    private static $has_many = array(
        'SubRelation' => Group::class
    );

    private static $singular_name = "My Sub Object";

    private static $plural_name = "My Sub Objects";
}
