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

        $this->pushCurrent();
        $session = new Session(isset($_SESSION) ? $_SESSION : array());
        $request = new HTTPRequest('GET', '/');
        $request->setSession($session);
        $this->setRequest($request);
        $this->setResponse(new HTTPResponse());

        $this->doInit();
    }
}
