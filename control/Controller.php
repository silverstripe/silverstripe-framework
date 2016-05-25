<?php

/**
 * Controllers are the cornerstone of all site functionality in SilverStripe. The {@link Director}
 * selects a controller to pass control to, and then calls {@link run()}. This method will execute
 * the appropriate action - either by calling the action method, or displaying the action's template.
 *
 * See {@link getTemplate()} for information on how the template is chosen.
 *
 * @package framework
 *
 * @subpackage control
 */

use SilverStripe\Model\DataModel;
class Controller extends RequestHandler implements TemplateGlobalProvider {

	/**
	 * An array of arguments extracted from the URL.
	 *
	 * @var array
	 */
	protected $urlParams;

	/**
	 * Contains all GET and POST parameters passed to the current {@link SS_HTTPRequest}.
	 *
	 * @var array
	 */
	protected $requestParams;

	/**
	 * The URL part matched on the current controller as determined by the "$Action" part of the
	 * {@link $url_handlers} definition. Should correlate to a public method on this controller.
	 *
	 * Used in {@link render()} and {@link getViewer()} to determine action-specific templates.
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * The {@link Session} object for this controller.
	 *
	 * @var Session
	 */
	protected $session;

	/**
	 * Stack of current controllers. Controller::$controller_stack[0] is the current controller.
	 *
	 * @var array
	 */
	protected static $controller_stack = array();

	/**
	 * @var bool
	 */
	protected $basicAuthEnabled = true;

	/**
	 * The response object that the controller returns.
	 *
	 * Set in {@link handleRequest()}.
	 *
	 * @var SS_HTTPResponse
	 */
	protected $response;

