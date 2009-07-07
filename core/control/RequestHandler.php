<?php

/**
 * This class is the base class of any Sapphire object that can be used to handle HTTP requests.
 * 
 * Any RequestHandler object can be made responsible for handling its own segment of the URL namespace.
 * The {@link Director} begins the URL parsing process; it will parse the beginning of the URL to identify which
 * controller is being used.  It will then call {@link handleRequest()} on that Controller, passing it the parameters that it
 * parsed from the URL, and the {@link HTTPRequest} that contains the remainder of the URL to be parsed.
 *
 * You can use ?debug_request=1 to view information about the different components and rule matches for a specific URL.
 *
 * In Sapphire, URL parsing is distributed throughout the object graph.  For example, suppose that we have a search form
 * that contains a {@link TreeMultiSelectField} named "Groups".  We want to use ajax to load segments of this tree as they are needed
 * rather than downloading the tree right at the beginning.  We could use this URL to get the tree segment that appears underneath
 * Group #36: "admin/crm/SearchForm/field/Groups/treesegment/36"
 *  - Director will determine that admin/crm is controlled by a new ModelAdmin object, and pass control to that.
 *    Matching Director Rule: "admin/crm" => "ModelAdmin" (defined in mysite/_config.php)
 *  - ModelAdmin will determine that SearchForm is controlled by a Form object returned by $this->SearchForm(), and pass control to that.
 *    Matching $url_handlers: "$Action" => "$Action" (defined in RequestHandler class)
 *  - Form will determine that field/Groups is controlled by the Groups field, a TreeMultiselectField, and pass control to that.
 *    Matching $url_handlers: 'field/$FieldName!' => 'handleField' (defined in Form class)
 *  - TreeMultiselectField will determine that treesegment/36 is handled by its treesegment() method.  This method will return an HTML fragment that is output to the screen.
 *    Matching $url_handlers: "$Action/$ID" => "handleItem" (defined in TreeMultiSelectField class)
 *
 * {@link RequestHandler::handleRequest()} is where this behaviour is implemented.
 * 
 * @package sapphire
 * @subpackage control
 */
class RequestHandler extends ViewableData {
	protected $request = null;
	
	/**
	 * This variable records whether RequestHandler::__construct()
	 * was called or not. Useful for checking if subclasses have
	 * called parent::__construct()
	 *
	 * @var boolean
	 */
	protected $brokenOnConstruct = true;
	
	/**
	 * The default URL handling rules.  This specifies that the next component of the URL corresponds to a method to
	 * be called on this RequestHandlingData object.
	 *
	 * The keys of this array are parse rules.  See {@link HTTPRequest::match()} for a description of the rules available.
	 * 
	 * The values of the array are the method to be called if the rule matches.  If this value starts with a '$', then the
	 * named parameter of the parsed URL wil be used to determine the method name.
	 */
	static $url_handlers = array(
		'$Action' => '$Action',
	);

	
	/**
	 * Define a list of action handling methods that are allowed to be called directly by URLs.
	 * The variable should be an array of action names. This sample shows the different values that it can contain:
	 *
	 * <code>
	 * array(
	 *		'someaction', // someaction can be accessed by anyone, any time
	 *		'otheraction' => true, // So can otheraction
	 *		'restrictedaction' => 'ADMIN', // restrictedaction can only be people with ADMIN privilege
	 *		'complexaction' '->canComplexAction' // complexaction can only be accessed if $this->canComplexAction() returns true
	 *	);
	 * </code>
	 */
	static $allowed_actions = null;
	
	public function __construct() {
		$this->brokenOnConstruct = false;
		parent::__construct();
	}
	
