<?php

/**
 * @package sapphire
 * @subpackage core
 */

/**
 * Supports debugging and core error handling via static methods.
 * 
 * @package sapphire
 * @subpackage core
 */
class Debug {
	
	/**
	 * @var $custom_smtp_server string Custom mailserver for sending mails.
	 */
	protected static $custom_smtp_server = '';
	
	/**
	 * @var $send_errors_to string Email address to send error notifications
	 */
	protected static $send_errors_to;
	
	/**
	 * @var $send_warnings_to string Email address to send warning notifications
	 */
	protected static $send_warnings_to;
	
	/**
	 * Show the contents of val in a debug-friendly way.
	 * Debug::show() is intended to be equivalent to dprintr()
	 */
	static function show($val, $showHeader = true) {
		if(!Director::isLive()) {
			if($showHeader) {
				$caller = Debug::caller();
				if(Director::is_ajax())
					echo "Debug ($caller[class]$caller[type]$caller[function]() in line $caller[line] of " . basename($caller['file']) . ")\n";
				else 
					echo "<div style=\"background-color: white; text-align: left; width: 50em\">\n<hr>\n<h3>Debug <span style=\"font-size: 65%\">($caller[class]$caller[type]$caller[function]() \n<span style=\"font-weight:normal\">in line</span> $caller[line] \n<span style=\"font-weight:normal\">of</span> " . basename($caller['file']) . ")</span>\n</h3>\n";
			}
			
			echo Debug::text($val);
	
			if(!Director::is_ajax()) echo "</div>";
		}

	}
	
	/**
	 * Emails the contents of the output buffer
	 */
	static function mailBuffer( $email, $subject ) {
		mail( $email, $subject, ob_get_contents() );
		ob_end_clean();
	}  
	
	static function endshow($val) {
		if(!Director::isLive()) {
			$caller = Debug::caller();
			echo "<hr>\n<h3>Debug \n<span style=\"font-size: 65%\">($caller[class]$caller[type]$caller[function]() \n<span style=\"font-weight:normal\">in line</span> $caller[line] \n<span style=\"font-weight:normal\">of</span> " . basename($caller['file']) . ")</span>\n</h3>\n";
			echo Debug::text($val);
			die();
		}
	}

	static function text($val) {
		if(is_object($val)) {
			if(method_exists($val, 'hasMethod')) {
				$hasDebugMethod = $val->hasMethod('debug');
			} else {
				$hasDebugMethod = method_exists($val, 'debug');
			}
			
			if($hasDebugMethod) {
				return $val->debug();
			}
		}

		if(is_array($val)) {
			$result = "<ul>\n";
			foreach($val as $k => $v) {
				$result .= "<li>$k = " . Debug::text($v) . "</li>\n";
			}
			$val = $result . "</ul>\n";

		} else if (is_object($val)) {
			$val = var_export($val, true);
		} else {
			if(true || !Director::is_ajax()) {
				$val = "<pre style=\"font-family: Courier new\">" . htmlentities($val) . "</pre>\n";
			}
		}

		return $val;
	}

	/**
	 * Show a debugging message
	 */
	static function message($message, $showHeader = true) {
		if(!Director::isLive()) {
			$caller = Debug::caller();
			$file = basename($caller['file']);
			echo "<p style=\"background-color: white; color: black; width: 95%; margin: 0.5em; padding: 0.3em; border: 1px #CCC solid\">\n";
			if($showHeader) echo "<b>Debug (line $caller[line] of $file):</b>\n ";
			echo Convert::raw2xml(trim($message)) . "</p>\n";
		}
	}

	/**
	 * Load an error handler
	 * 
	 * @todo why does this delegate to loadFatalErrorHandler?
	 */
	static function loadErrorHandlers() {
		Debug::loadFatalErrorHandler();
	}

	/**
	 * @todo can this be moved into loadErrorHandlers?
	 */
	static function loadFatalErrorHandler() {
		set_error_handler('errorHandler', (E_ALL ^ E_NOTICE) ^ E_USER_NOTICE);
		set_exception_handler('exceptionHandler');
	}

	static function warningHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	  if(error_reporting() == 0) return;
		if(self::$send_warnings_to) self::emailError(self::$send_warnings_to, $errno, $errstr, $errfile, $errline, $errcontext, "Warning");

