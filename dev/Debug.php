<?php

/**
 * @package sapphire
 * @subpackage core
 */

/**
 * Supports debugging and core error handling.
 * 
 * Attaches custom methods to the default 
 * error handling hooks in PHP. Currently, three levels
 * of error are supported:
 * 
 * - Notice
 * - Warning
 * - Error
 * 
 * Notice level errors are currently unsupported, and will be passed
 * directly to the normal PHP error output.
 * 
 * Uncaught exceptions are currently passed to the debug
 * reporter as standard PHP errors.
 * 
 * There are four different types of error handler supported by the
 * Debug class:
 * 
 * - Friendly
 * - Fatal
 * - Logger
 * - Emailer
 * 
 * Currently, only Friendly, Fatal, and Emailer handlers are implemented.
 * 
 * @todo port header/footer wrapping code to external reporter class
 * @todo add support for user defined config: Debug::die_on_notice(true | false)
 * @todo add appropriate handling for E_NOTICE and E_USER_NOTICE levels
 * @todo better way of figuring out the error context to display in highlighted source
 * @todo implement error logger handler
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
					echo "<div style=\"background-color: white; text-align: left;\">\n<hr>\n<h3>Debug <span style=\"font-size: 65%\">($caller[class]$caller[type]$caller[function]() \n<span style=\"font-weight:normal\">in line</span> $caller[line] \n<span style=\"font-weight:normal\">of</span> " . basename($caller['file']) . ")</span>\n</h3>\n";
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
	
	/**
	 * Close out the show dumper
	 *
	 * @param mixed $val
	 */
	static function endshow($val) {
		if(!Director::isLive()) {
			$caller = Debug::caller();
			echo "<hr>\n<h3>Debug \n<span style=\"font-size: 65%\">($caller[class]$caller[type]$caller[function]() \n<span style=\"font-weight:normal\">in line</span> $caller[line] \n<span style=\"font-weight:normal\">of</span> " . basename($caller['file']) . ")</span>\n</h3>\n";
			echo Debug::text($val);
			die();
		}
	}
	
	/**
	 * Quick dump of a variable.
	 *
	 * @param mixed $val
	 */
	static function dump($val) {
		echo '<pre style="background-color:#ccc;padding:5px;">';
		$caller = Debug::caller();
		echo "<span style=\"font-size: 60%\">Line $caller[line] of " . basename($caller['file']) . "</span>\n";
		print_r($val);
		echo '</pre>';
	}

	/**
	 * ??
	 *
	 * @param unknown_type $val
	 * @return unknown
	 */
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
			if(Director::is_cli()) {
				if($showHeader) echo "Debug (line $caller[line] of $file):\n ";
				echo trim($message) . "\n";
			} else {
				echo "<p style=\"background-color: white; color: black; width: 95%; margin: 0.5em; padding: 0.3em; border: 1px #CCC solid\">\n";
				if($showHeader) echo "<b>Debug (line $caller[line] of $file):</b>\n ";
				echo Convert::raw2xml(trim($message)) . "</p>\n";
			}
		}
	}

	/**
	 * Load error handlers into environment
	 */
	static function loadErrorHandlers() {
		//set_error_handler('errorHandler', (E_ALL ^ E_NOTICE) ^ E_USER_NOTICE);
		set_error_handler('errorHandler', E_ALL);
		set_exception_handler('exceptionHandler');
	}

	/**
	 * Handle a non-fatal warning error thrown by PHP interpreter.
	 *
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * @param unknown_type $errcontext
	 */
	static function warningHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	  if(error_reporting() == 0) return;
		if(self::$send_warnings_to) self::emailError(self::$send_warnings_to, $errno, $errstr, $errfile, $errline, $errcontext, "Warning");

		if(Director::isDev()) {
		  self::showError($errno, $errstr, $errfile, $errline, $errcontext);
		}
	}

	/**
	 * Handle a fatal error, depending on the mode of the site (ie: Dev, Test, or Live).
	 * 
	 * Runtime execution dies immediately once the error is generated.
	 *
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * @param unknown_type $errcontext
	 */
	static function fatalHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if(self::$send_errors_to) self::emailError(self::$send_errors_to, $errno, $errstr, $errfile, $errline, $errcontext, "Error");

		if(Director::isDev()) {
			Debug::showError($errno, $errstr, $errfile, $errline, $errcontext);

		} else {
			Debug::friendlyError($errno, $errstr, $errfile, $errline, $errcontext);
		}
		die();
	}
	
	/**
	 * Render a user-facing error page, using the default HTML error template
	 * if it exists.
	 *
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * @param unknown_type $errcontext
	 */
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

	/**
	 * Render a developer facing error page, showing the stack trace and details
	 * of the code where the error occured.
	 *
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * @param unknown_type $errcontext
	 */
	static function showError($errno, $errstr, $errfile, $errline, $errcontext) {
		if(!headers_sent()) header("HTTP/1.0 500 Internal server error");
		if(Director::is_ajax()) {
			echo "ERROR:Error $errno: $errstr\n At l$errline in $errfile\n";
			Debug::backtrace();
		} else {
			$reporter = new DebugReporter();
			$reporter->writeHeader();
			echo '<div class="info">';
			echo "<h1>" . strip_tags($errstr) . "</h1>";
			echo "<h3>{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}</h3>";
			echo "<p>Line <strong>$errline</strong> in <strong>$errfile</strong></p>";
			echo '</div>';
			echo '<div class="trace"><h3>Source</h3>';
			Debug::showLines($errfile, $errline);
			echo '<h3>Trace</h3>';
			Debug::backtrace();
			echo '</div>';
			$reporter->writeFooter();
			die();
		}
	}
	
	/**
	 * Utility method to render a snippet of PHP source code, from selected file
	 * and highlighting the given line number.
	 *
	 * @param string $errfile
	 * @param int $errline
	 */
	static function showLines($errfile, $errline) {
		$lines = file($errfile);
		$offset = $errline-10;
		$lines = array_slice($lines, $offset, 16);
		echo '<pre>';
		$offset++;
		foreach($lines as $line) {
			$line = htmlentities($line);
			if ($offset == $errline) {
				echo "<span>$offset</span> <span class=\"error\">$line</span>";
			} else {
				echo "<span>$offset</span> $line";
			}
			$offset++;
		}
		echo '</pre>';		
	}

	/**
	 * Dispatch an email notification message when an error is triggered. 
	 *
	 * @param unknown_type $emailAddress
	 * @param unknown_type $errno
	 * @param unknown_type $errstr
	 * @param unknown_type $errfile
	 * @param unknown_type $errline
	 * @param unknown_type $errcontext
	 * @param unknown_type $errorType
	 */
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
	
	/**
	 * Render or return a backtrace from the given scope.
	 *
	 * @param unknown_type $returnVal
	 * @param unknown_type $ignoreAjax
	 * @return unknown
	 */
	static function backtrace($returnVal = false, $ignoreAjax = false) {

		$bt = debug_backtrace();

		// Ingore functions that are plumbing of the error handler
		$ignoredFunctions = array('Debug::emailError','Debug::warningHandler','Debug::fatalHandler','errorHandler','Debug::showError','Debug::backtrace', 'exceptionHandler');
		while( $bt && in_array(self::full_func_name($bt[0]), $ignoredFunctions) ) {
			array_shift($bt);
		}
		
		$result = "<ul>";
		foreach($bt as $item) {
			if(Director::is_ajax() && !$ignoreAjax) {
				$result .= self::full_func_name($item,true) . "\n";
				$result .= "line $item[line] of " . basename($item['file']) . "\n\n";
			} else {
				if ($item['function'] == 'user_error') {
					$name = $item['args'][0];
				} else {
					$name = self::full_func_name($item,true);
				}
				$result .= "<li><b>" . $name . "</b>\n<br />\n";
				$result .= isset($item['line']) ? "Line $item[line] of " : '';
				$result .=  isset($item['file']) ? basename($item['file']) : ''; 
				$result .= "</li>\n";
			}
		}
		$result .= "</ul>";

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
/**
 * Generic callback, to catch uncaught exceptions when they bubble up to the top of the call chain.
 * 
 * @ignore 
 * @param unknown_type $exception
 */
function exceptionHandler($exception) {
	$errno = E_USER_ERROR;
	$type = get_class($exception);
	$message = "Uncaught " . $type . ": " . $exception->getMessage();
	$file = $exception->getFile();
	$line = $exception->getLine();
	$context = $exception->getTrace();
	Debug::fatalHandler($errno, $message, $file, $line, $context);
}

/**
 * Generic callback to catch standard PHP runtime errors thrown by the interpreter
 * or manually triggered with the user_error function.
 * 
 * @ignore 
 * @param unknown_type $errno
 * @param unknown_type $errstr
 * @param unknown_type $errfile
 * @param unknown_type $errline
 * @param unknown_type $errcontext
 */
function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	switch($errno) {
		case E_ERROR:
		case E_CORE_ERROR:
		case E_USER_ERROR:
			Debug::fatalHandler($errno, $errstr, $errfile, $errline, $errcontext);
			break;

		case E_NOTICE:
		case E_WARNING:
		case E_CORE_WARNING:
		case E_USER_WARNING:
			Debug::warningHandler($errno, $errstr, $errfile, $errline, $errcontext);
			break;
			
	}
}

