<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DatabaseAdmin;

class DevBuildController extends Controller
{

    private static $url_handlers = array(
        '' => 'build'
    );

    private static $allowed_actions = array(
        'build'
    );

    public function build($request)
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
