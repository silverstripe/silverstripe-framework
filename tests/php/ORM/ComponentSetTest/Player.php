<?php

namespace SilverStripe\ORM\Tests\ComponentSetTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Member;

class Player extends Member implements TestOnly
{
    private static $table_name = 'ComponentSetTest_Player';

    private static $belongs_many_many = array(
        'Teams' => Team::class
    );
}
