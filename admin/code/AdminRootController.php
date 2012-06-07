<?php

class AdminRootController extends Controller {

	/**
	 * @var string
	 * @config
	 * The url base that all LeftAndMain derived panels will live under
	 * Won't automatically update the base route if you change this - that has to be done seperately
	 */
	static $url_base = 'admin';

	/**
	 * @var string
	 * @config
	 * The LeftAndMain child that will be used as the initial panel to display if none is selected (i.e. if you visit /admin)
	 */
	static $default_panel = 'SecurityAdmin';

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

	function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		// If this is the final portion of the request (i.e. the URL is just /admin), direct to the default panel
		if ($request->allParsed()) {
			$base = $this->config()->url_base;
			$segment = Config::inst()->get($this->config()->default_panel, 'url_segment');

			$this->response = new SS_HTTPResponse();
			$this->redirect(Controller::join_links($base, $segment));
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
}
