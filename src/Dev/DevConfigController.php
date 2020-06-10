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
        if (Director::is_cli()) {
            echo "\nCONFIG MANIFEST\n\n";
            echo Yaml::dump(Config::inst()->getAll(), 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        } else {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("Configuration", Director::absoluteBaseURL());
            echo "<div class=\"options\">";
            echo "<h2>Config manifest</h2>";
            echo "<pre>";
            echo Yaml::dump(Config::inst()->getAll(), 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
            echo "</pre>";
            echo "</div>";
            echo $renderer->renderFooter();
        }

        return $this->getResponse();
    }
}
