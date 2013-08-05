<?php
/**
 * This file is the Framework constants bootstrap. It will prepare some basic common constants.
 *
 * It takes care of:
 *  - Including _ss_environment.php
 *  - Normalisation of $_SERVER values
 *  - Initialisation of necessary constants (mostly paths)
 *
 * Initialized constants:
 * - BASE_URL: Full URL to the webroot, e.g. "http://my-host.com/my-webroot" (no trailing slash).
 * - BASE_PATH: Absolute path to the webroot, e.g. "/var/www/my-webroot" (no trailing slash).
 *   See Director::baseFolder(). Can be overwritten by Director::setBaseFolder().
 * - TEMP_FOLDER: Absolute path to temporary folder, used for manifest and template caches. Example: "/var/tmp"
 *   See getTempFolder(). No trailing slash.
 * - MODULES_DIR: Not used at the moment
 * - MODULES_PATH: Not used at the moment
 * - THEMES_DIR: Path relative to webroot, e.g. "themes"
 * - THEMES_PATH: Absolute filepath, e.g. "/var/www/my-webroot/themes"
 * - CMS_DIR: Path relative to webroot, e.g. "cms"
 * - CMS_PATH: Absolute filepath, e.g. "/var/www/my-webroot/cms"
 * - SAPPHIRE_DIR: Path relative to webroot, e.g. "sapphire"
 * - SAPPHIRE_PATH:Absolute filepath, e.g. "/var/www/my-webroot/sapphire"
 * - THIRDPARTY_DIR: Path relative to webroot, e.g. "sapphire/thirdparty"
 * - THIRDPARTY_PATH: Absolute filepath, e.g. "/var/www/my-webroot/sapphire/thirdparty"
 *
 * @package sapphire
 * @subpackage core
 */

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT CONFIG

/**
 * Include _ss_environment.php files
 */
$envFiles = array('_ss_environment.php', '../_ss_environment.php', '../../_ss_environment.php', '../../../_ss_environment.php');
foreach($envFiles as $envFile) {
	if(@file_exists($envFile)) {
		define('SS_ENVIRONMENT_FILE', $envFile);
		include_once($envFile);
		break;
	}
}

///////////////////////////////////////////////////////////////////////////////
// GLOBALS AND DEFINE SETTING

function stripslashes_recursively(&$array) {
	foreach($array as $k => $v) {
		if(is_array($v)) stripslashes_recursively($array[$k]);
		else $array[$k] = stripslashes($v);
	}
}

/**
 * A blank HTTP_HOST value is used to detect command-line execution.
 * We update the $_SERVER variable to contain data consistent with the rest of the application.
 */
if(!isset($_SERVER['HTTP_HOST'])) {
	// HTTP_HOST, REQUEST_PORT, SCRIPT_NAME, and PHP_SELF
	if(isset($_FILE_TO_URL_MAPPING)) {
		$fullPath = $testPath = realpath($_SERVER['SCRIPT_FILENAME']);
		while($testPath && $testPath != "/"  && !preg_match('/^[A-Z]:\\\\$/', $testPath)) {
			if(isset($_FILE_TO_URL_MAPPING[$testPath])) {
				$url = $_FILE_TO_URL_MAPPING[$testPath]
					. str_replace(DIRECTORY_SEPARATOR,'/',substr($fullPath,strlen($testPath)));

				$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST);
				$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = parse_url($url, PHP_URL_PATH);
				$_SERVER['REQUEST_PORT'] = parse_url($url, PHP_URL_PORT);
				break;
			}
			$testPath = dirname($testPath);
		}
	}

	// Everything else
	$serverDefaults = array(
		'SERVER_PROTOCOL' => 'HTTP/1.1',
		'HTTP_ACCEPT' => 'text/plain;q=0.5',
		'HTTP_ACCEPT_LANGUAGE' => '*;q=0.5',
		'HTTP_ACCEPT_ENCODING' => '',
		'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1;q=0.5',
		'SERVER_SIGNATURE' => 'Command-line PHP/' . phpversion(),
		'SERVER_SOFTWARE' => 'PHP/' . phpversion(),
		'SERVER_ADDR' => '127.0.0.1',
		'REMOTE_ADDR' => '127.0.0.1',
		'REQUEST_METHOD' => 'GET',
		'HTTP_USER_AGENT' => 'CLI',
	);

	$_SERVER = array_merge($serverDefaults, $_SERVER);

	/**
	 * If we have an HTTP_HOST value, then we're being called from the webserver and there are some things that
	 * need checking
	 */
} else {
	/**
	 * Fix magic quotes setting
	 */
	if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
		if($_REQUEST) stripslashes_recursively($_REQUEST);
		if($_GET) stripslashes_recursively($_GET);
		if($_POST) stripslashes_recursively($_POST);
	}

	/**
	 * Fix HTTP_HOST from reverse proxies
	 */
	if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
		$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
	}
}

