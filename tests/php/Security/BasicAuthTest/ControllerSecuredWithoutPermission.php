<?php

namespace SilverStripe\Security\Tests\BasicAuthTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * @skipUpgrade
 */
class ControllerSecuredWithoutPermission extends Controller implements TestOnly
{
    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links('BasicAuthTest_ControllerSecuredWithoutPermission', $action, '/');
    }
}
