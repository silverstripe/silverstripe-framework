<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class UnsecuredController extends Controller implements TestOnly
{
    private static $url_segment = 'UnsecuredController';

    // Not defined, allow access to all
    // static $allowed_actions = array();

    // Granted for all
    public function method1()
    {
    }

    // Granted for all
    public function method2()
    {
    }
}
