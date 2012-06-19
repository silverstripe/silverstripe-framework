<?php
/**
 * Base controller class.
 * Controllers are the cornerstone of all site functionality in SilverStripe.  The {@link Director}
 * selects a controller to pass control to, and then calls {@link run()}.  This method will execute
 * the appropriate action - either by calling the action method, or displaying the action's template.
 *
 * See {@link getTemplate()} for information on how the template is chosen.
 * @package framework
 * @subpackage control
 */
class Controller extends RequestHandler implements TemplateGlobalProvider {

	/**
	 * @var array $urlParams An array of arguments extracted from the URL 
	 */
	protected $urlParams;
	
	/**
	 * @var array $requestParams Contains all GET and POST parameters
	 * passed to the current {@link SS_HTTPRequest}.
	 * @uses SS_HTTPRequest->requestVars()
	 */
	protected $requestParams;
	
	/**
	 * @var string $action The URL part matched on the current controller as
	 * determined by the "$Action" part of the {@link $url_handlers} definition.
	 * Should correlate to a public method on this controller.
	 * Used in {@link render()} and {@link getViewer()} to determine
	 * action-specific templates.
	 */
	protected $action;
	
	/**
	 * The {@link Session} object for this controller
	 */
	protected $session;

	/**
	 * Stack of current controllers.
	 * Controller::$controller_stack[0] is the current controller.
	 */
	protected static $controller_stack = array();
	
	protected $basicAuthEnabled = true;

	/**
	 * @var SS_HTTPResponse $response The response object that the controller returns.
	 * Set in {@link handleRequest()}.
	 */
	protected $response;
	
	/**
	 * Default URL handlers - (Action)/(ID)/(OtherID)
	 */
	static $url_handlers = array(
		'$Action//$ID/$OtherID' => 'handleAction',
	);
	
	static $allowed_actions = array(
		'handleAction',
		'handleIndex',
	);
	
	/**
	 * Initialisation function that is run before any action on the controller is called.
	 * 
	 * @uses BasicAuth::requireLogin()
	 */
	function init() {
		if($this->basicAuthEnabled) BasicAuth::protect_site_if_necessary();

		// Directly access the session variable just in case the Group or Member tables don't yet exist
		if(Session::get('loggedInAs') && Security::database_is_ready()) {
			$member = Member::currentUser();
			if($member) {
				if(!headers_sent()) Cookie::set("PastMember", true, 90, null, null, false, true);
				DB::query("UPDATE \"Member\" SET \"LastVisited\" = " . DB::getConn()->now() . " WHERE \"ID\" = $member->ID", null);
			}
		}
		
		// This is used to test that subordinate controllers are actually calling parent::init() - a common bug
		$this->baseInitCalled = true;
	}
	
	/**
	 * Returns a link to this controller.  Overload with your own Link rules if they exist.
	 */
	function Link() {
		return get_class($this) .'/';
	}
	
	/**
	 * Executes this controller, and return an {@link SS_HTTPResponse} object with the result.
	 * 
	 * This method first does a few set-up activities:
	 *  - Push this controller ont to the controller stack - 
	 *    see {@link Controller::curr()} for information about this.
	 *  - Call {@link init()}
	 *  - Defer to {@link RequestHandler->handleRequest()} to determine which action
	 *    should be executed
	 * 
	 * Note: $requestParams['executeForm'] support was removed, 
	 * make the following change in your URLs: 
	 * "/?executeForm=FooBar" -> "/FooBar" 
	 * Also make sure "FooBar" is in the $allowed_actions of your controller class.
	 * 
	 * Note: You should rarely need to overload run() - 
	 * this kind of change is only really appropriate for things like nested
	 * controllers - {@link ModelAsController} and {@link RootURLController} 
	 * are two examples here.  If you want to make more
	 * orthodox functionality, it's better to overload {@link init()} or {@link index()}.
	 * 
	 * Important: If you are going to overload handleRequest, 
	 * make sure that you start the method with $this->pushCurrent()
	 * and end the method with $this->popCurrent().  
	 * Failure to do this will create weird session errors.
	 * 
	 * @param $request The {@link SS_HTTPRequest} object that is responsible 
	 *  for distributing request parsing.
	 * @return SS_HTTPResponse The response that this controller produces, 
	 *  including HTTP headers such as redirection info
	 */
	function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		if(!$request) user_error("Controller::handleRequest() not passed a request!", E_USER_ERROR);
		
		$this->pushCurrent();
		$this->urlParams = $request->allParams();
		$this->request = $request;
		$this->response = new SS_HTTPResponse();
		$this->setDataModel($model);
		
		$this->extend('onBeforeInit');

		// Init
		$this->baseInitCalled = false;	
		$this->init();
		if(!$this->baseInitCalled) user_error("init() method on class '$this->class' doesn't call Controller::init().  Make sure that you have parent::init() included.", E_USER_WARNING);

		$this->extend('onAfterInit');
		
		// If we had a redirection or something, halt processing.
		if($this->response->isFinished()) {
			$this->popCurrent();
			return $this->response;
		}

