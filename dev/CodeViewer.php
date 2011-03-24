<?php
/**
 * Allows human reading of a test in a format suitable for agile documentation
 * 
 * @package sapphire
 * @subpackage tools
 */
class CodeViewer extends Controller {
	
	public static $url_handlers = array(
		''       => 'browse',
		'$Class' => 'viewClass'
	);
	
	static $allowed_actions = array(
		'index',
		'browse',
		'viewClass'
	);
	
	/**
	 * Define a simple finite state machine.
	 * Top keys are the state names.  'start' is the first state, and 'die' is the error state.
	 * Inner keys are token names/codes.  The values are either a string, new state, or an array(new state, handler method).
	 * The handler method will be passed the PHP token as an argument, and is expected to populate a property of the object.
	 */
	static $fsm = array(
		'start' => array(
			T_CLASS => array('className','createClass'),
			T_DOC_COMMENT => array('', 'saveClassComment'),
		),
		'className' => array(
			T_STRING => array('classSpec', 'setClassName'),
		),
		'classSpec' => array(
			'{' => 'classBody',
		),
		'classBody' => array(
			T_FUNCTION => array('methodName','createMethod'),
			'}' => array('start', 'completeClass'),
			T_DOC_COMMENT => array('', 'saveMethodComment'),
		),
		'methodName' => array(
			T_STRING => array('methodSpec', 'setMethodName'),
		),
		'methodSpec' => array(
			'{' => 'methodBody',
		),
		'methodBody' => array(
			'{' => array('!push','appendMethodContent'),
			'}' => array(
				'hasstack' => array('!pop', 'appendMethodContent'),
				'nostack' => array('classBody', 'completeMethod'),
			),
			T_VARIABLE => array('variable', 'potentialMethodCall'),
			T_COMMENT => array('', 'appendMethodComment'),
			T_DOC_COMMENT => array('', 'appendMethodComment'),
			'*' => array('', 'appendMethodContent'),
		),
		'variable' => array(
			T_OBJECT_OPERATOR => array('variableArrow', 'potentialMethodCall'),
			'*' => array('methodBody', 'appendMethodContent'),
		),
		'variableArrow' => array(
			T_STRING => array('methodOrProperty', 'potentialMethodCall'),
			T_WHITESPACE => array('', 'potentialMethodCall'),
			'*' => array('methodBody', 'appendMethodContent'),
		),
		'methodOrProperty' => array(
			'(' => array('methodCall', 'potentialMethodCall'),
			T_WHITESPACE => array('', 'potentialMethodCall'),
			'*' => array('methodBody', 'appendMethodContent'),
		),
		'methodCall' => array(
			'(' => array('!push/nestedInMethodCall', 'potentialMethodCall'),
			')' => array('methodBody', 'completeMethodCall'),
			'*' => array('', 'potentialMethodCall'),
		),
		'nestedInMethodCall' => array(
			'(' => array('!push', 'potentialMethodCall'),
			')' => array('!pop', 'potentialMethodCall'),
			'*' => array('', 'potentialMethodCall'),
		),
	);
	
	function init() {
		parent::init();
		
		if(!Permission::check('ADMIN')) return Security::permissionFailure();
		ManifestBuilder::load_test_manifest();
	}
	
	public function browse() {
		$classes = ClassInfo::subclassesFor('SapphireTest');
		
		array_shift($classes);
		ksort($classes);
		
		$result  ='<h1>View any of the following test classes</h1>';
		$result .='<ul>';
		foreach($classes as $class) {
			$result .="<li><a href=\"{$this->Link($class)}\">$class</a></li>";
		}
		$result .='</ul>';
		
		$result .='<h1>View any of the following other classes</h1>';
		
		$classes = array_keys(ClassInfo::allClasses());
		sort($classes);
		
		$result .='<ul>';
		foreach($classes as $class) {
			$result .="<li><a href=\"{$this->Link($class)}\">$class</a></li>";
		}
		$result .='</ul>';
		
		return $this->customise(array (
			'Content' => $result
		))->renderWith('CodeViewer');
	}
	
