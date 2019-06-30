<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DBClassNameTest;

use SilverStripe\Dev\TestOnly;

class CustomDefaultSubclass extends CustomDefault implements TestOnly
{
    private static $table_name = 'DBClassNameTest_CustomDefaultSubclass';

    private static $db = array(
        'Content' => 'HTMLText'
    );
}
