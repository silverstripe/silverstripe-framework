<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class ContainerController extends Controller implements TestOnly
{
    private static $url_segment = 'ContainerController';

    private static $allowed_actions = array(
        'subcontroller',
    );

    public function subcontroller()
    {
        return new SubController();
    }
}
