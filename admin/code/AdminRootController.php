<?php

namespace SilverStripe\Admin;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataModel;
use SilverStripe\View\TemplateGlobalProvider;

class AdminRootController extends Controller implements TemplateGlobalProvider
{

    /**
     * Fallback admin URL in case this cannot be infered from Director.rules
     *
     * @var string
     * @config
     */
    private static $url_base = 'admin';

    /**
     * Convenience function to return the admin route config.
     * Looks for the {@link Director::$rules} for the current admin Controller.
     *
     * @return string
     */
    public static function get_admin_route()
    {
        $rules = Director::config()->rules;
        $adminRoute = array_search(__CLASS__, $rules);
        return $adminRoute ?: static::config()->url_base;
    }

    /**
     * Returns the root admin URL for the site with trailing slash
     *
     * @return string
     */
    public static function admin_url()
    {
        return self::get_admin_route() . '/';
    }

    /**
     * @var string
     * @config
     * The LeftAndMain child that will be used as the initial panel to display if none is selected (i.e. if you
     * visit /admin)
     */
    private static $default_panel = SecurityAdmin::class;

    /**
     * @var array
     * Holds an array of url_pattern => controller k/v pairs, the same as Director::rules. However this is built
     * dynamically from introspecting on all the classes that derive from LeftAndMain.
     *
     * Don't access this directly - always access via the rules() accessor below, which will build this array
     * the first time it's accessed
     */
    private static $_rules = null;

    /**
     * Gets a list of url_pattern => controller k/v pairs for each LeftAndMain derived controller
     */
    public static function rules()
    {
        if (self::$_rules === null) {
            self::$_rules = array();

            // Map over the array calling add_rule_for_controller on each
            $classes = CMSMenu::get_cms_classes(null, true, CMSMenu::URL_PRIORITY);
            array_map(array(__CLASS__, 'add_rule_for_controller'), $classes);
        }
        return self::$_rules;
    }

    /**
     * Add the appropriate k/v pair to self::$rules for the given controller.
     *
     * @param string $controllerClass Name of class
     */
    protected static function add_rule_for_controller($controllerClass)
    {
        $config = Config::forClass($controllerClass);
        $urlSegment = $config->get('url_segment');
        $urlRule    = $config->get('url_rule');

        if ($urlSegment) {
            // Make director rule
            if ($urlRule[0] == '/') {
                $urlRule = substr($urlRule, 1);
            }
            $rule = $urlSegment . '//' . $urlRule;

            // ensure that the first call to add_rule_for_controller for a rule takes precedence
            if (!isset(self::$_rules[$rule])) {
                self::$_rules[$rule] = $controllerClass;
            }
        }
    }

    public function handleRequest(HTTPRequest $request, DataModel $model)
    {
        // If this is the final portion of the request (i.e. the URL is just /admin), direct to the default panel
        if ($request->allParsed()) {
            $segment = Config::forClass($this->config()->default_panel)
                ->get('url_segment');

            $this->redirect(Controller::join_links(self::admin_url(), $segment, '/'));
            return $this->getResponse();
        } // Otherwise
        else {
            $rules = self::rules();
            foreach ($rules as $pattern => $controller) {
                if (($arguments = $request->match($pattern, true)) !== false) {
                    /** @var LeftAndMain $controllerObj */
                    $controllerObj = Injector::inst()->create($controller);
                    $controllerObj->setSession($this->session);

                    return $controllerObj->handleRequest($request, $model);
                }
            }
        }

        return $this->httpError(404, 'Not found');
    }

    /**
     * @return array Returns an array of strings of the method names of methods on the call that should be exposed
     * as global variables in the templates.
     */
    public static function get_template_global_variables()
    {
        return array(
            'adminURL' => 'admin_url'
        );
    }
}
