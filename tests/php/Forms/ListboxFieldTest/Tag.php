<?php

namespace SilverStripe\Forms\Tests\ListboxFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Tag extends DataObject implements TestOnly
{
    private static $table_name = 'ListboxFieldTest_Tag';

    private static $belongs_many_many = array(
        'Articles' => Article::class
    );
}
