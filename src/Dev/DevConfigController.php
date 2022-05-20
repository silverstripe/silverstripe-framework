<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use Symfony\Component\Yaml\Yaml;
use SilverStripe\Core\Injector\Injector;

/**
 * Outputs the full configuration.
 */
class DevConfigController extends Controller
{

    /**
     * @var array
     */
    private static $url_handlers = [
        'audit' => 'audit',
        '' => 'index'
    ];

    /**
     * @var array
     */
    private static $allowed_actions = [
        'index',
        'audit',
    ];

    /**
     * Note: config() method is already defined, so let's just use index()
     *
     * @return string|HTTPResponse
     */
    public function index()
    {
        $body = '';
        $subtitle = "Config manifest";

        if (Director::is_cli()) {
            $body .= sprintf("\n%s\n\n", strtoupper($subtitle ?? ''));
            $body .= Yaml::dump(Config::inst()->getAll(), 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        } else {
            $renderer = DebugView::create();
            $body .= $renderer->renderHeader();
            $body .= $renderer->renderInfo("Configuration", Director::absoluteBaseURL());
            $body .= "<div class=\"options\">";
            $body .= sprintf("<h2>%s</h2>", $subtitle);
            $body .= "<pre>";
            $body .= Yaml::dump(Config::inst()->getAll(), 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
            $body .= "</pre>";
            $body .= "</div>";
            $body .= $renderer->renderFooter();
        }

        return $this->getResponse()->setBody($body);
    }

    /**
     * Output the extraneous config properties which are defined in .yaml but not in a corresponding class
     *
     * @return string|HTTPResponse
     */
    public function audit()
    {
        $body = '';
        $missing = [];
        $subtitle = "Missing Config property definitions";

        foreach ($this->array_keys_recursive(Config::inst()->getAll(), 2) as $className => $props) {
            $props = array_keys($props ?? []);

            if (!count($props ?? [])) {
                // We can skip this entry
                continue;
            }

            if ($className == strtolower(Injector::class)) {
                // We don't want to check the injector config
                continue;
            }

            foreach ($props as $prop) {
                $defined = false;
                // Check ancestry (private properties don't inherit natively)
                foreach (ClassInfo::ancestry($className) as $cn) {
                    if (property_exists($cn, $prop ?? '')) {
                        $defined = true;
                        break;
                    }
                }

                if ($defined) {
                    // No need to record this property
                    continue;
                }

                $missing[] = sprintf("%s::$%s\n", $className, $prop);
            }
        }

        $output = count($missing ?? [])
            ? implode("\n", $missing)
            : "All configured properties are defined\n";

        if (Director::is_cli()) {
            $body .= sprintf("\n%s\n\n", strtoupper($subtitle ?? ''));
            $body .= $output;
        } else {
            $renderer = DebugView::create();
            $body .= $renderer->renderHeader();
            $body .= $renderer->renderInfo(
                "Configuration",
                Director::absoluteBaseURL(),
                "Config properties that are not defined (or inherited) by their respective classes"
            );
            $body .= "<div class=\"options\">";
            $body .= sprintf("<h2>%s</h2>", $subtitle);
            $body .= sprintf("<pre>%s</pre>", $output);
            $body .= "</div>";
            $body .= $renderer->renderFooter();
        }

        return $this->getResponse()->setBody($body);
    }

    /**
     * Returns all the keys of a multi-dimensional array while maintining any nested structure
     *
     * @param array $array
     * @param int $maxdepth
     * @param int $depth
     * @param array $arrayKeys
     * @return array
     */
    private function array_keys_recursive($array, $maxdepth = 20, $depth = 0, $arrayKeys = [])
    {
        if ($depth < $maxdepth) {
            $depth++;
            $keys = array_keys($array ?? []);

            foreach ($keys as $key) {
                if (!is_array($array[$key])) {
                    continue;
                }

                $arrayKeys[$key] = $this->array_keys_recursive($array[$key], $maxdepth, $depth);
            }
        }

        return $arrayKeys;
    }
}
