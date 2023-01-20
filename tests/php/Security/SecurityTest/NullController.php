<?php

namespace SilverStripe\Security\Tests\SecurityTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\TestOnly;

class NullController extends Controller implements TestOnly
{

    public function redirect(string $url, int $code = 302): HTTPResponse
    {
        // NOOP
        return HTTPResponse::create();
    }

    public function Link($action = null)
    {
        return Controller::join_links('SecurityTest_NullController', $action, '/');
    }
}
