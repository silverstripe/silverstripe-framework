<?php
/**
 * Class of static methods to support debugging.
 */
class Debug {
	
	/**
	 * @var $mail_server string Custom mailserver for sending debug mails.
	 */
	protected static $custom_smtp_server = '';
	
	protected static $send_errors_to;
	
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
		if(is_object($val) && $val->hasMethod('debug')) {
			return $val->debug();
		} else {
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
	 */
	static function loadErrorHandlers() {
		Debug::loadFatalErrorHandler();
	}

	static function loadFatalErrorHandler() {
		set_error_handler('errorHandler', E_ALL & ~E_NOTICE);
	}

	static function warningHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if(self::$send_warnings_to) self::emailError(self::$send_warnings_to, $errno, $errstr, $errfile, $errline, $errcontext, "Warning");

		if(Director::isDev()) {
			if(error_reporting() != 0) { // otherwise the error was suppressed with @
				self::showError($errno, $errstr, $errfile, $errline, $errcontext);
				die();
			}
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
			echo "ERROR:There has been an error";

		} else {
			if(file_exists('../assets/error-500.html')) {
				echo "ERROR:";
				include('../assets/error-500.html');
			} else {
				echo "ERROR:<h1>Error</h1><p>The website server has not been able to respond to your request.</p>\n";
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
		mail($emailAddress, "$errorType on $_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", $data, "Content-type: text/html\nFrom: errors@silverstripe.com");
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
	 * Use send_errors_to() instead.
	 */
	static function sendLiveErrorsTo($emailAddress) {
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
		$ignoredFunctions = array('Debug::emailError','Debug::warningHandler','Debug::fatalHandler','errorHandler','Debug::showError','Debug::backtrace');
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
		
		if($returnVal) return $result;
		else echo $result;
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
			@$funcName .= "(" . implode(",", (array)$item['args'])  .")";
		}
		
		return $funcName;
	}
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