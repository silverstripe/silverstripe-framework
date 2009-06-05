<?php
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
	 * String indicating the file where errors are logged.
	 * Filename is relative to the site root.
	 * The named file will have a terse log sent to it, and the full log (an 
	 * encoded file containing backtraces and things) will go to a file of a similar
	 * name, but with the suffix ".full" added.
	 */
	protected static $log_errors_to = null;
	
	/**
	 * Show the contents of val in a debug-friendly way.
	 * Debug::show() is intended to be equivalent to dprintr()
	 */
	static function show($val, $showHeader = true) {
		if(!Director::isLive()) {
			if($showHeader) {
				$caller = Debug::caller();
				if(Director::is_ajax() || Director::is_cli())
					echo "Debug ($caller[class]$caller[type]$caller[function]() in line $caller[line] of " . basename($caller['file']) . ")\n";
				else 
					echo "<div style=\"background-color: white; text-align: left;\">\n<hr>\n<h3>Debug <span style=\"font-size: 65%\">($caller[class]$caller[type]$caller[function]() \n<span style=\"font-weight:normal\">in line</span> $caller[line] \n<span style=\"font-weight:normal\">of</span> " . basename($caller['file']) . ")</span>\n</h3>\n";
			}
			
			echo Debug::text($val);
	
			if(!Director::is_ajax() && !Director::is_cli()) echo "</div>";
			else echo "\n\n";
		}

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
		echo '<pre style="background-color:#ccc;padding:5px;font-size:14px;line-height:18px;">';
		$caller = Debug::caller();
		echo "<span style=\"font-size: 12px;color:#666;\">Line $caller[line] of " . basename($caller['file']) . ":</span>\n";
		if (is_string($val)) print_r(wordwrap($val, 100));
		else print_r($val);
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
			if(!Director::is_cli() && !Director::is_ajax()) {
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
	 * Log to a standard text file output.
	 *
	 * @param $message string to output
	 */
	static function log($message) {
		$file = dirname(__FILE__).'/../../debug.log';
		$now = date('r');
		$oldcontent = file_get_contents($file);
		$content = $oldcontent . "\n\n== $now ==\n$message\n";
		file_put_contents($file, $content);
	}

	/**
	 * Load error handlers into environment.
	 * Caution: The error levels default to E_ALL is the site is in dev-mode (set in main.php).
	 */
	static function loadErrorHandlers() {
		set_error_handler('errorHandler', error_reporting());
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
		self::log_error_if_necessary( $errno, $errstr, $errfile, $errline, $errcontext, "Warning");

		if(Director::isDev()) {
		  self::showError($errno, $errstr, $errfile, $errline, $errcontext, "Warning");
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
		self::log_error_if_necessary( $errno, $errstr, $errfile, $errline, $errcontext, "Error");
		
		if(Director::isDev() || Director::is_cli()) {
			Debug::showError($errno, $errstr, $errfile, $errline, $errcontext, "Error");

		} else {
			Debug::friendlyError();
		}
		exit(1);
	}
	
	/**
	 * Render a user-facing error page, using the default HTML error template
	 * rendered by {@link ErrorPage} if it exists. Doesn't use the standard {@link HTTPResponse} class
	 * the keep dependencies minimal. 
	 * 
	 * @uses ErrorPage
	 *
	 * @param int $statusCode HTTP Status Code (Default: 500)
	 * @param string $friendlyErrorMessage User-focused error message. Should not contain code pointers or "tech-speak".
	 *    Used in the HTTP Header and ajax responses.
	 * @param string $friendlyErrorDetail Detailed user-focused message. Is just used if no {@link ErrorPage} is found
	 *    for this specific status code.
	 * @return string HTML error message for non-ajax requests, plaintext for ajax-request.
	 */
	static function friendlyError($statusCode = 500, $friendlyErrorMessage = null, $friendlyErrorDetail = null) {
		if(!$friendlyErrorMessage) $friendlyErrorMessage = 'There has been an error';
		if(!$friendlyErrorDetail) $friendlyErrorDetail = 'The website server has not been able to respond to your request.';

		if(!headers_sent()) header($_SERVER['SERVER_PROTOCOL'] . " $statusCode $friendlyErrorMessage");

		if(Director::is_ajax()) {
			echo $friendlyErrorMessage;
		} else {
			$errorFilePath = ErrorPage::get_filepath_for_errorcode($statusCode, Translatable::get_current_locale());
			if(file_exists($errorFilePath)) {
				echo file_get_contents($errorFilePath);
			} else {
				$renderer = new DebugView();
				$renderer->writeHeader();
				$renderer->writeInfo("Website Error", $friendlyErrorMessage, $friendlyErrorDetail);
				
				if(Email::getAdminEmail()) {
					$mailto = Email::obfuscate(Email::getAdminEmail());
					$renderer->writeParagraph('Contact an administrator: ' . $mailto . '');
				}

				$renderer->writeFooter();
			}
		}
	}
	
	/**
	 * Create an instance of an appropriate DebugView object.
	 */
	static function create_debug_view() {
		if(Director::is_cli() || Director::is_ajax()) return new CliDebugView();
		else return new DebugView();
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
	static function showError($errno, $errstr, $errfile, $errline, $errcontext, $errtype) {
		if(!headers_sent()) {
			$errText = "$errtype: \"$errstr\" at line $errline of $errfile";
			$errText = str_replace(array("\n","\r")," ",$errText);
			if(!headers_sent()) header($_SERVER['SERVER_PROTOCOL'] . " 500 $errText");
			
			// if error is displayed through ajax with CliDebugView, use plaintext output
			if(Director::is_ajax()) header('Content-Type: text/plain');
		}
		
		// Legacy error handling for customized prototype.js Ajax.Base.responseIsSuccess()
		// if(Director::is_ajax()) echo "ERROR:\n";
		
		$reporter = self::create_debug_view();
		
		// Coupling alert: This relies on knowledge of how the director gets its URL, it could be improved.
		$httpRequest = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_REQUEST['url'];
		if(isset($_SERVER['REQUEST_METHOD'])) $httpRequest = $_SERVER['REQUEST_METHOD'] . ' ' . $httpRequest;

		$reporter->writeHeader($httpRequest);
		$reporter->writeError($httpRequest, $errno, $errstr, $errfile, $errline, $errcontext);

		$lines = file($errfile);

		// Make the array 1-based
		array_unshift($lines,"");
		unset($lines[0]);

		$offset = $errline-10;
		$lines = array_slice($lines, $offset, 16, true);
		$reporter->writeSourceFragment($lines, $errline);

		$reporter->writeTrace(($errcontext ? $errcontext : debug_backtrace()));
		$reporter->writeFooter();
		exit(1);
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
	 * Uses the native PHP mail() function.
	 *
	 * @param string $emailAddress
	 * @param string $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param string $errcontext
	 * @param string $errorType "warning" or "error"
	 * @return boolean
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
		if(self::$custom_smtp_server) ini_set("SMTP", self::$custom_smtp_server);			

		$relfile = Director::makeRelative($errfile);
		if($relfile[0] == '/') $relfile = substr($relfile,1);
		
		return mail($emailAddress, "$errorType at $relfile line $errline (http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI])", $data, "Content-type: text/html\nFrom: errors@silverstripe.com");
	}
	
	/**
	 * Log the given error, if self::$log_errors is set.
	 * Uses the native error_log() funtion in PHP.
	 * 
	 * Format: [d-M-Y h:i:s] <type> at <file> line <line>: <errormessage> <url>
	 * 
	 * @todo Detect script path for CLI errors
	 * @todo Log detailed errors to full file
	 */
	protected static function log_error_if_necessary($errno, $errstr, $errfile, $errline, $errcontext, $errtype) {
		if(self::$log_errors_to) {
			$shortFile = "../" . self::$log_errors_to;
			$fullFile = $shortFile . '.full';

			$relfile = Director::makeRelative($errfile);
			if($relfile[0] == '/') $relfile = substr($relfile,1);

			$urlSuffix = "";
			if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] && isset($_SERVER['REQUEST_URI'])) {
				$urlSuffix = " (http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI])";
			}
			
			error_log('[' . date('d-M-Y h:i:s') . "] $errtype at $relfile line $errline: $errstr$urlSuffix\n", 3, $shortFile);
		}
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
	 *
	 * @param string $emailAddress The email address to send errors to
	 * @param string $sendWarnings Set to true to send warnings as well as errors (Default: false)
	 */
	static function send_errors_to($emailAddress, $sendWarnings = false) {
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
	 * @param string $emailAddress
	 */
	static function send_warnings_to($emailAddress) {
		self::$send_warnings_to = $emailAddress;
	}

	/**
	 * @return string
	 */
	static function get_send_warnings_to() {
		return self::$send_warnings_to;
	}
	
	/**
	 * Call this to enable logging of errors.
	 */
	static function log_errors_to($logFile = ".sserrors") {
		self::$log_errors_to = $logFile;
	}
	

	/**
	 * Deprecated.  Send live errors and warnings to the given address.
	 * @deprecated 2.3 Use send_errors_to() instead.
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
		$result = self::get_rendered_backtrace($bt, Director::is_cli() || (Director::is_ajax() && !$ignoreAjax));
		if ($returnVal) {
			return $result;
		} else {
			echo $result;
		}
	}
		
	/**
	 * Render a backtrace array into an appropriate plain-text or HTML string.
	 * @param $bt The trace array, as returned by debug_backtrace() or Exception::getTrace().
	 * @param $plainText Set to false for HTML output, or true for plain-text output
	 */
	static function get_rendered_backtrace($bt, $plainText = false) {
		// Ingore functions that are plumbing of the error handler
		$ignoredFunctions = array('DebugView->writeTrace', 'CliDebugView->writeTrace', 
			'Debug::emailError','Debug::warningHandler','Debug::fatalHandler','errorHandler','Debug::showError',
			'Debug::backtrace', 'exceptionHandler');
			
		while( $bt && in_array(self::full_func_name($bt[0]), $ignoredFunctions) ) {
			array_shift($bt);
		}
		
		$result = "<ul>";
		foreach($bt as $item) {
			if($plainText) {
				$result .= self::full_func_name($item,true) . "\n";
				if(isset($item['line']) && isset($item['file'])) $result .= "line $item[line] of " . basename($item['file']) . "\n";
				$result .= "\n";
			} else {
				if ($item['function'] == 'user_error') {
					$name = $item['args'][0];
				} else {
					$name = self::full_func_name($item,true);
				}
				$result .= "<li><b>" . htmlentities($name) . "</b>\n<br />\n";
				$result .= isset($item['line']) ? "Line $item[line] of " : '';
				$result .=  isset($item['file']) ? htmlentities(basename($item['file'])) : ''; 
				$result .= "</li>\n";
			}
		}
		$result .= "</ul>";
		return $result;
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
		header($_SERVER['SERVER_PROTOCOL'] . " 302 Found");
		header("Location: " . Director::baseURL() . "Security/login");
		die();
	}
}










/**
 * Generic callback, to catch uncaught exceptions when they bubble up to the top of the call chain.
 * 
 * @ignore 
 * @param Exception $exception
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
 * Caution: The error levels default to E_ALL is the site is in dev-mode (set in main.php).
 * 
 * @ignore 
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 * @param string $errcontext
 */
function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	switch($errno) {
		case E_ERROR:
		case E_CORE_ERROR:
		case E_USER_ERROR:
			Debug::fatalHandler($errno, $errstr, $errfile, $errline, null);
			break;

		case E_NOTICE:
		case E_WARNING:
		case E_CORE_WARNING:
		case E_USER_WARNING:
			Debug::warningHandler($errno, $errstr, $errfile, $errline, null);
			break;
			
	}
}
