<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Object;

class MyObject extends Object
{
    public $title = 'my object';
    /**
 * @config
*/
    private static $mystaticProperty = "MyObject";
    static $mystaticArray = array('one');
}
