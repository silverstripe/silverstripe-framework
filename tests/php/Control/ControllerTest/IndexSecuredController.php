<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Dev\TestOnly;

class IndexSecuredController extends AccessBaseController implements TestOnly
{
    private static $url_segment = 'IndexSecuredController';

    private static $allowed_actions = array(
        "index" => "ADMIN",
    );
}
