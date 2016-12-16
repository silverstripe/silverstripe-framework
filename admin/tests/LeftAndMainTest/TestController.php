<?php

namespace SilverStripe\Admin\Tests\LeftAndMainTest;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class TestController extends LeftAndMain implements TestOnly
{
    protected $template = 'BlankPage';

    private static $tree_class = TestObject::class;

    public function Link($action = null)
    {
        return Controller::join_links('LeftAndMainTest_Controller', $action, '/');
    }
}
