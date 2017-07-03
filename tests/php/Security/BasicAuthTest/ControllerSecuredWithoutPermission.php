<?php

namespace SilverStripe\Security\Tests\BasicAuthTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\BasicAuth;

/**
 * @skipUpgrade
 */
class ControllerSecuredWithoutPermission extends Controller implements TestOnly
{

    protected $template = 'BlankPage';

    protected function init()
    {
        BasicAuth::protect_entire_site(true, null);
        parent::init();
    }

    public function Link($action = null)
    {
        return Controller::join_links('BasicAuthTest_ControllerSecuredWithoutPermission', $action, '/');
    }
}
