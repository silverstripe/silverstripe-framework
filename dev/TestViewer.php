<?php
/**
 * Allows human reading of a test in a format suitable for agile documentation
 * @package sapphire
 * @subpackage testing
 */
class TestViewer extends Controller {
	/**
	 * Define a simple finite state machine.
	 * Top keys are the state names.  'start' is the first state, and 'die' is the error state.
	 * Inner keys are token names/codes.  The values are either a string, new state, or an array(new state, handler method).
	 * The handler method will be passed the PHP token as an argument, and is expected to populate a property of the object.
	 */
	static $fsm = array(
		'start' => array(
			T_CLASS => array('className','createClass'),
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
		
		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);
	}

	function createClass($token) {
		$this->currentClass = array();
	}
	function setClassName($token) {
		$this->currentClass['name'] = $token[1];
	}
	function completeClass($token) {
		$this->classes[] = $this->currentClass;
	}
	
	function createMethod($token) {
		$this->currentMethod = array();
		$this->currentMethod['content'] = "<pre>";
	}
	function setMethodName($token) {
		$this->currentMethod['name'] = $token[1];
	}
	function appendMethodComment($token) {
		if(substr($token[1],0,2) == '/*') {
			$comment = preg_replace('/^\/\*/','',$token[1]);
			$comment = preg_replace('/\*\/$/','',$comment);
			$comment = preg_replace('/\n[\t ]*\* */m',"\n",$comment);
			
			$this->closeOffMethodContentPre();
			$this->currentMethod['content'] .= "<p>$comment</p><pre>";
		} else {
			$this->currentMethod['content'] .= $this->renderToken($token);
		}
		
	} 
	function appendMethodContent($token) {
		if($this->potentialMethodCall) {
			$this->currentMethod['content'] .= $this->potentialMethodCall;
			$this->potentialMethodCall = "";
		}
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
		$tokenContent = htmlentities(is_array($token) ? $token[1] : $token);
		$tokenName = is_array($token) ? token_name($token[0]) : 'T_PUNCTUATION';

		switch($tokenName) {
			case "T_WHITESPACE":
				return $tokenContent;
			default:
				return "<span class=\"$tokenName\">$tokenContent</span>";
		}
	}
	
	protected $classes = array();
	protected $currentMethod, $currentClass;
	
	function Content() {
		$className = $this->urlParams['ID'];
		if($className && ClassInfo::exists($className)) {
			return $this->testAnalysis(getClassFile($className));
		} else {
			$result = "<h1>View any of the following test classes</h1>";
			$classes = ClassInfo::subclassesFor('SapphireTest');
			ksort($classes);
			foreach($classes as $className) {
				if($className == 'SapphireTest') continue;
				$result .= "<li><a href=\"TestViewer/show/$className\">$className</a></li>";
			}
			return $result;
		}
	}
		
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
			if(in_array($classDef['name'], $subclasses)) {
				echo "<h1>$classDef[name]</h1>";
				if($classDef['methods']) foreach($classDef['methods'] as $method) {
					if(substr($method['name'],0,4) == 'test') {
						//$title = ucfirst(strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', substr($method['name'], 4))));
						$title = $method['name'];

						echo "<h2>$title</h2>";
						echo $method['content'];
					}
				}
			}
			
		}
	}
}