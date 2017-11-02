<?php

namespace SilverStripe\Security\Tests\BasicAuthTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\BasicAuth;

/**
 * @skipUpgrade
 */
class ControllerSecuredWithPermission extends Controller implements TestOnly
{
    public static $post_init_called = false;

    public static $index_called = false;

    protected $template = 'BlankPage';

    protected function init()
    {
        self::$post_init_called = false;
        self::$index_called = false;

        BasicAuth::protect_entire_site(true, 'MYCODE');
        parent::init();

        self::$post_init_called = true;
    }

    public function index()
    {
        self::$index_called = true;
        return "index";
    }

    public function Link($action = null)
    {
        return Controller::join_links('BasicAuthTest_ControllerSecuredWithPermission', $action, '/');
    }
}