/**
 * Define system paths
 */
if(!defined('BASE_PATH')) {
	// Assuming that this file is sapphire/core/Core.php we can then determine the base path
	define('BASE_PATH', rtrim(dirname(dirname(dirname(__FILE__)))), DIRECTORY_SEPARATOR);
}
if(!defined('BASE_URL')) {
	// Determine the base URL by comparing SCRIPT_NAME to SCRIPT_FILENAME and getting the common
	// elements
	if(substr($_SERVER['SCRIPT_FILENAME'],0,strlen(BASE_PATH)) == BASE_PATH) {
		$urlSegmentToRemove = substr($_SERVER['SCRIPT_FILENAME'],strlen(BASE_PATH));
		if(substr($_SERVER['SCRIPT_NAME'],-strlen($urlSegmentToRemove)) == $urlSegmentToRemove) {
			$baseURL = substr($_SERVER['SCRIPT_NAME'], 0, -strlen($urlSegmentToRemove));
			define('BASE_URL', rtrim($baseURL, DIRECTORY_SEPARATOR));
		}
	}

	// If that didn't work, failover to the old syntax.  Hopefully this isn't necessary, and maybe
	// if can be phased out?
	if(!defined('BASE_URL')) {
		$dir = (strpos($_SERVER['SCRIPT_NAME'], 'index.php') !== false)
			? dirname($_SERVER['SCRIPT_NAME'])
			: dirname(dirname($_SERVER['SCRIPT_NAME']));
		define('BASE_URL', rtrim($dir, DIRECTORY_SEPARATOR));
	}
}
define('MODULES_DIR', 'modules');
define('MODULES_PATH', BASE_PATH . '/' . MODULES_DIR);
define('THEMES_DIR', 'themes');
define('THEMES_PATH', BASE_PATH . '/' . THEMES_DIR);
define('SAPPHIRE_DIR', 'sapphire');
define('SAPPHIRE_PATH', BASE_PATH . '/' . SAPPHIRE_DIR);
define('CMS_DIR', 'cms');
define('CMS_PATH', BASE_PATH . '/' . CMS_DIR);
define('THIRDPARTY_DIR', SAPPHIRE_DIR . '/thirdparty');
define('THIRDPARTY_PATH', BASE_PATH . '/' . THIRDPARTY_DIR);
define('ASSETS_DIR', 'assets');
define('ASSETS_PATH', BASE_PATH . '/' . ASSETS_DIR);

///////////////////////////////////////////////////////////////////////////////
// INCLUDES

set_include_path(BASE_PATH . '/sapphire' . PATH_SEPARATOR
. BASE_PATH . '/sapphire/parsers' . PATH_SEPARATOR
. BASE_PATH . '/sapphire/thirdparty' . PATH_SEPARATOR
. get_include_path());

/**
 * Define the temporary folder if it wasn't defined yet
 */
require_once(dirname(__FILE__).'/TempPath.php');

if(!defined('TEMP_FOLDER')) {
	define('TEMP_FOLDER', getTempFolder());
}
