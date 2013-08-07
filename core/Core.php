<?php
/**
 * This file is the Sapphire bootstrap.  It will get your environment ready to call Director::direct().
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
 * @package sapphire
 * @subpackage core
 */


/**
 * Set up error reporting
 */
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
 * Include Constants (if it hasn't already been included) to pull in BASE_PATH, etc
 */
require_once dirname(__FILE__).'/Constants.php';

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
 * Set default encoding
 */
if (function_exists('mb_http_output')) {
	mb_http_output('UTF-8');
	mb_internal_encoding('UTF-8');
	mb_regex_encoding('UTF-8');
}

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

/**
 * @see i18n::_t()
 */
function _t($entity, $string = "", $priority = 40, $context = "") {
	return i18n::_t($entity, $string, $priority, $context);
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
	if($max != -1 && translate_memstring($memoryLimit) > translate_memstring($max)) return false;
	
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
 * Increase the time limit of this script.  By default, the time will be unlimited.
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
