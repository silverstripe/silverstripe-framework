<?php

namespace SilverStripe\Security\Tests\BasicAuthTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * @skipUpgrade
 */
class ControllerSecuredWithPermission extends Controller implements TestOnly
{
    protected $template = 'BlankPage';

    public function index()
    {
        return "index";
    }

    public function Link($action = null)
    {
        return Controller::join_links('BasicAuthTest_ControllerSecuredWithPermission', $action, '/');
    }
}