	/**
	 * Default URL handlers.
	 *
	 * @var array
	 */
	private static $url_handlers = array(
		'$Action//$ID/$OtherID' => 'handleAction',
	);

	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'handleAction',
		'handleIndex',
	);

	/**
	 * Initialisation function that is run before any action on the controller is called.
	 *
	 * @uses BasicAuth::requireLogin()
	 */
	protected function init() {
		if($this->basicAuthEnabled) BasicAuth::protect_site_if_necessary();

		// This is used to test that subordinate controllers are actually calling parent::init() - a common bug
		$this->baseInitCalled = true;
	}

	/**
	 * A stand in function to protect the init function from failing to be called as well as providing before and
	 * after hooks for the init function itself
	 *
	 * This should be called on all controllers before handling requests
	 */
	public function doInit() {
		//extension hook
		$this->extend('onBeforeInit');

		// Safety call
		$this->baseInitCalled = false;
		$this->init();
		if (!$this->baseInitCalled) {
			user_error(
				"init() method on class '$this->class' doesn't call Controller::init()."
				. "Make sure that you have parent::init() included.",
				E_USER_WARNING
			);
		}

		$this->extend('onAfterInit');

	}

	/**
	 * Returns a link to this controller. Overload with your own Link rules if they exist.
	 *
	 * @return string
	 */
	public function Link() {
		return get_class($this) .'/';
	}

	/**
	 * {@inheritdoc}
	 *
	 * Also set the URLParams
	 */
	public function setRequest($request) {
		$return = parent::setRequest($request);
		$this->setURLParams($this->getRequest()->allParams());

		return $return;
	}

	/**
	 * A bootstrap for the handleRequest method
	 *
	 * @todo setDataModel and setRequest are redundantly called in parent::handleRequest() - sort this out
	 *
	 * @param SS_HTTPRequest $request
	 * @param DataModel $model
	 */
	protected function beforeHandleRequest(SS_HTTPRequest $request, DataModel $model) {
		//Push the current controller to protect against weird session issues
		$this->pushCurrent();
		//Set up the internal dependencies (request, response, datamodel)
		$this->setRequest($request);
		$this->setResponse(new SS_HTTPResponse());
		$this->setDataModel($model);
		//kick off the init functionality
		$this->doInit();

	}

	/**
	 * Cleanup for the handleRequest method
	 */
	protected function afterHandleRequest() {
		//Pop the current controller from the stack
		$this->popCurrent();
	}

	/**
	 * Executes this controller, and return an {@link SS_HTTPResponse} object with the result.
	 *
	 * This method defers to {@link RequestHandler->handleRequest()} to determine which action
	 *    should be executed
	 *
	 * Note: You should rarely need to overload handleRequest() -
	 * this kind of change is only really appropriate for things like nested
	 * controllers - {@link ModelAsController} and {@link RootURLController}
	 * are two examples here.  If you want to make more
	 * orthodox functionality, it's better to overload {@link init()} or {@link index()}.
	 *
	 * Important: If you are going to overload handleRequest,
	 * make sure that you start the method with $this->beforeHandleRequest()
	 * and end the method with $this->afterHandleRequest()
	 *
	 * @param SS_HTTPRequest $request
	 * @param DataModel $model
	 *
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		if (!$request) {
			user_error("Controller::handleRequest() not passed a request!", E_USER_ERROR);
		}

		//set up the controller for the incoming request
		$this->beforeHandleRequest($request, $model);

		//if the before handler manipulated the response in a way that we shouldn't proceed, then skip our request
		// handling
		if (!$this->getResponse()->isFinished()) {

			//retrieve the response for the request
			$response = parent::handleRequest($request, $model);

			//prepare the response (we can receive an assortment of response types (strings/objects/HTTPResponses)
			$this->prepareResponse($response);
		}

		//after request work
		$this->afterHandleRequest();

		//return the response
		return $this->getResponse();
	}

	/**
	 * Prepare the response (we can receive an assortment of response types (strings/objects/HTTPResponses) and
	 * changes the controller response object appropriately
	 *
	 * @param $response
	 */
	protected function prepareResponse($response) {
		if ($response instanceof SS_HTTPResponse) {
			if (isset($_REQUEST['debug_request'])) {
				Debug::message(
					"Request handler returned SS_HTTPResponse object to $this->class controller;"
					. "returning it without modification."
				);
			}
			$this->setResponse($response);

		}
		else {
			if ($response instanceof Object && $response->hasMethod('getViewer')) {
				if (isset($_REQUEST['debug_request'])) {
					Debug::message(
						"Request handler $response->class object to $this->class controller;"
						. "rendering with template returned by $response->class::getViewer()"
					);
				}
				$response = $response->getViewer($this->getAction())->process($response);
			}

			$this->getResponse()->setbody($response);

		}

		//deal with content if appropriate
		ContentNegotiator::process($this->getResponse());

		//add cache headers
		HTTP::add_cache_headers($this->getResponse());
	}

	/**
	 * Controller's default action handler.  It will call the method named in "$Action", if that method
	 * exists. If "$Action" isn't given, it will use "index" as a default.
	 *
	 * @param SS_HTTPRequest $request
	 * @param string $action
	 *
	 * @return HTMLText|SS_HTTPResponse
	 */
	protected function handleAction($request, $action) {
		foreach($request->latestParams() as $k => $v) {
			if($v || !isset($this->urlParams[$k])) $this->urlParams[$k] = $v;
		}

		$this->action = $action;
		$this->requestParams = $request->requestVars();

		if($this->hasMethod($action)) {
			$result = parent::handleAction($request, $action);

			// If the action returns an array, customise with it before rendering the template.
			if(is_array($result)) {
				return $this->getViewer($action)->process($this->customise($result));
			} else {
				return $result;
			}
		}

		// Fall back to index action with before/after handlers
		$beforeResult = $this->extend('beforeCallActionHandler', $request, $action);
		if ($beforeResult) {
			return reset($beforeResult);
		}

		$result = $this->getViewer($action)->process($this);

		$afterResult = $this->extend('afterCallActionHandler', $request, $action, $result);
		if($afterResult) {
			return reset($afterResult);
		}

		return $result;
	}

	/**
	 * @param array $urlParams
	 */
	public function setURLParams($urlParams) {
		$this->urlParams = $urlParams;
		return $this;
	}

	/**
	 * Returns the parameters extracted from the URL by the {@link Director}.
	 *
	 * @return array
	 */
	public function getURLParams() {
		return $this->urlParams;
	}

	/**
	 * Returns the SS_HTTPResponse object that this controller is building up. Can be used to set the
	 * status code and headers.
	 *
	 * @return SS_HTTPResponse
	 */
	public function getResponse() {
		if (!$this->response) {
			$this->setResponse(new SS_HTTPResponse());
		}
		return $this->response;
	}

	/**
	 * Sets the SS_HTTPResponse object that this controller is building up.
	 *
	 * @param SS_HTTPResponse $response
	 *
	 * @return $this
	 */
	public function setResponse(SS_HTTPResponse $response) {
		$this->response = $response;
		return $this;
	}

	/**
	 * @var bool
	 */
	protected $baseInitCalled = false;

	/**
	 * Return the object that is going to own a form that's being processed, and handle its execution.
	 * Note that the result need not be an actual controller object.
	 *
	 * @return mixed
	 */
	public function getFormOwner() {
		// Get the appropriate controller: sometimes we want to get a form from another controller
		if(isset($this->requestParams['formController'])) {
			$formController = Director::getControllerForURL($this->requestParams['formController']);

			while(is_a($formController, 'NestedController')) {
				$formController = $formController->getNestedController();
			}
			return $formController;

		} else {
			return $this;
		}
	}

	/**
	 * This is the default action handler used if a method doesn't exist. It will process the
	 * controller object with the template returned by {@link getViewer()}.
	 *
	 * @param string $action
	 *
	 * @return HTMLText
	 */
	public function defaultAction($action) {
		return $this->getViewer($action)->process($this);
	}

	/**
	 * Returns the action that is being executed on this controller.
	 *
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Return the viewer identified being the default handler for this Controller/Action combination.
	 *
	 * @param string $action
	 *
	 * @return SSViewer
	 */
	public function getViewer($action) {
		// Hard-coded templates
		if(isset($this->templates[$action]) && $this->templates[$action]) {
			$templates = $this->templates[$action];

		}	else if(isset($this->templates['index']) && $this->templates['index']) {
			$templates = $this->templates['index'];

		}	else if($this->template) {
			$templates = $this->template;
		} else {
			// Add action-specific templates for inheritance chain
			$templates = array();
			$parentClass = $this->class;
			if($action && $action != 'index') {
				$parentClass = $this->class;
				while($parentClass != "Controller") {
					$templates[] = strtok($parentClass,'_') . '_' . $action;
					$parentClass = get_parent_class($parentClass);
				}
			}
			// Add controller templates for inheritance chain
			$parentClass = $this->class;
			while($parentClass != "Controller") {
				$templates[] = strtok($parentClass,'_');
				$parentClass = get_parent_class($parentClass);
			}

			$templates[] = 'Controller';

			// remove duplicates
			$templates = array_unique($templates);
		}

		return new SSViewer($templates);
	}

	/**
	 * @param string $action
	 *
	 * @return bool
	 */
	public function hasAction($action) {
		return parent::hasAction($action) || $this->hasActionTemplate($action);
	}

	/**
	 * Removes all the "action" part of the current URL and returns the result. If no action parameter
	 * is present, returns the full URL.
	 *
	 * @param string $fullURL
	 * @param null|string $action
	 *
	 * @return string
	 */
	public function removeAction($fullURL, $action = null) {
		if (!$action) $action = $this->getAction();    //default to current action
		$returnURL = $fullURL;

		if (($pos = strpos($fullURL, $action)) !== false) {
			$returnURL = substr($fullURL,0,$pos);
		}

		return $returnURL;
	}

	/**
	 * Return the class that defines the given action, so that we know where to check allowed_actions.
	 * Overrides RequestHandler to also look at defined templates.
	 *
	 * @param string $action
	 *
	 * @return string
	 */
	protected function definingClassForAction($action) {
		$definingClass = parent::definingClassForAction($action);
		if($definingClass) return $definingClass;

		$class = get_class($this);
		while($class != 'RequestHandler') {
			$templateName = strtok($class, '_') . '_' . $action;
			if(SSViewer::hasTemplate($templateName)) return $class;

			$class = get_parent_class($class);
		}
	}

	/**
	 * Returns TRUE if this controller has a template that is specifically designed to handle a
	 * specific action.
	 *
	 * @param string $action
	 *
	 * @return bool
	 */
	public function hasActionTemplate($action) {
		if(isset($this->templates[$action])) return true;

		$parentClass = $this->class;
		$templates   = array();

		while($parentClass != 'Controller') {
			$templates[] = strtok($parentClass, '_') . '_' . $action;
			$parentClass = get_parent_class($parentClass);
		}

		return SSViewer::hasTemplate($templates);
	}

	/**
	 * Render the current controller with the templates determined by {@link getViewer()}.
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function render($params = null) {
		$template = $this->getViewer($this->getAction());

		// if the object is already customised (e.g. through Controller->run()), use it
		$obj = ($this->customisedObj) ? $this->customisedObj : $this;

		if($params) $obj = $this->customise($params);

		return $template->process($obj);
	}

	/**
	 * Call this to disable site-wide basic authentication for a specific controller. This must be
	 * called before Controller::init(). That is, you must call it in your controller's init method
	 * before it calls parent::init().
	 */
	public function disableBasicAuth() {
		$this->basicAuthEnabled = false;
	}

	/**
	 * Returns the current controller.
	 *
	 * @return Controller
	 */
	public static function curr() {
		if(Controller::$controller_stack) {
			return Controller::$controller_stack[0];
		} else {
			user_error("No current controller available", E_USER_WARNING);
		}
	}

	/**
	 * Tests whether we have a currently active controller or not. True if there is at least 1
	 * controller in the stack.
	 *
	 * @return bool
	 */
	public static function has_curr() {
		return Controller::$controller_stack ? true : false;
	}

	/**
	 * Returns true if the member is allowed to do the given action. Defaults to the currently logged
	 * in user.
	 *
	 * @param string $perm
	 * @param null|member $member
	 *
	 * @return bool
	 */
	public function can($perm, $member = null) {
		if(!$member) $member = Member::currentUser();
		if(is_array($perm)) {
			$perm = array_map(array($this, 'can'), $perm, array_fill(0, count($perm), $member));
			return min($perm);
		}
		if($this->hasMethod($methodName = 'can' . $perm)) {
			return $this->$methodName($member);
		} else {
			return true;
		}
	}

	/**
	 * Pushes this controller onto the stack of current controllers. This means that any redirection,
	 * session setting, or other things that rely on Controller::curr() will now write to this
	 * controller object.
	 */
	public function pushCurrent() {
		array_unshift(self::$controller_stack, $this);
		// Create a new session object
		if(!$this->session) {
			if(isset(self::$controller_stack[1])) {
				$this->session = self::$controller_stack[1]->getSession();
			} else {
				$this->session = Injector::inst()->create('Session', array());
			}
		}
	}

	/**
	 * Pop this controller off the top of the stack.
	 */
	public function popCurrent() {
		if($this === self::$controller_stack[0]) {
			array_shift(self::$controller_stack);
		} else {
			user_error("popCurrent called on $this->class controller, but it wasn't at the top of the stack",
				E_USER_WARNING);
		}
	}

	/**
	 * Redirect to the given URL.
	 *
	 * @param string $url
	 * @param int $code
	 *
	 * @return SS_HTTPResponse
	 */
	public function redirect($url, $code=302) {

		if($this->getResponse()->getHeader('Location') && $this->getResponse()->getHeader('Location') != $url) {
			user_error("Already directed to " . $this->getResponse()->getHeader('Location')
				. "; now trying to direct to $url", E_USER_WARNING);
			return;
		}

		// Attach site-root to relative links, if they have a slash in them
		if($url=="" || $url[0]=='?' || (substr($url,0,4) != "http" && $url[0] != "/" && strpos($url,'/') !== false)) {
			$url = Director::baseURL() . $url;
		}

		return $this->getResponse()->redirect($url, $code);
	}

	/**
	 * Redirect back. Uses either the HTTP-Referer or a manually set request-variable called "BackURL".
	 * This variable is needed in scenarios where HTTP-Referer is not sent (e.g when calling a page by
	 * location.href in IE). If none of the two variables is available, it will redirect to the base
	 * URL (see {@link Director::baseURL()}).
	 *
	 * @uses redirect()
	 *
	 * @return bool|SS_HTTPResponse
	 */
	public function redirectBack() {
		// Don't cache the redirect back ever
		HTTP::set_cache_age(0);

		$url = null;

		// In edge-cases, this will be called outside of a handleRequest() context; in that case,
		// redirect to the homepage - don't break into the global state at this stage because we'll
		// be calling from a test context or something else where the global state is inappropraite
		if($this->getRequest()) {
			if($this->getRequest()->requestVar('BackURL')) {
				$url = $this->getRequest()->requestVar('BackURL');
			} else if($this->getRequest()->isAjax() && $this->getRequest()->getHeader('X-Backurl')) {
				$url = $this->getRequest()->getHeader('X-Backurl');
			} else if($this->getRequest()->getHeader('Referer')) {
				$url = $this->getRequest()->getHeader('Referer');
			}
		}

		if(!$url) $url = Director::baseURL();

		// absolute redirection URLs not located on this site may cause phishing
		if(Director::is_site_url($url)) {
			$url = Director::absoluteURL($url, true);
			return $this->redirect($url);
		} else {
			return false;
		}

	}

	/**
	 * Tests whether a redirection has been requested. If redirect() has been called, it will return
	 * the URL redirected to. Otherwise, it will return null.
	 *
	 * @return null|string
	 */
	public function redirectedTo() {
		return $this->getResponse() && $this->getResponse()->getHeader('Location');
	}

	/**
	 * Get the Session object representing this Controller's session.
	 *
	 * @return Session
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Set the Session object.
	 *
	 * @param Session $session
	 */
	public function setSession(Session $session) {
		$this->session = $session;
	}

	/**
	 * Joins two or more link segments together, putting a slash between them if necessary. Use this
	 * for building the results of {@link Link()} methods. If either of the links have query strings,
	 * then they will be combined and put at the end of the resulting url.
	 *
	 * Caution: All parameters are expected to be URI-encoded already.
	 *
	 * @param string
	 *
	 * @return string
	 */
	public static function join_links() {
		$args = func_get_args();
		$result = "";
		$queryargs = array();
		$fragmentIdentifier = null;

		foreach($args as $arg) {
			// Find fragment identifier - keep the last one
			if(strpos($arg,'#') !== false) {
				list($arg, $fragmentIdentifier) = explode('#',$arg,2);
			}
			// Find querystrings
			if(strpos($arg,'?') !== false) {
				list($arg, $suffix) = explode('?',$arg,2);
				parse_str($suffix, $localargs);
				$queryargs = array_merge($queryargs, $localargs);
			}
			if((is_string($arg) && $arg) || is_numeric($arg)) {
				$arg = (string) $arg;
				if($result && substr($result,-1) != '/' && $arg[0] != '/') $result .= "/$arg";
				else $result .= (substr($result, -1) == '/' && $arg[0] == '/') ? ltrim($arg, '/') : $arg;
			}
		}

		if($queryargs) $result .= '?' . http_build_query($queryargs);

		if($fragmentIdentifier) $result .= "#$fragmentIdentifier";

		return $result;
	}

	/**
	 * @return array
	 */
	public static function get_template_global_variables() {
		return array(
			'CurrentPage' => 'curr',
		);
	}
}


