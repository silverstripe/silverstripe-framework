<?php

/**
 * This class is the base class of any Sapphire object that can be used to handle HTTP requests.
 * 
 * Any RequestHandlingData object can be made responsible for handling its own segment of the URL namespace.
 * The {@link Director} begins the URL parsing process; it will parse the beginning of the URL to identify which
 * controller is being used.  It will then call handleRequest on that Controller, passing it the parameters that it
 * parsed from the URL, and the HTTPRequest that contains the remainder of the URL to be parsed.
 *
 * In Sapphire, URL parsing is distributed throughout the object graph.  For example, suppose that we have a search form
 * that contains a {@link TreeMultiSelectField}, Groups.  We want to use ajax to load segments of this tree as they are needed
 * rather than downloading the tree right at the beginning.  We could use this URL to get the tree segment that appears underneath
 * Group #36:
 * 
 * admin/crm/SearchForm/fields/Groups/treesegment/36
 * 
 *  - Director will determine that admin/crm is controlled by a new ModelAdmin object, and pass control to that.
 *  - ModelAdmin will determine that SearchForm is controlled by a Form object returned by $this->SearchForm(), and pass control to that.
 *  - Form will determine that fields/Groups is controlled by the Groups field, a TreeMultiselectField, and pass control to that.
 *  - TreeMultiselectField will determine that treesegment/36 is handled by its treesegment() method.  This method will return an HTML fragment that is output to the screen.
 *
 * {@link RequestHandlingData::handleRequest()} is where this behaviour is implemented.
 */
class RequestHandlingData extends ViewableData {
	protected $request = null;
	
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
	 * @return HTTPResponse|RequestHandlingData|string|array
	 */
	function handleRequest($request) {
		$this->request = $request;
		
		foreach($this->stat('url_handlers') as $rule => $action) {
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
				
				// If we return a RequestHandlingData, call handleRequest() on that, even if there is no more URL to parse.
				// It might have its own handler.  However, we only do this if we haven't just parsed an empty rule ourselves,
				// to prevent infinite loops
				if(!$request->isEmptyPattern($rule) && is_object($result) && $result instanceof RequestHandlingData) {
					$returnValue = $result->handleRequest($request);

					// Array results can be used to handle 
					if(is_array($returnValue)) $returnValue = $this->customise($returnValue);
					
					return $returnValue;
						
				// If we return some other data, and all the URL is parsed, then return that
				} else if($request->allParsed()) {
					return $result;
					
				// But if we have more content on the URL and we don't know what to do with it, return an error.
				} else {
					return $this->httpError(400, "I can't handle sub-URLs of a $this->class object.");
				}
				
				break;
			}
		}
		
		// If nothing matches, return this object
		return $this;
	}

	/**
	 * Check that the given action is allowed to be called from a URL.
	 * It will interrogate {@link self::$allowed_actions} to determine this.
	 */
	function checkAccessAction($action) {
		// Collate self::$allowed_actions from this class and all parent classes
		$access = null;
		$className = $this->class;
		while($className != 'RequestHandlingData') {
			// Merge any non-null parts onto $access.
			$accessPart = eval("return $className::\$allowed_actions;");
			if($accessPart !== null) $access = array_merge((array)$access, $accessPart);
			
			// Build an array of parts for checking if part[0] == part[1], which means that this class doesn't directly define it.
			$accessParts[] = $accessPart;
			
			$className = get_parent_class($className);
		}
		
		// Add $allowed_actions from extensions
		if($this->extension_instances) {
			foreach($this->extension_instances as $inst) {
				$accessPart = $inst->stat('allowed_actions');
				if($accessPart !== null) $access = array_merge((array)$access, $accessPart);
			}
		}
		
		if($access === null || (isset($accessParts[1]) && $accessParts[0] === $accessParts[1])) {
			// user_error("Deprecated: please define static \$allowed_actions on your Controllers for security purposes", E_USER_NOTICE);
			return true;
		}
		
		if($action == 'index') return true;
		
		// Make checkAccessAction case-insensitive
		$action = strtolower($action);
		foreach($access as $k => $v) $newAccess[strtolower($k)] = strtolower($v);
		$access = $newAccess;
				
		if(isset($access[$action])) {
			$test = $access[$action];
			if($test === true) return true;
			if(substr($test,0,2) == '->') {
				$funcName = substr($test,2);
				return $this->$funcName();
			}
			if(Permission::check($test)) return true;
		} else if((($key = array_search($action, $access)) !== false) && is_numeric($key)) {
			return true;
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