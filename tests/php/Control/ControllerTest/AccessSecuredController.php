<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Dev\TestOnly;

class AccessSecuredController extends AccessBaseController implements TestOnly
{
    private static $url_segment = 'AccessSecuredController';

    private static $allowed_actions = array(
        "method1", // denied because only defined in parent
        "method2" => true, // granted because its redefined
        "adminonly" => "ADMIN",
        'templateaction' => 'ADMIN'
    );

    public function method2()
    {
    }

    public function adminonly()
    {
    }

    protected function protectedmethod()
    {
    }
}
