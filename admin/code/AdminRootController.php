<?php

/**
 * @package framework
 * @subpackage admin
 */
class AdminRootController extends Controller implements TemplateGlobalProvider {

	/**
	 * Convenience function to return the admin route config.
	 * Looks for the {@link Director::$rules} for the current admin Controller.
	 */
	public static function get_admin_route() {
		if (Controller::has_curr()) {
			$routeParams = Controller::curr()->getRequest()->routeParams();
			$adminControllerClass = isset($routeParams['Controller']) ? $routeParams['Controller'] : get_called_class();
		}
		else {
			$adminControllerClass = get_called_class();
		}

		$rules = Config::inst()->get('Director', 'rules');
		$adminRoute = array_search($adminControllerClass, $rules);
		return $adminRoute ? $adminRoute : '';
	}

	/**
	 * Returns the root admin URL for the site with trailing slash
	 *
	 * @return string
	 * @uses get_admin_route()
	 */
	public static function admin_url() {
		return self::get_admin_route() . '/';
	}

	/**
	 * Includes the adminURL JavaScript config in the ss namespace
	 */
	public static function include_js() {
		$js = "(function(root) {
			root.ss = root.ss || {};
			root.ss.config = root.ss.config || {};
			root.ss.config.adminURL = '".self::admin_url()."'
		}(window));";
		Requirements::customScript($js, 'adminURLConfig');
	}

	/**
	 * @var string
	 * @config
	 * The LeftAndMain child that will be used as the initial panel to display if none is selected (i.e. if you
	 * visit /admin)
	 */
	private static $default_panel = 'SecurityAdmin';

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
	public static function rules() {
		if (self::$_rules === null) {
			self::$_rules = array();

			// Build an array of class => url_priority k/v pairs
			$classes = array();
			foreach (CMSMenu::get_cms_classes() as $class) {
				$classes[$class] = Config::inst()->get($class, 'url_priority', Config::FIRST_SET);
			}

			// Sort them so highest priority item is first
			arsort($classes, SORT_NUMERIC);

			// Map over the array calling add_rule_for_controller on each
			array_map(array(__CLASS__, 'add_rule_for_controller'), array_keys($classes));
		}
		return self::$_rules;
	}

	/**
	 * Add the appropriate k/v pair to self::$rules for the given controller.
	 */
	protected static function add_rule_for_controller($controllerClass) {
		$urlSegment = Config::inst()->get($controllerClass, 'url_segment', Config::FIRST_SET);
		$urlRule    = Config::inst()->get($controllerClass, 'url_rule', Config::FIRST_SET);

		if($urlSegment) {
			// Make director rule
			if($urlRule[0] == '/') $urlRule = substr($urlRule,1);
			$rule = $urlSegment . '//' . $urlRule;

			// ensure that the first call to add_rule_for_controller for a rule takes precedence
			if(!isset(self::$_rules[$rule])) self::$_rules[$rule] = $controllerClass;
		}
	}

	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		// If this is the final portion of the request (i.e. the URL is just /admin), direct to the default panel
		if ($request->allParsed()) {
			$segment = Config::inst()->get($this->config()->default_panel, 'url_segment');

			$this->response = new SS_HTTPResponse();
			$this->redirect(Controller::join_links(self::admin_url(), $segment));
			return $this->response;
		}

		// Otherwise
		else {
			$rules = self::rules();
			foreach($rules as $pattern => $controller) {
				if(($arguments = $request->match($pattern, true)) !== false) {
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
	public static function get_template_global_variables() {
		return array(
			'adminURL' => 'admin_url'
		);
	}
}