	public function viewClass(SS_HTTPRequest $request) {
		$class = $request->param('Class');
		
		if(!class_exists($class)) {
			throw new Exception('CodeViewer->viewClass(): not passed a valid class to view (does the class exist?)');
		}
		
		return $this->customise(array (
			'Content' => $this->testAnalysis(getClassFile($class))
		))->renderWith('CodeViewer');
	}
	
	public function Link($action = null) {
		return Controller::join_links(Director::absoluteBaseURL(), 'dev/viewcode/', $action);
	}
	
	protected $classComment, $methodComment;

	function saveClassComment($token) {
		$this->classComment = $this->parseComment($token);
	}
	function saveMethodComment($token) {
		$this->methodComment = $this->parseComment($token);
	}

	function createClass($token) {
		$this->currentClass = array(
			"description" => $this->classComment['pretty'],
			"heading" => isset($this->classComment['heading']) ? $this->classComment['heading'] : null,
		);
		$ths->classComment = null;
	}
	function setClassName($token) {
		$this->currentClass['name'] = $token[1];
		if(!$this->currentClass['heading']) $this->currentClass['heading'] = $token[1];
	}
	function completeClass($token) {
		$this->classes[] = $this->currentClass;
	}
	
	function createMethod($token) {
		$this->currentMethod = array();
		$this->currentMethod['content'] = "<pre>";
		$this->currentMethod['description'] = $this->methodComment['pretty'];
		$this->currentMethod['heading'] = isset($this->methodComment['heading']) ? $this->methodComment['heading'] : null;
		$this->methodComment = null;

	}
	function setMethodName($token) {
		$this->currentMethod['name'] = $token[1];
		if(!$this->currentMethod['heading']) $this->currentMethod['heading'] = $token[1];
	}
	function appendMethodComment($token) {
		if(substr($token[1],0,2) == '/*') {
			$this->closeOffMethodContentPre();
			$this->currentMethod['content'] .= $this->prettyComment($token) . "<pre>";
		} else {
			$this->currentMethod['content'] .= $this->renderToken($token);
		}
	} 

	function prettyComment($token) {
		$comment = preg_replace('/^\/\*/','',$token[1]);
		$comment = preg_replace('/\*\/$/','',$comment);
		$comment = preg_replace('/(^|\n)[\t ]*\* */m',"\n",$comment);
		$comment = htmlentities($comment, ENT_COMPAT, 'UTF-8');
		$comment = str_replace("\n\n", "</p><p>", $comment);
		return "<p>$comment</p>";
	}

	function parseComment($token) {
		$parsed = array();		

		$comment = preg_replace('/^\/\*/','',$token[1]);
		$comment = preg_replace('/\*\/$/','',$comment);
		$comment = preg_replace('/(^|\n)[\t ]*\* */m',"\n",$comment);
		
		foreach(array('heading','nav') as $var) {
			if(preg_match('/@' . $var . '\s+([^\n]+)\n/', $comment, $matches)) {
				$parsed[$var] = $matches[1];
				$comment = preg_replace('/@' . $var . '\s+([^\n]+)\n/','', $comment);
			}
		}
		
		$parsed['pretty'] = "<p>" . str_replace("\n\n", "</p><p>", htmlentities($comment, ENT_COMPAT, 'UTF-8')). "</p>";
		return $parsed;
	}
	
	protected $isNewLine = true;

	function appendMethodContent($token) {
		if($this->potentialMethodCall) {
			$this->currentMethod['content'] .= $this->potentialMethodCall;
			$this->potentialMethodCall = "";
		}
		//if($this->isNewLine && isset($token[2])) $this->currentMethod['content'] .= $token[2] . ": ";
		$this->isNewLine = false;
		$this->currentMethod['content'] .= $this->renderToken($token);
	}
	function completeMethod($token) {
		$this->closeOffMethodContentPre();
		$this->currentMethod['content'] = str_replace("\n\t\t","\n",$this->currentMethod['content']);
		$this->currentClass['methods'][] = $this->currentMethod;
	}
	
	protected $potentialMethodCall = "";
	function potentialMethodCall($token) {
		$this->potentialMethodCall .= $this->renderToken($token);
	}
	function completeMethodCall($token) {
		$this->potentialMethodCall .= $this->renderToken($token);
		if(strpos($this->potentialMethodCall, '-&gt;</span><span class="T_STRING">assert') !== false) {
			$this->currentMethod['content'] .= "<strong>" . $this->potentialMethodCall . "</strong>";
		} else {
			$this->currentMethod['content'] .= $this->potentialMethodCall;
		}
		$this->potentialMethodCall = "";
	}
	