	/**
	 * Handles URL requests.
	 *
	 *  - ViewableData::handleRequest() iterates through each rule in {@link self::$url_handlers}.
	 *  - If the rule matches, the named method will be called.
	 *  - If there is still more URL to be processed, then handleRequest() is called on the object that that method returns.
	 *
	 * Once all of the URL has been processed, the final result is returned.  However, if the final result is an array, this
	 * array is interpreted as being additional template data to customise the 2nd to last result with, rather than an object
	 * in its own right.  This is most frequently used when a Controller's action will return an array of data with which to
	 * customise the controller.
	 * 
	 * @param $params The parameters taken from the parsed URL of the parent url handler
	 * @param $request The {@link HTTPRequest} object that is reponsible for distributing URL parsing
	 * @uses HTTPRequest
	 * @uses HTTPRequest->match()
	 * @return HTTPResponse|RequestHandler|string|array
	 */
	function handleRequest($request) {
		// $handlerClass is used to step up the class hierarchy to implement url_handlers inheritance
		$handlerClass = ($this->class) ? $this->class : get_class($this);
	
		if($this->brokenOnConstruct) {
			user_error("parent::__construct() needs to be called on {$handlerClass}::__construct()", E_USER_WARNING);
		}
	
		$this->request = $request;
		
		// We stop after RequestHandler; in other words, at ViewableData
		while($handlerClass && $handlerClass != 'ViewableData') {
			$urlHandlers = Object::get_static($handlerClass, 'url_handlers');
			
			if($urlHandlers) foreach($urlHandlers as $rule => $action) {
				if(isset($_REQUEST['debug_request'])) Debug::message("Testing '$rule' with '" . $request->remaining() . "' on $this->class");
				if($params = $request->match($rule, true)) {
					// FIXME: This unnecessary coupling was added to fix a bug in Image_Uploader.
					if($this instanceof Controller) $this->urlParams = $request->allParams();
				
					if(isset($_REQUEST['debug_request'])) {
						Debug::message("Rule '$rule' matched to action '$action' on $this->class.  Latest request params: " . var_export($request->latestParams(), true));
					}
				
					// Actions can reference URL parameters, eg, '$Action/$ID/$OtherID' => '$Action',
					if($action[0] == '$') $action = $params[substr($action,1)];
				
					if($this->checkAccessAction($action)) {
						if(!$action) {
							if(isset($_REQUEST['debug_request'])) Debug::message("Action not set; using default action method name 'index'");
							$action = "index";
						} else if(!is_string($action)) {
							user_error("Non-string method name: " . var_export($action, true), E_USER_ERROR);
						} 
						$result = $this->$action($request);
					} else {
						return $this->httpError(403, "Action '$action' isn't allowed on class $this->class");
					}
				
					if($result instanceof HTTPResponse && $result->isError()) {
						if(isset($_REQUEST['debug_request'])) Debug::message("Rule resulted in HTTP error; breaking");
						return $result;
					}
				
					// If we return a RequestHandler, call handleRequest() on that, even if there is no more URL to parse.
					// It might have its own handler.  However, we only do this if we haven't just parsed an empty rule ourselves,
					// to prevent infinite loops
					if(!$request->isEmptyPattern($rule) && is_object($result) && $result instanceof RequestHandler) {
						$returnValue = $result->handleRequest($request);

						// Array results can be used to handle 
						if(is_array($returnValue)) $returnValue = $this->customise($returnValue);
					
						return $returnValue;
						
					// If we return some other data, and all the URL is parsed, then return that
					} else if($request->allParsed()) {
						return $result;
					
					// But if we have more content on the URL and we don't know what to do with it, return an error.
					} else {
						return $this->httpError(404, "I can't handle sub-URLs of a $this->class object.");
					}
				
					return $this;
				}
			}
			
			$handlerClass = get_parent_class($handlerClass);
		}
		
		// If nothing matches, return this object
		return $this;
	}
	
	/**
	 * Check that the given action is allowed to be called from a URL.
	 * It will interrogate {@link self::$allowed_actions} to determine this.
	 */
	function checkAccessAction($action) {
		$action            = strtolower($action);
		$allowedActions    = Object::combined_static(get_class($this), 'allowed_actions');
		$newAllowedActions = array();
		
		// merge in any $allowed_actions from extensions
		if($this->extension_instances) foreach($this->extension_instances as $extension) {
			if($extAccess = $extension->stat('allowed_actions')) {
				$allowedActions = array_merge($allowedActions, $extAccess);
			}
		}
		
		if($action == 'index') return true;
		
		if($allowedActions)  {
			foreach($allowedActions as $key => $value) {
				$newAllowedActions[strtolower($key)] = strtolower($value);
			}
			
			$allowedActions = $newAllowedActions;
			
			if(isset($allowedActions[$action])) {
				$test = $allowedActions[$action];
				// PHP can be loose about typing; let's give people a break if true becomes 1 or '1'
				if($test === true || $test === 1 || $test === '1') {
					return true;
				} elseif(substr($test, 0, 2) == '->') {
					return $this->{substr($test, 2)}();
				} elseif(Permission::check($test)) {
					return true;
				}
			} elseif((($key = array_search($action, $allowedActions)) !== false) && is_numeric($key)) {
				return true;
			}
		}
		
		if($allowedActions === null || !$this->uninherited('allowed_actions')) {
			// If no allowed_actions are provided, then we should only let through actions that aren't handled by magic methods
			// we test this by calling the unmagic method_exists and comparing it to the magic $this->hasMethod().  This will
			// still let through actions that are handled by templates.
			return method_exists($this, $action) || !$this->hasMethod($action);
		}
		
		return false;
	}
	
	/**
	 * Throw an HTTP error instead of performing the normal processing
	 * @todo This doesn't work properly right now. :-(
	 */
	function httpError($errorCode, $errorMessage = null) {
		$r = new HTTPResponse();
		$r->setBody($errorMessage);
		$r->setStatuscode($errorCode);
		return $r;
	}
	
	/**
	 * Returns the HTTPRequest object that this controller is using.
	 *
	 * @return HTTPRequest
	 */
	function getRequest() {
		return $this->request;
	}
}