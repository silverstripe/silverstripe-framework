<?php

namespace SilverStripe\Control\Tests\DirectorTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
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
        'returnIsSSL',
    );

    public function returnGetValue($request)
    {
        if (isset($_GET['somekey'])) {
            return $_GET['somekey'];
        }
        return null;
    }

    public function returnPostValue($request)
    {
        if (isset($_POST['somekey'])) {
            return $_POST['somekey'];
        }
        return null;
    }

    public function returnRequestValue($request)
    {
        if (isset($_REQUEST['somekey'])) {
            return $_REQUEST['somekey'];
        }
        return null;
    }

    public function returnCookieValue($request)
    {
        if (isset($_COOKIE['somekey'])) {
            return $_COOKIE['somekey'];
        }
        return null;
    }

    public function returnIsSSL()
    {
        return Director::is_https() ? 'yes': 'no';
    }
}
