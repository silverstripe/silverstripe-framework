<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldSortableHeaderTest;

use SilverStripe\Dev\TestOnly;

/**
 * Should have access to same properties as cheerleader
 */
class Mom extends Cheerleader implements TestOnly
{

    private static $table_name = 'GridFieldSortableHeaderTest_Mom';

    private static $db = array(
        'NumberOfCookiesBaked' => 'Int'
    );
}
