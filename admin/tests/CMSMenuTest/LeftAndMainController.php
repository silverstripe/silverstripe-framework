<?php

namespace SilverStripe\Admin\Tests\CMSMenuTest;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Dev\TestOnly;

class LeftAndMainController extends LeftAndMain implements TestOnly
{
    private static $url_segment = 'CMSMenuTest_LeftAndMainController';

    private static $menu_title = 'CMSMenuTest_LeftAndMainController';

    private static $menu_priority = 50;
}
