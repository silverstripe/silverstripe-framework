<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class AccessBaseController extends Controller implements TestOnly
{
    private static $allowed_actions = array();

    private static $extensions = [
        AccessBaseControllerExtension::class
    ];

    // Denied for all
    public function method1()
    {
    }

    // Denied for all
    public function method2()
    {
    }
}
