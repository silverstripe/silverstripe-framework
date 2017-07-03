<?php

namespace SilverStripe\Security\Tests\SecurityTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * @skipUpgrade
 */
class SecuredController extends Controller implements TestOnly
{
    private static $allowed_actions = array('index');

    public function index()
    {
        if (!Permission::check('ADMIN')) {
            return Security::permissionFailure($this);
        }

        return 'Success';
    }

    public function Link($action = null)
    {
        return Controller::join_links('SecurityTest_SecuredController', $action, '/');
    }
}