	/**
	 * Finish the "pre" block in method content.
	 * Will remove whitespace and empty "pre" blocks
	 */
	function closeOffMethodContentPre() {
		$this->currentMethod['content'] = trim($this->currentMethod['content']);
		if(substr($this->currentMethod['content'],-5) == '<pre>') $this->currentMethod['content'] = substr($this->currentMethod['content'], 0,-5);
		else $this->currentMethod['content'] .= '</pre>';
	}
		
	/**
	 * Render the given token as HTML
	 */
	function renderToken($token) {
		$tokenContent = htmlentities(
			is_array($token) ? $token[1] : $token, 
			ENT_COMPAT, 
			'UTF-8'
		);
		$tokenName = is_array($token) ? token_name($token[0]) : 'T_PUNCTUATION';

		switch($tokenName) {
			case "T_WHITESPACE":
				if(strpos($tokenContent, "\n") !== false) $this->isNewLine = true;
				return $tokenContent;
			default:
				return "<span class=\"$tokenName\">$tokenContent</span>";
		}
	}
	
	protected $classes = array();
	protected $currentMethod, $currentClass;
	
	function testAnalysis($file) {
		$content = file_get_contents($file);
		$tokens = token_get_all($content);
		
		// Execute a finite-state-machine with a built-in state stack
		// This FSM+stack gives us enough expressive power for simple PHP parsing
		$state = "start";
		$stateStack = array();
		
		//echo "<li>state $state";
		foreach($tokens as $token) {
			// Get token name - some tokens are arrays, some arent'
			if(is_array($token)) $tokenName = $token[0]; else $tokenName = $token;
			//echo "<li>token '$tokenName'";
			
			// Find the rule for that token in the current state
			if(isset(self::$fsm[$state][$tokenName])) $rule = self::$fsm[$state][$tokenName];
			else if(isset(self::$fsm[$state]['*'])) $rule = self::$fsm[$state]['*'];
			else $rule = null;
			
			// Check to see if we have specified multiple rules depending on whether the stack is populated	
			if(is_array($rule) && array_keys($rule) == array('hasstack', 'nostack')) {
				if($stateStack) $rule = $rule['hasstack'];
				else $rule = $rule = $rule['nostack'];
			}
			
			if(is_array($rule)) {
				list($destState, $methodName) = $rule;
				$this->$methodName($token);
			} else if($rule) {
				$destState = $rule;
			} else {
				$destState = null;
			}
			//echo "<li>->state $destState";

			if(preg_match('/!(push|pop)(\/[a-zA-Z0-9]+)?/', $destState, $parts)) {
				$action = $parts[1];
				$argument = isset($parts[2]) ? substr($parts[2],1) : null;
				$destState = null;
				
				switch($action) {
					case "push":
						$stateStack[] = $state;
						if($argument) $destState = $argument;
						break;
					
					case "pop":
						if($stateStack) $destState = array_pop($stateStack);
						else if($argument) $destState = $argument;
						else user_error("State transition '!pop' was attempted with an empty state-stack and no default option specified.", E_USER_ERROR);
				}
			}
			
			if($destState) $state = $destState;
			if(!isset(self::$fsm[$state])) user_error("Transition to unrecognised state '$state'", E_USER_ERROR);
		}
		
		$subclasses = ClassInfo::subclassesFor('SapphireTest');
		foreach($this->classes as $classDef) {
			if(true ||in_array($classDef['name'], $subclasses)) {
				echo "<h1>$classDef[heading]</h1>";
				echo "<div style=\"font-weight: bold\">$classDef[description]</div>";
				if(isset($classDef['methods'])) foreach($classDef['methods'] as $method) {
					if(true || substr($method['name'],0,4) == 'test') {
						//$title = ucfirst(strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', substr($method['name'], 4))));
						$title = $method['heading'];

						echo "<h2>$title</h2>";
						echo "<div style=\"font-weight: bold\">$method[description]</div>";
						echo $method['content'];
					}
				}
			}
			
		}
	}
}