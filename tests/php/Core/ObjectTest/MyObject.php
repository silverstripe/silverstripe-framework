<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class MyObject extends BaseObject
{
    public $title = 'my object';
    /**
     * @config
     */
    private static $mystaticProperty = "MyObject";
    static $mystaticArray = array('one');
}
