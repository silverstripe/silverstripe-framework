<?php

namespace SilverStripe\Admin\Tests\CMSMenuTest;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Dev\TestOnly;

class CustomTitle extends LeftAndMain implements TestOnly
{
    private static $url_segment = 'CMSMenuTest_CustomTitle';

    private static $menu_priority = 50;

    public static function menu_title($class = null, $localised = false)
    {
        if ($localised) {
            return __CLASS__ . ' (localised)';
        } else {
            return __CLASS__ . ' (unlocalised)';
        }
    }
}
