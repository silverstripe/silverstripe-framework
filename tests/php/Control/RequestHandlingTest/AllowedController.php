<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * Controller for the test
 */
class AllowedController extends Controller implements TestOnly
{
    private static $url_segment = 'AllowedController';

    private static $url_handlers = array(
        // The double-slash is need here to ensure that
        '$Action//$ID/$OtherID' => "handleAction",
    );

    private static $allowed_actions = array(
        'failoverMethod', // part of the failover object
        'blockMethod' => '->provideAccess(false)',
        'allowMethod' => '->provideAccess',
    );

    private static $extensions = array(
        ControllerExtension::class,
        AllowedControllerExtension::class,
    );

    public function __construct()
    {
        $this->failover = new ControllerFailover();
        parent::__construct();
    }

    public function index($request)
    {
        return "This is the controller";
    }

    function provideAccess($access = true)
    {
        return $access;
    }

    function blockMethod($request)
    {
        return 'blockMethod';
    }

    function allowMethod($request)
    {
        return 'allowMethod';
    }
}
