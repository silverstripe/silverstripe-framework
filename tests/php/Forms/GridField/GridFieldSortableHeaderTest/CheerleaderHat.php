<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CheerleaderHat extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldSortableHeaderTest_CheerleaderHat';

    private static $db = array(
        'Colour' => 'Varchar'
    );

    private static $has_one = array(
        'Cheerleader' => Cheerleader::class
    );
}
