<?php
/**
 * This file is the Sapphire bootstrap.  It will get your environment ready to call Director::direct().
 *
 * It takes care of:
 *  - Including _ss_environment.php
 *  - Normalisation of $_SERVER values
 *  - Initialisation of necessary constants (mostly paths)
 *  - Checking of PHP memory limit
 *  - Including all the files needed to get the manifest built
 *  - Building and including the manifest
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
 * @todo This file currently contains a lot of bits and pieces, and its various responsibilities should probably be
 * moved into different subsystems.
 * @todo A lot of this stuff is very order-independent; for example, the require_once calls have to happen after the defines.'
 * This could be decoupled.
 * @package sapphire
 * @subpackage core
 */

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT CONFIG

if(defined('E_DEPRECATED')) error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT));
else error_reporting(E_ALL);
/*
 * This is for versions of PHP prior to version 5.2
 * Creating this here will allow both web requests and cron jobs to inherit it.
 */
if (!function_exists('array_fill_keys')) {
	function array_fill_keys($keys,$value) {
		//Sometimes we get passed an empty array, and if that's the case, you'll get an error message
		if(sizeof($keys)==0)
			return Array();
		else
			return array_combine($keys,array_fill(0,count($keys),$value));
	}
}

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

/**
 * Define the temporary folder if it wasn't defined yet
 */
if(!defined('TEMP_FOLDER')) {
	define('TEMP_FOLDER', getTempFolder());
}

/**
 * Priorities definition. These constants are used in calls to _t() as an optional argument
 */
define('PR_HIGH',100);
define('PR_MEDIUM',50);
define('PR_LOW',10);

/**
 * Ensure we have enough memory
 */
increase_memory_limit_to('64M');

///////////////////////////////////////////////////////////////////////////////
// INCLUDES

set_include_path(BASE_PATH . '/sapphire' . PATH_SEPARATOR
	. BASE_PATH . '/sapphire/parsers' . PATH_SEPARATOR
	. BASE_PATH . '/sapphire/thirdparty' . PATH_SEPARATOR
	. get_include_path());

/**
 * Sapphire class autoloader.  Requires the ManifestBuilder to work.
 * $_CLASS_MANIFEST must have been loaded up by ManifestBuilder for this to successfully load
 * classes.  Classes will be loaded from any PHP file within the application.
 * If your class contains an underscore, for example, Page_Controller, then the filename is
 * expected to be the stuff before the underscore.  In this case, Page.php.
 * 
 * Class names are converted to lowercase for lookup to adhere to PHP's case-insensitive
 * way of dealing with them.
 */
function sapphire_autoload($className) {
	global $_CLASS_MANIFEST;
	$lClassName = strtolower($className);
	if(isset($_CLASS_MANIFEST[$lClassName])) include_once($_CLASS_MANIFEST[$lClassName]);
	else if(isset($_CLASS_MANIFEST[$className])) include_once($_CLASS_MANIFEST[$className]);
}

spl_autoload_register('sapphire_autoload');

require_once("core/ManifestBuilder.php");
require_once("core/ClassInfo.php");
require_once('core/Object.php');
require_once('core/control/Director.php');
require_once('filesystem/Filesystem.php');
require_once("core/Session.php");

///////////////////////////////////////////////////////////////////////////////
// MANIFEST

/**
 * Include the manifest
 */
ManifestBuilder::include_manifest();

/**
 * ?debugmanifest=1 hook
 */
if(isset($_GET['debugmanifest'])) Debug::show(file_get_contents(MANIFEST_FILE));

// If this is a dev site, enable php error reporting
// This is necessary to force developers to acknowledge and fix
// notice level errors (you can override this directive in your _config.php)
if (Director::isLive()) {
	if(defined('E_DEPRECATED')) error_reporting(E_ALL & ~(E_NOTICE | E_DEPRECATED | E_STRICT));
	else error_reporting(E_ALL & ~E_NOTICE);
}
///////////////////////////////////////////////////////////////////////////////
// POST-MANIFEST COMMANDS

/**
 * Load error handlers
 */
Debug::loadErrorHandlers();

///////////////////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS

function getSysTempDir() {
	if(function_exists('sys_get_temp_dir')) {
		$sysTmp = sys_get_temp_dir();
	} elseif(isset($_ENV['TMP'])) {
		$sysTmp = $_ENV['TMP'];    	
	} else {
		$tmpFile = tempnam('adfadsfdas','');
		unlink($tmpFile);
		$sysTmp = dirname($tmpFile);
	}
	return $sysTmp;
}

/**
 * Returns the temporary folder that sapphire/silverstripe should use for its cache files
 * This is loaded into the TEMP_FOLDER define on start up
 * 
 * @param $base The base path to use as the basis for the temp folder name.  Defaults to BASE_PATH,
 * which is usually fine; however, the $base argument can be used to help test.
 */
