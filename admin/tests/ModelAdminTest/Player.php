<?php

namespace SilverStripe\Admin\Tests\ModelAdminTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Player extends DataObject implements TestOnly
{
    private static $table_name = 'ModelAdminTest_Player';
    private static $db = array(
        'Name' => 'Varchar',
        'Position' => 'Varchar',
    );
    private static $has_one = array(
        'Contact' => Contact::class
    );
}
