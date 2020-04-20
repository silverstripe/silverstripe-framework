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

    private static $url_handlers = [
        // The double-slash is need here to ensure that
        '$Action//$ID/$OtherID' => "handleAction",
    ];

    private static $allowed_actions = [
        'failoverMethod', // part of the failover object
        'blockMethod' => '->provideAccess(false)',
        'allowMethod' => '->provideAccess',
    ];

    private static $extensions = [
        ControllerExtension::class,
        AllowedControllerExtension::class,
    ];

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