		if(Director::isDev()) {
		  self::showError($errno, $errstr, $errfile, $errline, $errcontext);
		}
	}

	static function fatalHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if(self::$send_errors_to) self::emailError(self::$send_errors_to, $errno, $errstr, $errfile, $errline, $errcontext, "Error");

		if(Director::isDev()) {
			Debug::showError($errno, $errstr, $errfile, $errline, $errcontext);

		} else {
			Debug::friendlyError($errno, $errstr, $errfile, $errline, $errcontext);
		}
		die();
	}
	static function friendlyError($errno, $errstr, $errfile, $errline, $errcontext) {
		header("HTTP/1.0 500 Internal server error");

		if(Director::is_ajax()) {
			echo "There has been an error";

		} else {
			if(file_exists('../assets/error-500.html')) {
				include('../assets/error-500.html');
			} else {
				echo "<h1>Error</h1><p>The website server has not been able to respond to your request.</p>\n";
			}
		}
	}

	static function showError($errno, $errstr, $errfile, $errline, $errcontext) {
		if(!headers_sent()) header("HTTP/1.0 500 Internal server error");

		if(Director::is_ajax()) {
			echo "ERROR:Error $errno: $errstr\n At l$errline in $errfile\n";
			Debug::backtrace();

		} else {
			echo "<div style=\"border: 5px red solid\">\n";
			echo "<p style=\"color: white; background-color: red; margin: 0\">FATAL ERROR: $errstr<br />\n At line $errline in $errfile<br />\n<br />\n</p>\n";

			Debug::backtrace();
			//Debug::show(debug_backtrace());

			echo "<h2>Context</h2>\n";
			Debug::show($errcontext);

			echo "</div>\n";
		}
	}

	static function emailError($emailAddress, $errno, $errstr, $errfile, $errline, $errcontext, $errorType = "Error") {
		if(strtolower($errorType) == 'warning') {
			$colour = "orange";
		} else {
			$colour = "red";
		}

		$data = "<div style=\"border: 5px $colour solid\">\n";
		$data .= "<p style=\"color: white; background-color: $colour; margin: 0\">$errorType: $errstr<br /> At line $errline in $errfile\n<br />\n<br />\n</p>\n";

		$data .= Debug::backtrace(true);
		$data .= "</div>\n";

		// override smtp-server if needed			
		if(self::$custom_smtp_server) {
			ini_set("SMTP", self::$custom_smtp_server);			
		}

		$relfile = Director::makeRelative($errfile);
		if($relfile[0] == '/') $relfile = substr($relfile,1);
		mail($emailAddress, "$errorType at $relfile line $errline (http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI])", $data, "Content-type: text/html\nFrom: errors@silverstripe.com");
	}
	
	/**
	 * @param string $server IP-Address or domain
	 */
	static function set_custom_smtp_server($server) {
		self::$custom_smtp_server = $server;
	}

	/**
	 * @return string
	 */
	static function get_custom_smtp_server() {
		return self::$custom_smtp_server;
	}
	
	/**
	 * Send errors to the given email address.
	 * Can be used like so:
	 * if(Director::isLive()) Debug::send_errors_to("sam@silverstripe.com");
	 * @param emailAddress The email address to send them to
	 * @param sendWarnings Set to true to send warnings as well as errors.
	 */
	static function send_errors_to($emailAddress, $sendWarnings = true) {
		self::$send_errors_to = $emailAddress;
		self::$send_warnings_to = $sendWarnings ? $emailAddress : null;
	}
	
	/**
	 * @return string
	 */
	static function get_send_errors_to() {
		return self::$send_errors_to;
	}

	/**
	 * @return string
	 */
	static function get_send_warnings_to() {
		return self::$send_warnings_to;
	}

	/**
	 * Deprecated.  Send live errors and warnings to the given address.
	 * @deprecated Use send_errors_to() instead.
	 */
	static function sendLiveErrorsTo($emailAddress) {
		user_error('Debug::sendLiveErrorsTo() is deprecated. Use Debug::send_errors_to() instead.', E_USER_NOTICE);
		if(!Director::isDev()) self::send_errors_to($emailAddress, true);
	}
	
	static function caller() {
		$bt = debug_backtrace();
		$caller = $bt[2];
		$caller['line'] = $bt[1]['line'];
		$caller['file'] = $bt[1]['file'];
		if(!isset($caller['class'])) $caller['class'] = '';
		if(!isset($caller['type'])) $caller['type'] = '';
		return $caller;
	}
	
	static function backtrace($returnVal = false, $ignoreAjax = false) {

		$bt = debug_backtrace();

		// Ingore functions that are plumbing of the error handler
		$ignoredFunctions = array('Debug::emailError','Debug::warningHandler','Debug::fatalHandler','errorHandler','Debug::showError','Debug::backtrace', 'exceptionHandler');
		while( $bt && in_array(self::full_func_name($bt[0]), $ignoredFunctions) ) {
			array_shift($bt);
		}
		
		$result = "";
		foreach($bt as $item) {
			if(Director::is_ajax() && !$ignoreAjax) {
				$result .= self::full_func_name($item,true) . "\n";
				$result .= "line $item[line] of " . basename($item['file']) . "\n\n";
			} else {
				$result .= "<p><b>" . self::full_func_name($item,true) . "</b>\n<br />\n";
				$result .= isset($item['line']) ? "line $item[line] of " : '';
				$result .=  isset($item['file']) ? basename($item['file']) : ''; 
				$result .= "</p>\n";
			}
		}

		if ($returnVal) {
			return $result;
		} else {
			echo $result;
		}
	}
	
	/**
	 * Return the full function name.  If showArgs is set to true, a string representation of the arguments will be shown
	 */
	static function full_func_name($item, $showArgs = false) {
		$funcName = '';
		if(isset($item['class'])) $funcName .= $item['class'];
		if(isset($item['type'])) $funcName .= $item['type'];
		if(isset($item['function'])) $funcName .= $item['function'];
		
		if($showArgs && isset($item['args'])) {
			$args = array();
			foreach($item['args'] as $arg) {
				if(!is_object($arg) || method_exists($arg, '__toString')) {
					$args[] = (string) $arg;
				} else {
					$args[] = get_class($arg);
				}
			}
		
			$funcName .= "(" . implode(",", $args)  .")";
		}
		
		return $funcName;
	}
	
	/**
	 * Check if the user has permissions to run URL debug tools,
	 * else redirect them to log in.
	 */
	static function require_developer_login() {
		if(Director::isDev())	{
			return;
		}
		if(isset($_SESSION['loggedInAs'])) {
			// We have to do some raw SQL here, because this method is called in Object::defineMethods().
			// This means we have to be careful about what objects we create, as we don't want Object::defineMethods()
			// being called again.
			// This basically calls Permission::checkMember($_SESSION['loggedInAs'], 'ADMIN');
			
			$memberID = $_SESSION['loggedInAs'];
			
			$groups = DB::query("SELECT GroupID from Group_Members WHERE MemberID=" . $memberID);
			$groupCSV = implode($groups->column(), ',');
			
			$permission = DB::query("
				SELECT ID
				FROM Permission
				WHERE (
					Code = 'ADMIN'
					AND Type = " . Permission::GRANT_PERMISSION . "
					AND GroupID IN ($groupCSV)
				)
			")->value();
			
			if($permission) {
				return;
			}
		}
		
		// This basically does the same as
		// Security::permissionFailure(null, "You need to login with developer access to make use of debugging tools.");
		// We have to do this because of how early this method is called in execution.
		$_SESSION['Security']['Message']['message'] = "You need to login with developer access to make use of debugging tools.";
		$_SESSION['Security']['Message']['type'] =  'warning';
		$_SESSION['BackURL'] = $_SERVER['REQUEST_URI'];
		header("HTTP/1.1 302 Found");
		header("Location: " . Director::baseURL() . "Security/login");
		die();
	}
}

function exceptionHandler($exception) {
	$errno = E_USER_ERROR;
	$type = get_class($exception);
	$message = "Uncaught " . $type . ": " . $exception->getMessage();
	$file = $exception->getFile();
	$line = $exception->getLine();
	$context = $exception->getTrace();
	Debug::fatalHandler($errno, $message, $file, $line, $context);
}

function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	switch($errno) {
		case E_ERROR:
		case E_CORE_ERROR:
		case E_USER_ERROR:
			Debug::fatalHandler($errno, $errstr, $errfile, $errline, $errcontext);
			break;

		case E_WARNING:
		case E_CORE_WARNING:
		case E_USER_WARNING:
			Debug::warningHandler($errno, $errstr, $errfile, $errline, $errcontext);
			break;
	}
}
?>
