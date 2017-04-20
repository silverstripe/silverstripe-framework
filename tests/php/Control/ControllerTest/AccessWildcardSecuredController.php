<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Dev\TestOnly;

class AccessWildcardSecuredController extends AccessBaseController implements TestOnly
{
    private static $url_segment = 'AccessWildcardSecuredController';

    private static $allowed_actions = array(
        "*" => "ADMIN", // should throw exception
    );
}
