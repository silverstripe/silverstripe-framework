<?php

namespace SilverStripe\Dev\Tests\DevAdminControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class ControllerWithPermissions extends Controller implements PermissionProvider
{

    public const OK_MSG = 'DevAdminControllerTest_ControllerWithPermissions TEST OK';

    private static $url_handlers = [
        '' => 'index',
    ];

    private static $allowed_actions = [
        'index',
    ];


    public function index()
    {
        echo ControllerWithPermissions::OK_MSG;
    }

    public function canInit()
    {
        return Permission::check('DEV_ADMIN_TEST_PERMISSION');
    }

    public function providePermissions()
    {
        return [
            'DEV_ADMIN_TEST_PERMISSION' => 'Dev admin test permission',
        ];
    }
}
