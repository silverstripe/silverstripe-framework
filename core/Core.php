<?php
/**
 * This file is the Framework bootstrap.  It will get your environment ready to call Director::direct().
 *
 * It takes care of:
 *  - Including Constants.php to include _ss_environment and initialise necessary constants
 *  - Checking of PHP memory limit
 *  - Including all the files needed to get the manifest built
 *  - Building and including the manifest
 *
 * @todo This file currently contains a lot of bits and pieces, and its various responsibilities should probably be
 *       moved into different subsystems.
 * @todo A lot of this stuff is very order-dependent. This could be decoupled.
 *
 * @package framework
 * @subpackage core
 */

/**
 * All errors are reported, including E_STRICT by default *unless* the site is in
 * live mode, where reporting is limited to fatal errors and warnings (see later in this file)
 */
error_reporting(E_ALL | E_STRICT);

/**
 * Include Constants (if it hasn't already been included) to pull in BASE_PATH, etc
 */
require_once dirname(__FILE__).'/Constants.php';

global $_increase_time_limit_max;
$_increase_time_limit_max = -1;

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

// Include the files needed the initial manifest building, as well as any files
// that are needed for the boostrap process on every request.
require_once 'cache/Cache.php';
require_once 'core/Object.php';
require_once 'core/ClassInfo.php';
require_once 'core/DAG.php';
require_once 'core/Config.php';
require_once 'view/TemplateGlobalProvider.php';
require_once 'control/Director.php';
require_once 'dev/Debug.php';
require_once 'dev/DebugView.php';
require_once 'dev/Backtrace.php';
require_once 'dev/ZendLog.php';
require_once 'dev/Log.php';
require_once 'filesystem/FileFinder.php';
require_once 'core/manifest/ManifestCache.php';
require_once 'core/manifest/ClassLoader.php';
require_once 'core/manifest/ConfigManifest.php';
require_once 'core/manifest/ConfigStaticManifest.php';
require_once 'core/manifest/ClassManifest.php';
require_once 'core/manifest/ManifestFileFinder.php';
require_once 'core/manifest/TemplateLoader.php';
require_once 'core/manifest/TemplateManifest.php';
require_once 'core/manifest/TokenisedRegularExpression.php';
require_once 'control/injector/Injector.php';

// Initialise the dependency injector as soon as possible, as it is
// subsequently used by some of the following code
$injector = new Injector(array('locator' => 'SilverStripeServiceConfigurationLocator'));
Injector::set_inst($injector);

///////////////////////////////////////////////////////////////////////////////
// MANIFEST

// Regenerate the manifest if ?flush is set, or if the database is being built.
// The coupling is a hack, but it removes an annoying bug where new classes
// referenced in _config.php files can be referenced during the build process.
$requestURL = isset($_REQUEST['url']) ? trim($_REQUEST['url'], '/') : false;
$flush = (isset($_GET['flush']) || $requestURL === trim(BASE_URL . '/dev/build', '/'));

global $manifest;
$manifest = new SS_ClassManifest(BASE_PATH, false, $flush);

// Register SilverStripe's class map autoload
$loader = SS_ClassLoader::instance();
$loader->registerAutoloader();
$loader->pushManifest($manifest);

// Fall back to Composer's autoloader (e.g. for PHPUnit), if composer is used
if(file_exists(BASE_PATH . '/vendor/autoload.php')) {
	require_once BASE_PATH . '/vendor/autoload.php';
}

// Now that the class manifest is up, load the static configuration
$configManifest = new SS_ConfigStaticManifest(BASE_PATH, false, $flush);
Config::inst()->pushConfigStaticManifest($configManifest);

// And then the yaml configuration
$configManifest = new SS_ConfigManifest(BASE_PATH, false, $flush);
Config::inst()->pushConfigYamlManifest($configManifest);

// Load template manifest
SS_TemplateLoader::instance()->pushManifest(new SS_TemplateManifest(
	BASE_PATH, project(), false, $flush
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
	if(!is_string($className)) user_error("singleton() passed bad class_name: " . var_export($className,true),
		E_USER_ERROR);
	return Injector::inst()->get($className);
}

function project() {
	global $project;
	return $project;
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
	if($max != -1 && $max != null && $timeLimit > $max) return false;

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
