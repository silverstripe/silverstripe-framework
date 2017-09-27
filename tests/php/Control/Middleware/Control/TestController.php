<?php

namespace SilverStripe\Control\Tests\Middleware\Control;

use SilverStripe\Control\Controller;

class TestController extends Controller
{
    public function index($request)
    {
        return "Success";
    }

    public function Link($action = null)
    {
        return Controller::join_links('TestController', $action);
    }
}
