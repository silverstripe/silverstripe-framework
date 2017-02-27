<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Controller;

// Fake a current controller. Way harder than it should be
class FakeController extends Controller
{

    public function __construct()
    {
        parent::__construct();

        $session = Injector::inst()->create(Session::class, isset($_SESSION) ? $_SESSION : array());
        $this->setSession($session);

        $this->pushCurrent();

        $request = new HTTPRequest('GET', '/');
        $this->setRequest($request);

        $this->setResponse(new HTTPResponse());

        $this->doInit();
    }
}
