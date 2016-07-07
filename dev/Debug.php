<?php

use SilverStripe\ORM\DB;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;


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
	 * Show the contents of val in a debug-friendly way.
	 * Debug::show() is intended to be equivalent to dprintr()
	 * 
	 * @param mixed $val
	 * @param bool $showHeader
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
		$caller = $bt[2];
		$caller['line'] = $bt[1]['line'];
		$caller['file'] = $bt[1]['file'];
		if(!isset($caller['class'])) $caller['class'] = '';
		if(!isset($caller['type'])) $caller['type'] = '';
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
	 * @param mixed $val
	 * @return string
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
		$_SESSION['SilverStripe\\Security\\Security']['Message']['message']
			= "You need to login with developer access to make use of debugging tools.";
		$_SESSION['SilverStripe\\Security\\Security']['Message']['type'] =  'warning';
		$_SESSION['BackURL'] = $_SERVER['REQUEST_URI'];
		header($_SERVER['SERVER_PROTOCOL'] . " 302 Found");
		header("Location: " . Director::baseURL() . Security::login_url());
		die();
	}
}
