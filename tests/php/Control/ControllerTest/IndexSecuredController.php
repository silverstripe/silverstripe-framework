<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Tests\ControllerTest;
use SilverStripe\Dev\TestOnly;

class IndexSecuredController extends ControllerTest\AccessBaseController implements TestOnly
{

    private static $allowed_actions = array(
        "index" => "ADMIN",
    );
}
