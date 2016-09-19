<?php
/**
 * Supports debugging and core error handling.
 *
 * Attaches custom methods to the default error handling hooks
 * in PHP. Currently, two levels of error are supported:
 *
 * - Notice
 * - Warning
 * - Error
 *
 * Uncaught exceptions are currently passed to the debug
 * reporter as standard PHP errors.
 *
 * Errors handled by this class are passed along to {@link SS_Log}.
 * For configuration information, see the {@link SS_Log}
 * class documentation.
 *
 * @todo add support for user defined config: Debug::die_on_notice(true | false)
 * @todo better way of figuring out the error context to display in highlighted source
 *
 * @package framework
 * @subpackage dev
 */
class Debug {

	/**
	 * @config
	 * @var String indicating the file where errors are logged.
	 * Filename is relative to the site root.
	 * The named file will have a terse log sent to it, and the full log (an
	 * encoded file containing backtraces and things) will go to a file of a similar
	 * name, but with the suffix ".full" added.
	 */
	private static $log_errors_to = null;

	/**
	 * @config
	 * @var string The header of the message shown to users on the live site when a fatal error occurs.
	 */
	private static $friendly_error_header = 'There has been an error';

	/**
	 * Set to true to enable friendly errors to set a http response code corresponding to the error.
	 * If left false then error pages will be served as HTTP 200.
	 *
	 * Will be removed in 4.0, and fixed to on.
	 *
	 * @config
	 * @var bool
	 */
	private static $friendly_error_httpcode = false;

	/**
	 * @config
	 * @var string The body of the message shown to users on the live site when a fatal error occurs.
	 */
	private static $friendly_error_detail = 'The website server has not been able to respond to your request.';

	/**
	 * Show the contents of val in a debug-friendly way.
	 * Debug::show() is intended to be equivalent to dprintr()
	 */
	public static function show($val, $showHeader = true) {
		if(!Director::isLive()) {
			if($showHeader) {
				$caller = Debug::caller();
				if(Director::is_ajax() || Director::is_cli())
					echo "Debug ($caller[class]$caller[type]$caller[function]() in " . basename($caller['file'])
						. ":$caller[line])\n";
				else
					echo "<div style=\"background-color: white; text-align: left;\">\n<hr>\n"
						. "<h3>Debug <span style=\"font-size: 65%\">($caller[class]$caller[type]$caller[function]()"
						. " \nin " . basename($caller['file']) . ":$caller[line])</span>\n</h3>\n";
			}

			echo Debug::text($val);

			if(!Director::is_ajax() && !Director::is_cli()) echo "</div>";
			else echo "\n\n";
		}

	}

	/**
	 * Returns the caller for a specific method
	 *
	 * @return array
	 */
	public static function caller() {
		$bt = debug_backtrace();
		$caller = isset($bt[2]) ? $bt[2] : array();
		$caller['line'] = $bt[1]['line'];
		$caller['file'] = $bt[1]['file'];
		if(!isset($caller['class'])) $caller['class'] = '';
		if(!isset($caller['type'])) $caller['type'] = '';
		if(!isset($caller['function'])) $caller['function'] = '';
		return $caller;
	}

	/**
	 * Close out the show dumper
	 *
	 * @param mixed $val
	 */
	public static function endshow($val) {
		if(!Director::isLive()) {
			$caller = Debug::caller();
			echo "<hr>\n<h3>Debug \n<span style=\"font-size: 65%\">($caller[class]$caller[type]$caller[function]()"
				. " \nin " . basename($caller['file']) . ":$caller[line])</span>\n</h3>\n";
			echo Debug::text($val);
			die();
		}
	}

	/**
	 * Quick dump of a variable.
	 *
	 * @param mixed $val
	 */
	public static function dump($val) {
		self::create_debug_view()->writeVariable($val, self::caller());
	}