function getTempFolder($base = null) {
	if(!$base) $base = BASE_PATH;

	if($base) {
		$cachefolder = "silverstripe-cache" . str_replace(array(' ', "/", ":", "\\"), "-", $base);
	} else {
		$cachefolder = "silverstripe-cache";
	}

	$ssTmp = BASE_PATH . "/silverstripe-cache";
	if(@file_exists($ssTmp)) {
		return $ssTmp;
	}

	$sysTmp = getSysTempDir();
	$worked = true;
	$ssTmp = "$sysTmp/$cachefolder";

	if(!@file_exists($ssTmp)) {
		@$worked = mkdir($ssTmp);
	}

	if(!$worked) {
		$ssTmp = BASE_PATH . "/silverstripe-cache";
		$worked = true;
		if(!@file_exists($ssTmp)) {
			@$worked = mkdir($ssTmp);
		}
	}

	if(!$worked) {
		user_error("Permission problem gaining access to a temp folder. " .
			"Please create a folder named silverstripe-cache in the base folder "  .
			"of the installation and ensure it has the correct permissions", E_USER_ERROR);
	}

	return $ssTmp;
}

/**
 * Return the file where that class is stored.
 * 
 * @param String $className Case-insensitive lookup.
 * @return String
 */
function getClassFile($className) {
	global $_CLASS_MANIFEST;
	$lClassName = strtolower($className);
	if(isset($_CLASS_MANIFEST[$lClassName])) return $_CLASS_MANIFEST[$lClassName];
	else if(isset($_CLASS_MANIFEST[$className])) return $_CLASS_MANIFEST[$className];
}

/**
 * Creates a class instance by the "singleton" design pattern.
 * It will always return the same instance for this class,
 * which can be used for performance reasons and as a simple
 * way to access instance methods which don't rely on instance
 * data (e.g. the custom SilverStripe static handling).
 *
 * @uses Object::strong_create()
 *
 * @param string $className
 * @return Object
 */
function singleton($className) {
	global $_SINGLETONS;
	if(!isset($className)) user_error("singleton() Called without a class", E_USER_ERROR);
	if(!is_string($className)) user_error("singleton() passed bad class_name: " . var_export($className,true), E_USER_ERROR);
	if(!isset($_SINGLETONS[$className])) {
		if(!class_exists($className)) user_error("Bad class to singleton() - $className", E_USER_ERROR);
		$_SINGLETONS[$className] = Object::strong_create($className,null, true);
		if(!$_SINGLETONS[$className]) user_error("singleton() Unknown class '$className'", E_USER_ERROR);
	}
	return $_SINGLETONS[$className];
}

function project() {
	global $project;
	return $project;
}

function stripslashes_recursively(&$array) {
	foreach($array as $k => $v) {
		if(is_array($v)) stripslashes_recursively($array[$k]);
		else $array[$k] = stripslashes($v);
	}
}

/**
 * @see i18n::_t()
 */
function _t($entity, $string = "", $priority = 40, $context = "") {
	return i18n::_t($entity, $string, $priority, $context);
}

/**
 * Increase the memory limit to the given level if it's currently too low.
 * @param A memory limit string, such as "64M".  If omitted, unlimited memory will be set.
 */
function increase_memory_limit_to($memoryLimit = -1) {
	$curLimit = ini_get('memory_limit');
	
	// Can't go higher than infinite
	if($curLimit == -1) return;
	
	// Increase the memory limit if it's too low
	if($memoryLimit == -1 || translate_memstring($memoryLimit) > translate_memstring($curLimit)) {
		ini_set('memory_limit', $memoryLimit);
	}
}

/**
 * Turn a memory string, such as 512M into an actual number of bytes.
 * @param A memory limit string, such as "64M"
 */
function translate_memstring($memString) {
	switch(strtolower(substr($memString, -1))) {
		case "k": return round(substr($memString, 0, -1)*1024);
		case "m": return round(substr($memString, 0, -1)*1024*1024);
		case "g": return round(substr($memString, 0, -1)*1024*1024*1024);
		default: return round($memString);
	}
}

/**
 * Increase the time limit of this script.  By default, the time will be unlimited.
 * @param $timeLimit The time limit in seconds.  If omitted, no time limit will be set.
 */
function increase_time_limit_to($timeLimit = null) {
	if(!ini_get('safe_mode')) {
		if(!$timeLimit) {
			set_time_limit(0);
		} else {
			$currTimeLimit = ini_get('max_execution_time');
			if($currTimeLimit && $currTimeLimit < $timeLimit) {
				set_time_limit($timeLimit);
			}
		}
	}
}
