<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DatabaseAdmin;

class DevBuildController extends Controller
{

    private static $url_handlers = [
        '' => 'build'
    ];

    private static $allowed_actions = [
        'build'
    ];

    public function build(HTTPRequest $request): HTTPResponse
    {
        if (Director::is_cli()) {
            $da = DatabaseAdmin::create();
            return $da->handleRequest($request);
        } else {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("Environment Builder", Director::absoluteBaseURL());
            echo "<div class=\"build\">";

            $da = DatabaseAdmin::create();
            $response = $da->handleRequest($request);

            echo "</div>";
            echo $renderer->renderFooter();

            return $response;
        }
    }
}
