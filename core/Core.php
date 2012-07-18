<?php
/**
 * This file is the Framework bootstrap.  It will get your environment ready to call Director::direct().
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
 * - FRAMEWORK_DIR: Path relative to webroot, e.g. "framework"
 * - FRAMEWORK_PATH:Absolute filepath, e.g. "/var/www/my-webroot/framework"
 * - FRAMEWORK_ADMIN_DIR: Path relative to webroot, e.g. "framework/admin"
 * - FRAMEWORK_ADMIN_PATH: Absolute filepath, e.g. "/var/www/my-webroot/framework/admin"
 * - THIRDPARTY_DIR: Path relative to webroot, e.g. "framework/thirdparty"
 * - THIRDPARTY_PATH: Absolute filepath, e.g. "/var/www/my-webroot/framework/thirdparty"
 * 
 * @todo This file currently contains a lot of bits and pieces, and its various responsibilities should probably be
 * moved into different subsystems.
 * @todo A lot of this stuff is very order-independent; for example, the require_once calls have to happen after the defines.'
 * This could be decoupled.
 * @package framework
 * @subpackage core
 */

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT CONFIG

// ALL errors are reported, including E_STRICT by default *unless* the site is in
// live mode, where reporting is limited to fatal errors and warnings (see later in this file)
error_reporting(E_ALL | E_STRICT);

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
		while($testPath && $testPath != '/' && !preg_match('/^[A-Z]:\\\\$/', $testPath)) {
			if(isset($_FILE_TO_URL_MAPPING[$testPath])) {
				$url = $_FILE_TO_URL_MAPPING[$testPath] 
					. str_replace(DIRECTORY_SEPARATOR, '/', substr($fullPath,strlen($testPath)));
				
				$components = parse_url($url);
				$_SERVER['HTTP_HOST'] = $components['host'];
				if(!empty($components['port'])) $_SERVER['HTTP_HOST'] .= ':' . $components['port'];
				$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $components['path'];
				if(!empty($components['port'])) $_SERVER['REQUEST_PORT'] = $components['port'];
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
		// Get the first host, in case there's multiple separated through commas
		$_SERVER['HTTP_HOST'] = strtok($_SERVER['HTTP_X_FORWARDED_HOST'], ',');
	}
}

/**
 * Define system paths
 */
