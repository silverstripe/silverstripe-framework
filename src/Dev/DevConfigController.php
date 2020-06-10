<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DevConfigController
 *
 * @package SilverStripe\Dev
 */
class DevConfigController extends Controller
{

    /**
     * @var array
     */
    private static $url_handlers = [
        '' => 'index'
    ];

    /**
     * @var array
     */
    private static $allowed_actions = [
        'index'
    ];

    /**
     * Note: config() method is already defined, so let's just use index()
     *
     * @return string|HTTPResponse
     */
    public function index()
    {
        $body = '';

        if (Director::is_cli()) {
            $body .= "\nCONFIG MANIFEST\n\n";
            $body .= Yaml::dump(Config::inst()->getAll(), 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        } else {
            $renderer = DebugView::create();
            $body .= $renderer->renderHeader();
            $body .= $renderer->renderInfo("Configuration", Director::absoluteBaseURL());
            $body .= "<div class=\"options\">";
            $body .= "<h2>Config manifest</h2>";
            $body .= "<pre>";
            $body .= Yaml::dump(Config::inst()->getAll(), 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
            $body .= "</pre>";
            $body .= "</div>";
            $body .= $renderer->renderFooter();
        }

        return $this->getResponse()->setBody($body);
    }
}