/**
 * Interface for stylish rendering of a debug info report.
 */
interface DebugReporter {
	
	/**
	 * Render HTML markup for the header/top segment of debug report.
	 */
	function writeHeader();
	
	/**
	 * Render HTML markup for the footer and closing tags of debug report.
	 */
	function writeFooter();
	
}

/**
 * Concrete class to render a Sapphire specific wrapper design
 * for developer errors, task runner, and test runner.
 */
class SapphireDebugReporter implements DebugReporter {
	
	public function writeHeader() {
		echo '<!DOCTYPE html><html><head><title>'. $_SERVER['REQUEST_METHOD'] . ' ' .$_SERVER['REQUEST_URI'] .'</title>';
		echo '<style type="text/css">';
		echo 'body { background-color:#eee; margin:0; padding:0; font-family:Helvetica,Arial,sans-serif; }';
		echo '.info { border-bottom:1px dotted #333; background-color:#ccdef3; margin:0; padding:6px 12px; }';
		echo '.info h1 { margin:0; padding:0; color:#333; letter-spacing:-2px; }';
		echo '.header { margin:0; border-bottom:6px solid #ccdef3; height:23px; background-color:#666673; padding:4px 0 2px 6px; background-image:url('.Director::absoluteBaseURL().'cms/images/mainmenu/top-bg.gif); }';
		echo '.trace { padding:6px 12px; }';
		echo '.trace li { font-size:14px; margin:6px 0; }';
		echo 'pre { margin-left:18px; }';
		echo 'pre span { color:#999;}';
		echo 'pre .error { color:#f00; }';
		echo '.pass { padding:2px 20px 2px 40px; color:#006600; background:#E2F9E3 url('.Director::absoluteBaseURL() .'cms/images/alert-good.gif) no-repeat scroll 7px 50%; border:1px solid #8DD38D; }';
		echo '.fail { padding:2px 20px 2px 40px; color:#C80700; background:#FFE9E9 url('.Director::absoluteBaseURL() .'cms/images/alert-bad.gif) no-repeat scroll 7px 50%; }';	
		echo '</style></head>';
		echo '<body>';
		echo '<div class="header"><img src="'. Director::absoluteBaseURL() .'cms/images/mainmenu/logo.gif" width="26" height="23"></div>';
	}
	
	public function writeFooter() {
		echo "</body></html>";		
	}
	
}

?>