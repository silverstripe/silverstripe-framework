<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\TestOnly;

class HTTPMethodTestController extends Controller implements TestOnly
{
    private static $url_segment = 'HTTPMethodTestController';

    private static $url_handlers = [
        'GET /' => 'getRoot',
        'POST ' => 'postLegacyRoot',
    ];

    private static $allowed_actions = [
        'getRoot',
        'postLegacyRoot',
    ];

    public function getRoot(HTTPRequest $request)
    {
        return "Routed to getRoot";
    }

    public function postLegacyRoot(HTTPRequest $request)
    {
        return "Routed to postLegacyRoot";
    }
}
