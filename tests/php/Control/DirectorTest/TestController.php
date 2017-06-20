<?php

namespace SilverStripe\Control\Tests\DirectorTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

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

    private static $allowed_actions = array(
        'returnGetValue',
        'returnPostValue',
        'returnRequestValue',
        'returnCookieValue',
    );

    public function returnGetValue($request)
    {
        return $_GET['somekey'];
    }

    public function returnPostValue($request)
    {
        return $_POST['somekey'];
    }

    public function returnRequestValue($request)
    {
        return $_REQUEST['somekey'];
    }

    public function returnCookieValue($request)
    {
        return $_COOKIE['somekey'];
    }
}