if(!defined('BASE_PATH')) {
	// Assuming that this file is framework/core/Core.php we can then determine the base path
	$candidateBasePath = rtrim(dirname(dirname(dirname(__FILE__))), DIRECTORY_SEPARATOR);
	// We can't have an empty BASE_PATH.  Making it / means that double-slashes occur in places but that's benign.
	// This likely only happens on chrooted environemnts
	if($candidateBasePath == '') $candidateBasePath = DIRECTORY_SEPARATOR;
	define('BASE_PATH', $candidateBasePath);
}
if(!defined('BASE_URL')) {
	// Determine the base URL by comparing SCRIPT_NAME to SCRIPT_FILENAME and getting common elements
	$path = realpath($_SERVER['SCRIPT_FILENAME']);
	if(substr($path, 0, strlen(BASE_PATH)) == BASE_PATH) {
		$urlSegmentToRemove = substr($path, strlen(BASE_PATH));
		if(substr($_SERVER['SCRIPT_NAME'], -strlen($urlSegmentToRemove)) == $urlSegmentToRemove) {
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
// Relies on this being in a subdir of the framework.
// If it isn't, or is symlinked to a folder with a different name, you must define FRAMEWORK_DIR
if(!defined('FRAMEWORK_DIR')) {
	define('FRAMEWORK_DIR', basename(dirname(dirname(__FILE__))));
}
define('FRAMEWORK_PATH', BASE_PATH . '/' . FRAMEWORK_DIR);
define('FRAMEWORK_ADMIN_DIR', FRAMEWORK_DIR . '/admin');
define('FRAMEWORK_ADMIN_PATH', BASE_PATH . '/' . FRAMEWORK_ADMIN_DIR);

// These are all deprecated. Use the FRAMEWORK_ versions instead.
define('SAPPHIRE_DIR', FRAMEWORK_DIR);
define('SAPPHIRE_PATH', FRAMEWORK_PATH);
define('SAPPHIRE_ADMIN_DIR', FRAMEWORK_ADMIN_DIR);
define('SAPPHIRE_ADMIN_PATH', FRAMEWORK_ADMIN_PATH);

define('THIRDPARTY_DIR', FRAMEWORK_DIR . '/thirdparty');
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

/**
 * Ensure we don't run into xdebug's fairly conservative infinite recursion protection limit
 */
increase_xdebug_nesting_level_to(200);

/**
 * Set default encoding
 */
mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

/**
 * Enable better garbage collection
 */
gc_enable();

///////////////////////////////////////////////////////////////////////////////
// INCLUDES

if(defined('CUSTOM_INCLUDE_PATH')) {
	$includePath = CUSTOM_INCLUDE_PATH . PATH_SEPARATOR
		. FRAMEWORK_PATH . PATH_SEPARATOR
		. FRAMEWORK_PATH . '/parsers' . PATH_SEPARATOR
		. THIRDPARTY_PATH . PATH_SEPARATOR
		. get_include_path();
} else {
	$includePath = FRAMEWORK_PATH . PATH_SEPARATOR
		. FRAMEWORK_PATH . '/parsers' . PATH_SEPARATOR
		. THIRDPARTY_PATH . PATH_SEPARATOR
		. get_include_path();
}

set_include_path($includePath);

// Include the files needed the initial manifest building, as well as any files
// that are needed for the boostrap process on every request.
require_once 'cache/Cache.php';
require_once 'core/Object.php';
require_once 'core/ClassInfo.php';
require_once 'view/TemplateGlobalProvider.php';
require_once 'control/Director.php';
require_once 'dev/Debug.php';
require_once 'dev/DebugView.php';
require_once 'dev/Backtrace.php';
require_once 'dev/ZendLog.php';
require_once 'dev/Log.php';
require_once 'filesystem/FileFinder.php';
require_once 'core/manifest/ClassLoader.php';
require_once 'core/manifest/ClassManifest.php';
require_once 'core/manifest/ManifestFileFinder.php';
require_once 'core/manifest/TemplateLoader.php';
require_once 'core/manifest/TemplateManifest.php';
require_once 'core/manifest/TokenisedRegularExpression.php';
require_once 'control/injector/Injector.php';

// Initialise the dependency injector as soon as possible, as it is 
// subsequently used by some of the following code
$default_options = array('locator' => 'SilverStripeServiceConfigurationLocator');
Injector::inst($default_options); 

///////////////////////////////////////////////////////////////////////////////
// MANIFEST

// Regenerate the manifest if ?flush is set, or if the database is being built.
// The coupling is a hack, but it removes an annoying bug where new classes
// referenced in _config.php files can be referenced during the build process.
$flush = (isset($_GET['flush']) || isset($_REQUEST['url']) && (
	$_REQUEST['url'] == 'dev/build' || $_REQUEST['url'] == BASE_URL . '/dev/build'
));
$manifest = new SS_ClassManifest(BASE_PATH, false, $flush);

$loader = SS_ClassLoader::instance();
$loader->registerAutoloader();
$loader->pushManifest($manifest);

// Now that the class manifest is up, load the configuration
$configManifest = new SS_ConfigManifest(BASE_PATH, false, $flush);
Config::inst()->pushConfigManifest($configManifest);

SS_TemplateLoader::instance()->pushManifest(new SS_TemplateManifest(
	BASE_PATH, false, isset($_GET['flush'])
));

// If in live mode, ensure deprecation, strict and notices are not reported
if(Director::isLive()) {
	error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT | E_NOTICE));
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
	Deprecation::notice(3.0, 'Please use PHP function get_sys_temp_dir() instead.');
	return sys_get_temp_dir();
}

/**
 * Returns the temporary folder that silverstripe should use for its cache files
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

	$sysTmp = sys_get_temp_dir();
	$worked = true;
	$ssTmp = "$sysTmp/$cachefolder";

	if(!@file_exists($ssTmp)) {
		$worked = @mkdir($ssTmp);
	}

	if(!$worked) {
		$ssTmp = BASE_PATH . "/silverstripe-cache";
		$worked = true;
		if(!@file_exists($ssTmp)) {
			$worked = @mkdir($ssTmp);
		}
	}

	if(!$worked) {
		throw new Exception(
			'Permission problem gaining access to a temp folder. ' .
			'Please create a folder named silverstripe-cache in the base folder ' .
			'of the installation and ensure it has the correct permissions'
		);
	}

	return $ssTmp;
}

/**
 * @deprecated 3.0 Please use {@link SS_ClassManifest::getItemPath()}.
 */
function getClassFile($className) {
	Deprecation::notice('3.0', 'Use SS_ClassManifest::getItemPath() instead.');
	return SS_ClassLoader::instance()->getManifest()->getItemPath($className);
}

/**
 * Creates a class instance by the "singleton" design pattern.
 * It will always return the same instance for this class,
 * which can be used for performance reasons and as a simple
 * way to access instance methods which don't rely on instance
 * data (e.g. the custom SilverStripe static handling).
 *
 * @param string $className
 * @return Object
 */
function singleton($className) {
	if($className == "Config") user_error("Don't pass Config to singleton()", E_USER_ERROR);
	if(!isset($className)) user_error("singleton() Called without a class", E_USER_ERROR);
	if(!is_string($className)) user_error("singleton() passed bad class_name: " . var_export($className,true), E_USER_ERROR);
	return Injector::inst()->get($className);
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
function _t($entity, $string = "", $context = "", $injection = "") {
	return i18n::_t($entity, $string, $context, $injection);
}

/**
 * Increase the memory limit to the given level if it's currently too low.
 * Only increases up to the maximum defined in {@link set_increase_memory_limit_max()},
 * and defaults to the 'memory_limit' setting in the PHP configuration.
 * 
 * @param A memory limit string, such as "64M".  If omitted, unlimited memory will be set.
 * @return Boolean TRUE indicates a successful change, FALSE a denied change.
 */
function increase_memory_limit_to($memoryLimit = -1) {
	$curLimit = ini_get('memory_limit');
	
	// Can't go higher than infinite
	if($curLimit == -1 ) return true;
	
	// Check hard maximums
	$max = get_increase_memory_limit_max();

	if($max && $max != -1 && trANSLATE_MEMSTRING($memoryLimit) > translate_memstring($max)) return false;
	
	// Increase the memory limit if it's too low
	if($memoryLimit == -1 || translate_memstring($memoryLimit) > translate_memstring($curLimit)) {
		ini_set('memory_limit', $memoryLimit);
	}

	return true;
}

$_increase_memory_limit_max = ini_get('memory_limit');

/**
 * Set the maximum allowed value for {@link increase_memory_limit_to()}.
 * The same result can also be achieved through 'suhosin.memory_limit'
 * if PHP is running with the Suhosin system.
 * 
 * @param Memory limit string
 */
function set_increase_memory_limit_max($memoryLimit) {
	global $_increase_memory_limit_max;
	$_increase_memory_limit_max = $memoryLimit;
}

/**
 * @return Memory limit string
 */
function get_increase_memory_limit_max() {
	global $_increase_memory_limit_max;
	return $_increase_memory_limit_max;
}

/**
 * Increases the XDebug parameter max_nesting_level, which limits how deep recursion can go.
 * Only does anything if (a) xdebug is installed and (b) the new limit is higher than the existing limit
 *
 * @param int $limit - The new limit to increase to
 */
function increase_xdebug_nesting_level_to($limit) {
	if (function_exists('xdebug_enable')) {
		$current = ini_get('xdebug.max_nesting_level');
		if ((int)$current < $limit) ini_set('xdebug.max_nesting_level', $limit);
	}
}

/**
 * Turn a memory string, such as 512M into an actual number of bytes.
 * 
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
 * Increase the time limit of this script. By default, the time will be unlimited.
 * Only works if 'safe_mode' is off in the PHP configuration.
 * Only values up to {@link get_increase_time_limit_max()} are allowed.
 * 
 * @param $timeLimit The time limit in seconds.  If omitted, no time limit will be set.
 * @return Boolean TRUE indicates a successful change, FALSE a denied change.
 */
function increase_time_limit_to($timeLimit = null) {
	$max = get_increase_time_limit_max();
	if($max != -1 && $timeLimit > $max) return false;
	
	if(!ini_get('safe_mode')) {
		if(!$timeLimit) {
			set_time_limit(0);
			return true;
		} else {
			$currTimeLimit = ini_get('max_execution_time');
			// Only increase if its smaller
			if($currTimeLimit && $currTimeLimit < $timeLimit) {
				set_time_limit($timeLimit);
			} 
			return true;
		}
	} else {
		return false;
	}
}

$_increase_time_limit_max = -1;

/**
 * Set the maximum allowed value for {@link increase_timeLimit_to()};
 * 
 * @param Int Limit in seconds
 */
function set_increase_time_limit_max($timeLimit) {
	global $_increase_time_limit_max;
	$_increase_time_limit_max = $timeLimit;
}

/**
 * @return Int Limit in seconds
 */
function get_increase_time_limit_max() {
	global $_increase_time_limit_max;
	return $_increase_time_limit_max;
}
