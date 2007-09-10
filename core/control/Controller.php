<?php

/**
 * Base controller class.
 * Controllers are the cornerstone of all site functionality in Sapphire.  The {@link Director}
 * selects a controller to pass control to, and then calls {@link run()}.  This method will execute
 * the appropriate action - either by calling the action method, or displaying the action's template.
 * 
 * See {@link getTemplate()} for information on how the template is chosen.
 */
class Controller extends ViewableData {
	
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
	
	function setURLParams($urlParams) {
		$this->urlParams = $urlParams;
	}
	
	/**
	 * @return
	 */
	function getURLParams() {
		return $this->urlParams;
	}
	
	/**
	 * Execute the appropriate action handler.  If none is given, use defaultAction to display
	 * a template.  The default action will be appropriate in most cases where displaying data
	 * is the core goal; the Viewer can call methods on the controller to get the data it needs.
	 * 
	 * @param array $urlParams named parameters extracted from the URL, including Action.
	 * @param array $requestParams GET and POST variables.
	 * @return HTTPResponse The response that this controller produces, including HTTP headers such as redirection info
	 */
	protected $baseInitCalled = false;
	function run($requestParams) {
		if(isset($_GET['debug_profile'])) Profiler::mark("Controller", "run");		
		$this->pushCurrent();

		$this->response = new HTTPResponse();
		$this->requestParams = $requestParams;

		$this->action = isset($this->urlParams['Action']) ? str_replace("-","_",$this->urlParams['Action']) : "index";

		// Init
		$this->baseInitCalled = false;
		$this->init();
		if(!$this->baseInitCalled) user_error("init() method on class '$this->class' doesn't call Controller::init().  Make sure that you have parent::init() included.", E_USER_WARNING);

		// If we had a redirection or something, halt processing.
		if($this->response->isFinished()) {
			$this->popCurrent();
			return $this->response;
		}
		
		// Look at the action variables for forms
		foreach($this->requestParams as $paramName => $paramVal) {
			if(substr($paramName,0,7) == 'action_') {
				// Cleanup action_, _x and _y from image fields
				$funcName = preg_replace(array('/^action_/','/_x$|_y$/'),'',$paramName);
				break;
			}
		}

		// Form handler
		if(isset($this->requestParams['executeForm']) && is_string($this->requestParams['executeForm'])) {
			if(isset($funcName)) {
				Form::set_current_action($funcName);
			}
			
			// Get the appropraite ocntroller: sometimes we want to get a form from another controller
			if(isset($this->requestParams['formController'])) {
				$formController = Director::getControllerForURL($this->requestParams['formController']);

				while(is_a($formController, 'NestedController')) {
					$formController = $formController->getNestedController();
				}
				
			} else {
				$formController = $this;
			}

			// Create the form object
			$form = $formController;

			$formObjParts = explode('.', $this->requestParams['executeForm']);
			foreach($formObjParts as $formMethod){
				if(isset($_GET['debug_profile'])) Profiler::mark("Calling $formMethod", "on $form->class");
				$form = $form->$formMethod();
				if(isset($_GET['debug_profile'])) Profiler::unmark("Calling $formMethod", "on $form->class");
				if(!$form) break; //user_error("Form method '" . $this->requestParams['executeForm'] . "' returns null in controller class '$this->class' ($_SERVER[REQUEST_URI])", E_USER_ERROR);
			}
		
			
			// Populate the form
			if(isset($_GET['debug_profile'])) Profiler::mark("Controller", "populate form");		
			if($form){
				$form->loadDataFrom($this->requestParams, true);
				// disregard validation if a single field is called
				
				
				if(!isset($_REQUEST['action_callfieldmethod'])) {
					$valid = $form->beforeProcessing();
					if(!$valid) {
						$this->popCurrent();
						return $this->response;
					}
				}else{
					$fieldcaller = $form->dataFieldByName($requestParams['fieldName']); 
 					if(is_a($fieldcaller, "TableListField")){ 
 						if($fieldcaller->hasMethod('php')){
							$valid = $fieldcaller->php($requestParams);
							if(!$valid) exit();
 						}
					}
				}
				
				// If the action wasnt' set, choose the default on the form.
				if(!isset($funcName) && $defaultAction = $form->defaultAction()){
					$funcName = $defaultAction->actionName();
				}
				
				if(isset($funcName)) {
					$form->setButtonClicked($funcName);
				}
				
			}else{
				 user_error("No form (" . Session::get('CMSMain.currentPage') . ") returned by $formController->class->$_REQUEST[executeForm]", E_USER_WARNING);	
			}
			if(isset($_GET['debug_profile'])) Profiler::unmark("Controller", "populate form");		
			
			if(!isset($funcName)) {
				user_error("No action button has been clicked in this form executon, and no default has been allowed", E_USER_ERROR);
			}

			// First, try a handler method on the controller
			if($this->hasMethod($funcName) || !$form) {
				if(isset($_GET['debug_controller'])){
					Debug::show("Found function $funcName on the controller");
				}

				if(isset($_GET['debug_profile'])) Profiler::mark("$this->class::$funcName (controller action)");		
				$result = $this->$funcName($this->requestParams, $form);
				if(isset($_GET['debug_profile'])) Profiler::unmark("$this->class::$funcName (controller action)");		
				
			// Otherwise, try a handler method on the form object
			} else {
				if(isset($_GET['debug_controller'])) {
					Debug::show("Found function $funcName on the form object");
				}

				if(isset($_GET['debug_profile'])) Profiler::mark("$form->class::$funcName (form action)");		
				$result = $form->$funcName($this->requestParams, $form);
				if(isset($_GET['debug_profile'])) Profiler::unmark("$form->class::$funcName (form action)");		
			}

		// Normal action
		} else {
			if(!isset($funcName)) $funcName = $this->action;

			if($this->hasMethod($funcName)) {
				if(isset($_GET['debug_controller'])) Debug::show("Found function $funcName on the $this->class controller");

				if(isset($_GET['debug_profile'])) Profiler::mark("$this->class::$funcName (controller action)");		
				
				$result = $this->$funcName($this->urlParams);
				if(isset($_GET['debug_profile'])) Profiler::unmark("$this->class::$funcName (controller action)");
				
			} else {
				if(isset($_GET['debug_controller'])) Debug::show("Running default action for $funcName on the $this->class controller" );
				if(isset($_GET['debug_profile'])) Profiler::mark("Controller::defaultAction($funcName)");
				$result = $this->defaultAction($funcName, $this->urlParams);
				if(isset($_GET['debug_profile'])) Profiler::unmark("Controller::defaultAction($funcName)");		
			}
		}
		
		// If your controller function returns an array, then add that data to the
		// default template
		
		if(is_array($result)) {
			$extended = $this->customise($result);
			$viewer = $this->getViewer($funcName);
			
			$result = $viewer->process($extended);
		}

		$this->response->setBody($result);
	
		if($result) ContentNegotiator::process($this->response);
		
		// Set up HTTP cache headers
		HTTP::add_cache_headers($this->response);

		if(isset($_GET['debug_profile'])) Profiler::unmark("Controller", "run");
		
		$this->popCurrent();
		return $this->response;
	}

