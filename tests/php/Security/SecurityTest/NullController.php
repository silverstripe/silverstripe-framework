<?php

namespace SilverStripe\Security\Tests\SecurityTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * @skipUpgrade
 */
class NullController extends Controller implements TestOnly
{

    public function redirect($url, $code = 302)
    {
        // NOOP
    }

    public function Link($action = null)
    {
        return Controller::join_links('SecurityTest_NullController', $action, '/');
    }
}
