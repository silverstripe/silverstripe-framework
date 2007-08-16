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

	protected static $currentController;
	
	protected $basicAuthEnabled = true;
	
	function setURLParams($urlParams) {
		$this->urlParams = $urlParams;
	}
	
	function getURLParams() {
		return $this->urlParams;
	}
	
	/**
	 * Execute the appropriate action handler.  If none is given, use defaultAction to display
	 * a template.  The default action will be appropriate in most cases where displaying data
	 * is the core goal; the Viewer can call methods on the controller to get the data it needs.
	 * @param array $urlParams named parameters extracted from the URL, including Action.
	 * @param array $requestParams GET and POST variables.
	 */
	protected $baseInitCalled = false;
	function run($requestParams) {
		if(isset($_GET['debug_profile'])) Profiler::mark("Controller", "run");		
	
		$this->requestParams = $requestParams;
		$this->action = isset($this->urlParams['Action']) ? str_replace("-","_",$this->urlParams['Action']) : "index";

		// Init
		$this->baseInitCalled = false;
		$this->init();
		if(!$this->baseInitCalled) user_error("init() method on class '$this->class' doesn't call Controller::init().  Make sure that you have parent::init() included.", E_USER_WARNING);
		
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
					if(!$valid) exit();
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
	
		if($result) $result = ContentNegotiator::process($result);
		
		// Set up HTTP cache headers
		HTTP::add_cache_headers();

		if(isset($_GET['debug_profile'])) Profiler::unmark("Controller", "run");
		return $result;
	}

	function defaultAction($action) {
		return $this->getViewer($action)->process($this);
	}
	
	function getAction() {
		return $this->action;
	}
	
	/**
	 * Return an SSViewer object to process the data
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
		Controller::$currentController = $this;
		
		
		// This is used to test that subordinate controllers are actually calling parent::init() - a common bug
		$this->baseInitCalled = true;
	}

	public static function currentController() {
		return Controller::$currentController;
	}

	/**
	 * Returns true if the member is allowed to do the given action.
	 * @param perm The permission to be checked, such as 'View'.
	 * @param member The member whose permissions need checking.  Defaults to the currently logged
	 * in user.
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
	 */
	function PastVisitor() {
		return Cookie::get("PastVisitor") ? true : false;
	}
	 
	/**
	 * Return true if the visitor has signed up for a login account before
	 */
	function PastMember() {
		return Cookie::get("PastMember") ? true : false;
	}
}

?>