	function defaultAction($action) {
		return $this->getViewer($action)->process($this);
	}
	
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
	 * Call this to disable basic authentication on test sites
	 * must be called in the init() method
	 */
	function disableBasicAuth() {
		$this->basicAuthEnabled = false;
	}
	
	/**
	 * Initialisation function that is run before any action on the controller is called.
	 */
	function init() {
		// Test and development sites should be secured, via basic-auth
		if(ClassInfo::ready() && ClassInfo::hasTable("Member") && Director::isTest() && $this->basicAuthEnabled) {
			BasicAuth::requireLogin("SilverStripe test website.  Use your  CMS login", "ADMIN");
		}		
		
		//
		Cookie::set("PastVisitor", true);

		// ClassInfo::ready() called to ensure that we're not in a very-first-setup stage
		if(ClassInfo::ready() && ClassInfo::hasTable("Member") && ($member = Member::currentUser())) {
			Cookie::set("PastMember", true);
			DB::query("UPDATE Member SET LastVisited = NOW() WHERE ID = $member->ID", null);
		}
		
		// This is used to test that subordinate controllers are actually calling parent::init() - a common bug
		$this->baseInitCalled = true;
	}

	/**
	 * Deprecated - use Controller::curr() instead
	 * @returns Controller
	 */
	public static function currentController() {
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
	 */
	function LinkTo($a, $b) {
		return Director::baseURL() . $a . '/' . $b;
	}
	
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
	 * Handle redirection
	 */
	function redirect($url) {
		// Attach site-root to relative links, if they have a slash in them
		if($url == "" || $url[0] == '?' || (substr($url,0,4) != "http" && $url[0] != "/" && strpos($url,'/') !== false)){
			$url = Director::baseURL() . $url;
		}

		$this->response->redirect($url);
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
	
}

?>
