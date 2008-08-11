<?php
/**
 * Base controller class.
 * Controllers are the cornerstone of all site functionality in Sapphire.  The {@link Director}
 * selects a controller to pass control to, and then calls {@link run()}.  This method will execute
 * the appropriate action - either by calling the action method, or displaying the action's template.
 *
 * See {@link getTemplate()} for information on how the template is chosen.
 * @package sapphire
 * @subpackage control
 */
class Controller extends RequestHandlingData {
	/**
	 * An array of arguments extracted from the URL 
	 */
	protected $urlParams;
	protected $requestParams;
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
	 * The HTTPResponse object that the controller returns
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
	 * Handles HTTP requests.
	 * @param $request The {@link HTTPRequest} object that is responsible for distributing request parsing.
	 */
	function handleRequest($request) {
		$this->pushCurrent();
		$this->urlParams = $request->allParams();
		$this->response = new HTTPResponse();

		// Init
		$this->baseInitCalled = false;	
		$this->init();
		if(!$this->baseInitCalled) user_error("init() method on class '$this->class' doesn't call Controller::init().  Make sure that you have parent::init() included.", E_USER_WARNING);

		// If we had a redirection or something, halt processing.
		if($this->response->isFinished()) {
			$this->popCurrent();
			return $this->response;
		}

		$body = parent::handleRequest($request);
		if($body instanceof HTTPResponse) {
			if(isset($_REQUEST['debug_request'])) Debug::message("Request handler returned HTTPResponse object to $this->class controller; returning it without modification.");
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
	function handleAction($request) {
		// urlParams, requestParams, and action are set for backward compatability 
		foreach($request->latestParams() as $k => $v) {
			if($v || !isset($this->urlParams[$k])) $this->urlParams[$k] = $v;
		}

		$this->action = str_replace("-","_",$request->param('Action'));
		$this->requestParams = $request->requestVars();
		if(!$this->action) $this->action = 'index';
		$methodName = $this->action;
		
		// run & init are manually disabled, because they create infinite loops and other dodgy situations 
		if($this->checkAccessAction($this->action) && !in_array(strtolower($this->action), array('run', 'init'))) {
			if($this->hasMethod($methodName)) {
				$result = $this->$methodName($request);
			
				// Method returns an array, that is used to customise the object before rendering with a template
				if(is_array($result)) {
					return $this->getViewer($this->action)->process($this->customise($result));
				
				// Method returns a string / object, in which case we just return that
				} else {
					return $result;
				}
			
			// There is no method, in which case we just render this object using a (possibly alternate) template
			} else {
				return $this->getViewer($this->action)->process($this);
			}
		} else {
			return $this->httpError(403, "Action '$this->action' isn't allowed on class $this->class");
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
	 * Returns the HTTPResponse object that this controller is building up.
	 * Can be used to set the status code and headers
	 */
	function getResponse() {
		return $this->response;
	}

	protected $baseInitCalled = false;

	/**
	 * Executes this controller, and return an {@link HTTPResponse} object with the result.
	 * 
	 * This method first does a few set-up activities:
	 *  - Push this controller ont to the controller stack - see {@link Controller::curr()} for information about this.
	 *  - Call {@link init()}
	 * 
	 * Then it looks for the action method.  The action is taken from $this->urlParams['Action'] - for this reason, it's important
	 * to have $Action included in your Director rule
	 * 
	 * If $requestParams['executeForm'] is set, then the Controller assumes that we're processing a form.  This is usually
	 * set by adding ?executeForm=XXX to the form's action URL.  Form processing differs in the following ways:
	 *  - The action name will be the name of the button clicked.  If no button-click can be detected, the first button in the
	 *    list will be assumed.
	 *  - If the given action method doesn't exist on the controller, Controller will look for that method on the Form object.
	 *    this lets developers package both a form and its action handlers in a single subclass of Form.
	 * 
	 * NOTE: You should rarely need to overload run() - this kind of change is only really appropriate for things like nested
	 * controllers - {@link ModelAsController} and {@link RootURLController} are two examples here.  If you want to make more
	 * orthodox functionality, it's better to overload {@link init()} or {@link index()}.
	 * 
	 * Execute the appropriate action handler.  If none is given, use defaultAction to display
	 * a template.  The default action will be appropriate in most cases where displaying data
	 * is the core goal; the Viewer can call methods on the controller to get the data it needs.
	 * 
	 * @param array $requestParams GET and POST variables.
	 * @return HTTPResponse The response that this controller produces, including HTTP headers such as redirection info
	 */
	
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
			$parentClass = $this->class;
			while($parentClass != "Controller") {
				$templateName = $parentClass;
				if(($pos = strpos($templateName,'_')) !== false) $templateName = substr($templateName, 0, $pos);

				if($action && $action != "index") $templates[] = $templateName . '_' . $action;
				$templates[] = $templateName;

				$parentClass = get_parent_class($parentClass);
			}
			$templates = array_unique($templates);
		}
		if(isset($_GET['showtemplate'])) Debug::show($templates);
		return new SSViewer($templates);
	}
  
	/**
	 * Call this to disable basic authentication on test sites.
	 * must be called in the init() method
	 * @deprecated Use BasicAuth::disable() instead?  This is used in CliController - it should be updated.
	 */
	function disableBasicAuth() {
		$this->basicAuthEnabled = false;
	}
	
	/**
	 * Initialisation function that is run before any action on the controller is called.
	 * 
	 * @uses BasicAuth::requireLogin()
	 */
	function init() {
		// Test and development sites should be secured, via basic-auth
		if(ClassInfo::hasTable("Group") && ClassInfo::hasTable("Member") && Director::isTest() && $this->basicAuthEnabled) {
			BasicAuth::requireLogin("SilverStripe test website.  Use your  CMS login", "ADMIN");
		}		
		
		//
		Cookie::set("PastVisitor", true);

		// ClassInfo::hasTable() called to ensure that we're not in a very-first-setup stage
		if(ClassInfo::hasTable("Group") && ClassInfo::hasTable("Member") && ($member = Member::currentUser())) {
			Cookie::set("PastMember", true);
			DB::query("UPDATE Member SET LastVisited = NOW() WHERE ID = $member->ID", null);
		}
		
		// This is used to test that subordinate controllers are actually calling parent::init() - a common bug
		$this->baseInitCalled = true;
	}

	/**
	 * @deprecated use Controller::curr() instead
	 * @returns Controller
	 */
	public static function currentController() {
		user_error('Controller::currentController() is deprecated. Use Controller::curr() instead.', E_USER_NOTICE);
		return self::curr();
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
	 * @deprecated I don't believe that the system has widespread use/support of this.
	 */
	function can($perm, $member = null) {
		if(!$member) $member = Member::currentUser();
		if($this->hasMethod($methodName = 'can' . $perm)) {
			return $this->$methodName($member);
		} else {
			return true;
		}
	}

	//-----------------------------------------------------------------------------------

	/**
	 * returns a date object for use within a template
	 * Usage: $Now.Year - Returns 2006
	 * @return Date The current date
	 */
	function Now() {
		$d = new Date(null);
		$d->setVal(date("Y-m-d h:i:s"));
		return $d;
	}

	/**
	 * Returns a link to any other page
	 * @deprecated It's unclear what value this has; construct a link manually or use your own custom link-gen functions.
	 */
	function LinkTo($a, $b) {
		return Director::baseURL() . $a . '/' . $b;
	}

	/**
	 * Returns an absolute link to this controller
	 */
	function AbsoluteLink() {
		return Director::absoluteURL($this->Link());
	}

	/**
	 * Returns the currently logged in user
	 */
	function CurrentMember() {
		return Member::currentUser();
	}

	/**
	 * Returns true if the visitor has been here before
	 * @return boolean
	 */
	function PastVisitor() {
		return Cookie::get("PastVisitor") ? true : false;
	}

	/**
	 * Return true if the visitor has signed up for a login account before
	 * @return boolean
	 */
	function PastMember() {
		return Cookie::get("PastMember") ? true : false;
	}

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
	 * Redirct to the given URL.
	 * It is generally recommended to call Director::redirect() rather than calling this function directly.
	 */
	function redirect($url, $code=302) {
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
	 * Tests whether a redirection has been requested.
	 * @return string If redirect() has been called, it will return the URL redirected to.  Otherwise, it will return null;
	 */
	function redirectedTo() {
		return $this->response->getHeader('Location');
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
	 * Returns true if this controller is processing an ajax request
	 * @return boolean True if this controller is processing an ajax request
	 */
	function isAjax() {
		return (
			isset($this->requestParams['ajax']) ||
			(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest")
		);
	}
	
	/**
	 * Joins two link segments together, putting a slash between them if necessary.
	 * Use this for building the results of Link() methods.
	 */
	static function join_links() {
		$args = func_get_args();
		
		$result = array_shift($args);
		foreach($args as $arg) {
			if(substr($result,-1) != '/' && $arg[0] != '/') $result .= "/$arg";
			else $result .= $arg;
		}
		
		return $result;
	}
}

?>