		$body = parent::handleRequest($request, $model);
		if($body instanceof SS_HTTPResponse) {
			if(isset($_REQUEST['debug_request'])) Debug::message("Request handler returned SS_HTTPResponse object to $this->class controller; returning it without modification.");
			$this->response = $body;
			
		} else {
			if(is_object($body)) {
				if(isset($_REQUEST['debug_request'])) Debug::message("Request handler $body->class object to $this->class controller;, rendering with template returned by $body->class::getViewer()");
			   $body = $body->getViewer($request->latestParam('Action'))->process($body);
			}
			
			$this->response->setBody($body);
		}


		ContentNegotiator::process($this->response);
		HTTP::add_cache_headers($this->response);

		$this->popCurrent();
		return $this->response;
	}

	/**
	 * Controller's default action handler.  It will call the method named in $Action, if that method exists.
	 * If $Action isn't given, it will use "index" as a default.
	 */
	public function handleAction($request) {
		// urlParams, requestParams, and action are set for backward compatability 
		foreach($request->latestParams() as $k => $v) {
			if($v || !isset($this->urlParams[$k])) $this->urlParams[$k] = $v;
		}

		$this->action = str_replace("-","_",$request->param('Action'));
		$this->requestParams = $request->requestVars();
		if(!$this->action) $this->action = 'index';
		
		if(!$this->hasAction($this->action)) {
			$this->httpError(404, "The action '$this->action' does not exist in class $this->class");
		}
		
		// run & init are manually disabled, because they create infinite loops and other dodgy situations 
		if(!$this->checkAccessAction($this->action) || in_array(strtolower($this->action), array('run', 'init'))) {
			return $this->httpError(403, "Action '$this->action' isn't allowed on class $this->class");
		}
		
		if($this->hasMethod($this->action)) {
			$result = $this->{$this->action}($request);
			
			// If the action returns an array, customise with it before rendering the template.
			if(is_array($result)) {
				return $this->getViewer($this->action)->process($this->customise($result));
			} else {
				return $result;
			}
		} else {
			return $this->getViewer($this->action)->process($this);
		}
	}

	function setURLParams($urlParams) {
		$this->urlParams = $urlParams;
	}
	
	/**
	 * @return array The parameters extracted from the URL by the {@link Director}.
	 */
	function getURLParams() {
		return $this->urlParams;
	}
	
	/**
	 * Returns the SS_HTTPResponse object that this controller is building up.
	 * Can be used to set the status code and headers
	 */
	function getResponse() {
		return $this->response;
	}
	
	protected $baseInitCalled = false;

	/**
	 * Return the object that is going to own a form that's being processed, and handle its execution.
	 * Note that the result needn't be an actual controller object.
	 */
	function getFormOwner() {
		// Get the appropraite ocntroller: sometimes we want to get a form from another controller
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
	 * This is the default action handler used if a method doesn't exist.
	 * It will process the controller object with the template returned by {@link getViewer()}
	 */
	function defaultAction($action) {
		return $this->getViewer($action)->process($this);
	}

	/**
	 * Returns the action that is being executed on this controller.
	 */
	function getAction() {
		return $this->action;
	}

	/**
	 * Return an SSViewer object to process the data
	 * @return SSViewer The viewer identified being the default handler for this Controller/Action combination
	 */
	function getViewer($action) {
		// Hard-coded templates
		if($this->templates[$action]) {
			$templates = $this->templates[$action];
		}	else if($this->templates['index']) {
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
	
	public function hasAction($action) {
		return parent::hasAction($action) || $this->hasActionTemplate($action);
	}

	/**
	 * Removes all the "action" part of the current URL and returns the result.
	 * If no action parameter is present, returns the full URL
	 * @static
	 * @return String
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
	 * Returns TRUE if this controller has a template that is specifically designed to handle a specific action.
	 *
	 * @param string $action
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
	 * Render the current controller with the templates determined
	 * by {@link getViewer()}.
	 * 
	 * @param array $params Key-value array for custom template variables (Optional)
	 * @return string Parsed template content 
	 */
	function render($params = null) {
		$template = $this->getViewer($this->getAction());
	
		// if the object is already customised (e.g. through Controller->run()), use it
		$obj = ($this->customisedObj) ? $this->customisedObj : $this;
	
		if($params) $obj = $this->customise($params);
		
		return $template->process($obj);
	}
  
	/**
	 * Call this to disable site-wide basic authentication for a specific contoller.
	 * This must be called before Controller::init().  That is, you must call it in your controller's
	 * init method before it calls parent::init().
	 */
	function disableBasicAuth() {
		$this->basicAuthEnabled = false;
	}

	/**
	 * Returns the current controller
	 * @returns Controller
	 */
	public static function curr() {
		if(Controller::$controller_stack) {
			return Controller::$controller_stack[0];
		} else {
			user_error("No current controller available", E_USER_WARNING);
		}
	}
	
	/**
	 * Tests whether we have a currently active controller or not
	 * @return boolean True if there is at least 1 controller in the stack.
	 */
	public static function has_curr() {
		return Controller::$controller_stack ? true : false;
	}

	/**
	 * Returns true if the member is allowed to do the given action.
	 * @param perm The permission to be checked, such as 'View'.
	 * @param member The member whose permissions need checking.  Defaults to the currently logged
	 * in user.
	 * @return boolean
	 */
	function can($perm, $member = null) {
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

	//-----------------------------------------------------------------------------------

	/**
	 * Pushes this controller onto the stack of current controllers.
	 * This means that any redirection, session setting, or other things that rely on Controller::curr() will now write to this
	 * controller object.
	 */
	function pushCurrent() {
		array_unshift(self::$controller_stack, $this);
		// Create a new session object
		if(!$this->session) {
			if(isset(self::$controller_stack[1])) {
				$this->session = self::$controller_stack[1]->getSession();
			} else {
				$this->session = new Session(null);
			}
		}
	}

	/**
	 * Pop this controller off the top of the stack.
	 */
	function popCurrent() {
		if($this === self::$controller_stack[0]) {
			array_shift(self::$controller_stack);
		} else {
			user_error("popCurrent called on $this->class controller, but it wasn't at the top of the stack", E_USER_WARNING);
		}
	}
	
	/**
	 * Redirect to the given URL.
	 */
	function redirect($url, $code=302) {
		if(!$this->response) $this->response = new SS_HTTPResponse();
		
		if($this->response->getHeader('Location')) {
			user_error("Already directed to " . $this->response->getHeader('Location') . "; now trying to direct to $url", E_USER_WARNING);
			return;
		}

		// Attach site-root to relative links, if they have a slash in them
		if($url == "" || $url[0] == '?' || (substr($url,0,4) != "http" && $url[0] != "/" && strpos($url,'/') !== false)){
			$url = Director::baseURL() . $url;
		}

		$this->response->redirect($url, $code);
	}
	
	/**
	 * Redirect back. Uses either the HTTP_REFERER or a manually set request-variable called "BackURL".
	 * This variable is needed in scenarios where not HTTP-Referer is sent (
	 * e.g when calling a page by location.href in IE).
	 * If none of the two variables is available, it will redirect to the base
	 * URL (see {@link Director::baseURL()}).
	 * @uses redirect()
	 */
	function redirectBack() {
		$url = null;
		
		// In edge-cases, this will be called outside of a handleRequest() context; in that case,
		// redirect to the homepage - don't break into the global state at this stage because we'll
		// be calling from a test context or something else where the global state is inappropraite
		if($this->request) {
			if($this->request->requestVar('BackURL')) {
				$url = $this->request->requestVar('BackURL');
			} else if($this->request->getHeader('Referer')) {
				$url = $this->request->getHeader('Referer');
			}
		}
		
		if(!$url) $url = Director::baseURL();

		// absolute redirection URLs not located on this site may cause phishing
		if(Director::is_site_url($url)) {
			return $this->redirect($url);
		} else {
			return false;
		}

	}
	
	/**
	 * Tests whether a redirection has been requested.
	 * @return string If redirect() has been called, it will return the URL redirected to.  Otherwise, it will return null;
	 */
	function redirectedTo() {
		return $this->response && $this->response->getHeader('Location');
	} 
	
	/**
	 * Get the Session object representing this Controller's session
	 * @return Session
	 */
	function getSession() {
		return $this->session;
	}
	
	/**
	 * Set the Session object.
	 */
	function setSession(Session $session) {
		$this->session = $session;
	}
	
	/**
	 * Joins two or more link segments together, putting a slash between them if necessary.
	 * Use this for building the results of {@link Link()} methods.
	 * If either of the links have query strings, 
	 * then they will be combined and put at the end of the resulting url.
	 * 
	 * Caution: All parameters are expected to be URI-encoded already.
	 * 
	 * @param String 
	 * @return String
	 */
	static function join_links() {
		$args = func_get_args();
		$result = "";
		$querystrings = array();
		$fragmentIdentifier = null;
		foreach($args as $arg) {
			// Find fragment identifier - keep the last one
			if(strpos($arg,'#') !== false) {
				list($arg, $fragmentIdentifier) = explode('#',$arg,2);
			}
			// Find querystrings
			if(strpos($arg,'?') !== false) {
				list($arg, $suffix) = explode('?',$arg,2);
				$querystrings[] = $suffix;
			}
			if((is_string($arg) && $arg) || is_numeric($arg)) {
				$arg = (string)$arg;
				if($result && substr($result,-1) != '/' && $arg[0] != '/') $result .= "/$arg";
				else $result .= (substr($result, -1) == '/' && $arg[0] == '/') ? ltrim($arg, '/') : $arg;
			}
		}
		
		if($querystrings) $result .= '?' . implode('&', $querystrings);
		if($fragmentIdentifier) $result .= "#$fragmentIdentifier";
		
		return $result;
	}

	public static function get_template_global_variables() {
		return array(
			'CurrentPage' => 'curr',
		);
	}
}


