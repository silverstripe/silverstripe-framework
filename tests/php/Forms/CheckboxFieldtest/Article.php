<?php

namespace SilverStripe\Forms\Tests\CheckboxFieldtest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Article extends DataObject implements TestOnly
{
    private static $table_name = 'CheckboxFieldTest_Article';

    private static $db = array(
        'IsChecked' => 'Boolean'
    );
}
