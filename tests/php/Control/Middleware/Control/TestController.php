<?php

namespace SilverStripe\Control\Tests\Middleware\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;

class TestController extends Controller
{
    public function index(HTTPRequest $request): HTTPResponse
    {
        return HTTPResponse::create()->setBody("Success");
    }

    public function Link($action = null)
    {
        return Controller::join_links('TestController', $action);
    }
}
