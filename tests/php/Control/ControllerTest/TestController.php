<?php

namespace SilverStripe\Control\Tests\ControllerTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * Simple controller for testing
 */
class TestController extends Controller implements TestOnly
{
    public function __construct()
    {
        parent::__construct();
        if (Controller::has_curr()) {
            $this->setRequest(Controller::curr()->getRequest());
        }
    }

    private static $url_segment = 'TestController';

    public $Content = "default content";

    private static $allowed_actions = array(
        'methodaction',
        'stringaction',
        'redirectbacktest',
        'templateaction'
    );

    public function methodaction()
    {
        return array(
            "Content" => "methodaction content"
        );
    }

    public function stringaction()
    {
        return "stringaction was called.";
    }

    public function redirectbacktest()
    {
        return $this->redirectBack();
    }
}