	/**
	 * ??
	 *
	 * @param unknown_type $val
	 * @return unknown
	 */
	public static function text($val) {
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
		} else if (is_bool($val)) {
			$val = $val ? 'true' : 'false';
			$val = '(bool) ' . $val;
		} else {
			if(!Director::is_cli() && !Director::is_ajax()) {
				$val = "<pre style=\"font-family: Courier new\">" . htmlentities($val, ENT_COMPAT, 'UTF-8')
					. "</pre>\n";
			}
		}

		return $val;
	}

	/**
	 * Show a debugging message
	 */
	public static function message($message, $showHeader = true) {
		if(!Director::isLive()) {
			$caller = Debug::caller();
			$file = basename($caller['file']);
			if(Director::is_cli()) {
				if($showHeader) echo "Debug (line $caller[line] of $file):\n ";
				echo $message . "\n";
			} else {
				echo "<p class=\"message warning\">\n";
				if($showHeader) echo "<b>Debug (line $caller[line] of $file):</b>\n ";
				echo Convert::raw2xml($message) . "</p>\n";
			}
		}
	}

	// Keep track of how many headers have been sent
	private static $headerCount = 0;

	/**
	 * Send a debug message in an HTTP header. Only works if you are
	 * on Dev, and headers have not yet been sent.
	 *
	 * @param string $msg
	 * @param string $prefix (optional)
	 * @return void
	 */
	public static function header($msg, $prefix = null) {
		if (Director::isDev() && !headers_sent()) {
			self::$headerCount++;
			header('SS-'.self::$headerCount.($prefix?'-'.$prefix:'').': '.$msg);
		}
	}

	/**
	 * Log to a standard text file output.
	 *
	 * @param $message string to output
	 */
	public static function log($message) {
		if (defined('BASE_PATH')) {
			$path = BASE_PATH;
		}
		else {
			$path = dirname(__FILE__) . '/../..';
		}
		$file = $path . '/debug.log';
		$now = date('r');
		$content = "\n\n== $now ==\n$message\n";
		file_put_contents($file, $content, FILE_APPEND);
	}

	/**
	 * Load error handlers into environment.
	 * Caution: The error levels default to E_ALL is the site is in dev-mode (set in main.php).
	 */
	public static function loadErrorHandlers() {
		set_error_handler('errorHandler', error_reporting());
		set_exception_handler('exceptionHandler');
	}

	public static function noticeHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if(error_reporting() == 0) return;
		ini_set('display_errors', 0);

		// Send out the error details to the logger for writing
		SS_Log::log(
			array(
				'errno' => $errno,
				'errstr' => $errstr,
				'errfile' => $errfile,
				'errline' => $errline,
				'errcontext' => $errcontext
			),
			SS_Log::NOTICE
		);

		if(Director::isDev()) {
			return self::showError($errno, $errstr, $errfile, $errline, $errcontext, "Notice");
		} else {
			return false;
		}
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
	public static function warningHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		if(error_reporting() == 0) return;
		ini_set('display_errors', 0);

		// Send out the error details to the logger for writing
		SS_Log::log(
			array(
				'errno' => $errno,
				'errstr' => $errstr,
				'errfile' => $errfile,
				'errline' => $errline,
				'errcontext' => $errcontext
			),
			SS_Log::WARN
		);

		if(Director::isDev()) {
			return self::showError($errno, $errstr, $errfile, $errline, $errcontext, "Warning");
		} else {
			return false;
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
	public static function fatalHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		ini_set('display_errors', 0);

		// Send out the error details to the logger for writing
		SS_Log::log(
			array(
				'errno' => $errno,
				'errstr' => $errstr,
				'errfile' => $errfile,
				'errline' => $errline,
				'errcontext' => $errcontext
			),
			SS_Log::ERR
		);

		if(Director::isDev() || Director::is_cli()) {
			self::showError($errno, $errstr, $errfile, $errline, $errcontext, "Error");
		} else {
			self::friendlyError();
		}
		return false;
	}

	/**
	 * Render a user-facing error page, using the default HTML error template
	 * rendered by {@link ErrorPage} if it exists. Doesn't use the standard {@link SS_HTTPResponse} class
	 * the keep dependencies minimal.
	 *
	 * @uses ErrorPage
	 *
	 * @param int $statusCode HTTP Status Code (Default: 500)
	 * @param string $friendlyErrorMessage User-focused error message. Should not contain code pointers
	 *                                     or "tech-speak". Used in the HTTP Header and ajax responses.
	 * @param string $friendlyErrorDetail Detailed user-focused message. Is just used if no {@link ErrorPage} is found
	 *                                    for this specific status code.
	 * @return string HTML error message for non-ajax requests, plaintext for ajax-request.
	 */
	public static function friendlyError($statusCode=500, $friendlyErrorMessage=null, $friendlyErrorDetail=null) {
		// Ensure the error message complies with the HTTP 1.1 spec
		if(!$friendlyErrorMessage) {
			$friendlyErrorMessage = Config::inst()->get('Debug', 'friendly_error_header');
		}
		$friendlyErrorMessage = strip_tags(str_replace(array("\n", "\r"), '', $friendlyErrorMessage));

		if(!$friendlyErrorDetail) {
			$friendlyErrorDetail = Config::inst()->get('Debug', 'friendly_error_detail');
		}

		if(!headers_sent()) {
			// Allow toggle between legacy behaviour and correctly setting HTTP response code
			// In 4.0 this should be fixed to always set this response code.
			if(Config::inst()->get('Debug', 'friendly_error_httpcode') || !Controller::has_curr()) {
				header($_SERVER['SERVER_PROTOCOL'] . " $statusCode $friendlyErrorMessage");
			}
		}

		if(Director::is_ajax()) {
			echo $friendlyErrorMessage;
		} else {
			if(!headers_sent()) header('Content-Type: text/html');
			if(class_exists('ErrorPage')){
				$errorFilePath = ErrorPage::get_filepath_for_errorcode(
					$statusCode,
					class_exists('Translatable') ? Translatable::get_current_locale() : null
				);
				if(file_exists($errorFilePath)) {
					$content = file_get_contents($errorFilePath);
					// $BaseURL is left dynamic in error-###.html, so that multi-domain sites don't get broken
					echo str_replace('$BaseURL', Director::absoluteBaseURL(), $content);
				}
			} else {
				$renderer = new DebugView();
				$renderer->writeHeader();
				$renderer->writeInfo("Website Error", $friendlyErrorMessage, $friendlyErrorDetail);

				if(Email::config()->admin_email) {
					$mailto = Email::obfuscate(Email::config()->admin_email);
					$renderer->writeParagraph('Contact an administrator: ' . $mailto . '');
				}

				$renderer->writeFooter();
			}
		}
		return false;
	}

	/**
	 * Create an instance of an appropriate DebugView object.
	 *
	 * @return DebugView
	 */
	public static function create_debug_view() {
		$service = Director::is_cli() || Director::is_ajax()
			? 'CliDebugView'
			: 'DebugView';
		return Injector::inst()->get($service);
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
	public static function showError($errno, $errstr, $errfile, $errline, $errcontext, $errtype) {
		if(!headers_sent()) {
			$errText = "$errtype at line $errline of $errfile";
			$errText = str_replace(array("\n","\r")," ",$errText);

			if(!headers_sent()) header($_SERVER['SERVER_PROTOCOL'] . " 500 $errText");

			// if error is displayed through ajax with CliDebugView, use plaintext output
			if(Director::is_ajax()) {
				header('Content-Type: text/plain');
			}
		}

		$reporter = self::create_debug_view();

		// Coupling alert: This relies on knowledge of how the director gets its URL, it could be improved.
		$httpRequest = null;
		if(isset($_SERVER['REQUEST_URI'])) {
			$httpRequest = $_SERVER['REQUEST_URI'];
		} elseif(isset($_REQUEST['url'])) {
			$httpRequest = $_REQUEST['url'];
		}
		if(isset($_SERVER['REQUEST_METHOD'])) $httpRequest = $_SERVER['REQUEST_METHOD'] . ' ' . $httpRequest;

		$reporter->writeHeader($httpRequest);
		$reporter->writeError($httpRequest, $errno, $errstr, $errfile, $errline, $errcontext);

		if(file_exists($errfile)) {
			$lines = file($errfile);

			// Make the array 1-based
			array_unshift($lines,"");
			unset($lines[0]);

			$offset = $errline-10;
			$lines = array_slice($lines, $offset, 16, true);
			$reporter->writeSourceFragment($lines, $errline);
		}
		$reporter->writeTrace(($errcontext ? $errcontext : debug_backtrace()));
		$reporter->writeFooter();
		return false;
	}

	/**
	 * Utility method to render a snippet of PHP source code, from selected file
	 * and highlighting the given line number.
	 *
	 * @param string $errfile
	 * @param int $errline
	 */
	public static function showLines($errfile, $errline) {
		$lines = file($errfile);
		$offset = $errline-10;
		$lines = array_slice($lines, $offset, 16);
		echo '<pre>';
		$offset++;
		foreach($lines as $line) {
			$line = htmlentities($line, ENT_COMPAT, 'UTF-8');
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
	 * Check if the user has permissions to run URL debug tools,
	 * else redirect them to log in.
	 */
	public static function require_developer_login() {
		if(Director::isDev())	{
			return;
		}
		if(isset($_SESSION['loggedInAs'])) {
			// We have to do some raw SQL here, because this method is called in Object::defineMethods().
			// This means we have to be careful about what objects we create, as we don't want Object::defineMethods()
			// being called again.
			// This basically calls Permission::checkMember($_SESSION['loggedInAs'], 'ADMIN');

			// @TODO - Rewrite safely using DataList::filter
			$memberID = $_SESSION['loggedInAs'];
			$permission = DB::prepared_query('
				SELECT "ID" FROM "Permission"
				INNER JOIN "Group_Members" ON "Permission"."GroupID" = "Group_Members"."GroupID"
				WHERE "Permission"."Code" = ?
				AND "Permission"."Type" = ?
				AND "Group_Members"."MemberID" = ?',
				array(
					'ADMIN', // Code
					Permission::GRANT_PERMISSION, // Type
					$memberID // MemberID
				)
			)->value();

			if($permission) return;
		}

		// This basically does the same as
		// Security::permissionFailure(null, "You need to login with developer access to make use of debugging tools.")
		// We have to do this because of how early this method is called in execution.
		$_SESSION['Security']['Message']['message']
			= "You need to login with developer access to make use of debugging tools.";
		$_SESSION['Security']['Message']['type'] =  'warning';
		$_SESSION['BackURL'] = $_SERVER['REQUEST_URI'];
		header($_SERVER['SERVER_PROTOCOL'] . " 302 Found");
		header("Location: " . Director::baseURL() . Security::login_url());
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
	exit(1);
}

/**
 * Generic callback to catch standard PHP runtime errors thrown by the interpreter
 * or manually triggered with the user_error function. Any unknown error codes are treated as
 * fatal errors.
 * Caution: The error levels default to E_ALL if the site is in dev-mode (set in main.php).
 *
 * @ignore
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 */
function errorHandler($errno, $errstr, $errfile, $errline) {
	switch($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
		case E_STRICT:
			return Debug::noticeHandler($errno, $errstr, $errfile, $errline, debug_backtrace());

		case E_WARNING:
		case E_CORE_WARNING:
		case E_USER_WARNING:
		case E_RECOVERABLE_ERROR:
			return Debug::warningHandler($errno, $errstr, $errfile, $errline, debug_backtrace());

		case E_ERROR:
		case E_CORE_ERROR:
		case E_USER_ERROR:
		default:
			Debug::fatalHandler($errno, $errstr, $errfile, $errline, debug_backtrace());
			exit(1);
	}
}
