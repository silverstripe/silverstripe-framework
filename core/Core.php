<?php
/**
 * This file is the Sapphire bootstrap.  It will get your environment ready to call Director::direct().
 *
 * It takes care of:
 *  - Including _ss_environment.php
 *  - Normalisation of $_SERVER values
 *  - Initialisation of TEMP_FOLDER, BASE_URL, BASE_PATH, and other SilverStripe defines
 *  - Checking of PHP memory limit
 *  - Including all the files needed to get the manifest built
 *  - Building and including the manifest
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

/**
 * Include _ss_environment.php files
 */
$envFiles = array('../_ss_environment.php', '../../_ss_environment.php', '../../../_ss_environment.php');
foreach($envFiles as $envFile) {
	if(file_exists($envFile)) {
		include($envFile);
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
		$fullPath = $testPath = $_SERVER['SCRIPT_FILENAME'];
		while($testPath && $testPath != "/") {
			if(isset($_FILE_TO_URL_MAPPING[$testPath])) {
				$url = $_FILE_TO_URL_MAPPING[$testPath] . substr($fullPath,strlen($testPath));
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
	if (get_magic_quotes_gpc()) {
		if($_REQUEST) stripslashes_recursively($_REQUEST);
		if($_GET) stripslashes_recursively($_GET);
		if($_POST) stripslashes_recursively($_POST);
	}
}

/**
 * Define system paths
 */
define('BASE_PATH', dirname(dirname($_SERVER['SCRIPT_FILENAME'])));
define('BASE_URL', dirname(dirname($_SERVER['SCRIPT_NAME'])));
define('MODULES_DIR', 'modules');
define('MODULES_PATH', BASE_PATH . '/' . MODULES_DIR);
define('THIRDPARTY_DIR', 'jsparty');
define('THIRDPARTY_PATH', BASE_PATH . '/' . THIRDPARTY_DIR);
define('THEMES_DIR', 'themes');
define('THEMES_PATH', BASE_PATH . '/' . THEMES_DIR);
define('SAPPHIRE_DIR', 'sapphire');
define('SAPPHIRE_PATH', BASE_PATH . '/' . SAPPHIRE_DIR);
define('CMS_DIR', 'cms');
define('CMS_PATH', BASE_PATH . '/' . CMS_DIR);
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
$memString = ini_get("memory_limit");
switch(strtolower(substr($memString, -1))) {
case "k":
	$memory = round(substr($memString, 0, -1)*1024);
	break;
case "m":
	$memory = round(substr($memString, 0, -1)*1024*1024);
	break;
case "g":
	$memory = round(substr($memString, 0, -1)*1024*1024*1024);
	break;
default:
	$memory = round($memString);
}

// Check we have at least 32M
if ($memory < (32 * 1024 * 1024)) {
	// Increase memory limit
	ini_set('memory_limit', '32M');
}

///////////////////////////////////////////////////////////////////////////////
// INCLUDES

require_once("core/ManifestBuilder.php");
require_once("core/ClassInfo.php");
require_once('core/Object.php');
require_once('core/control/Director.php');
require_once('filesystem/Filesystem.php');
require_once("core/Session.php");

///////////////////////////////////////////////////////////////////////////////
// MANIFEST

/**
 * Build the manifest
 */
if(ManifestBuilder::staleManifest()){
	ManifestBuilder::compileManifest();
}		

require_once(MANIFEST_FILE);

/**
 * ?debugmanifest=1 hook
 */
if(isset($_GET['debugmanifest'])) Debug::show(file_get_contents(MANIFEST_FILE));


///////////////////////////////////////////////////////////////////////////////
// POST-MANIFEST COMMANDS

/**
 * Load error handlers
 */
Debug::loadErrorHandlers();

///////////////////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS

/**
 * Returns the temporary folder that sapphire/silverstripe should use for its cache files
 * This is loaded into the TEMP_FOLDER define on start up
 */
function getTempFolder() {
	if(preg_match('/^(.*)\/sapphire\/[^\/]+$/', $_SERVER['SCRIPT_FILENAME'], $matches)) {
		$cachefolder = "silverstripe-cache" . str_replace(array(' ',"/",":", "\\"),"-", $matches[1]);
	} else {
		$cachefolder = "silverstripe-cache";
	}
	
	$ssTmp = BASE_PATH . "/silverstripe-cache";
    if(@file_exists($ssTmp)) {
    	return $ssTmp;
    }
	
    if(function_exists('sys_get_temp_dir')) {
        $sysTmp = sys_get_temp_dir();
    } elseif(isset($_ENV['TMP'])) {
		$sysTmp = $_ENV['TMP'];    	
    } else {
        $tmpFile = tempnam('adfadsfdas','');
        unlink($tmpFile);
        $sysTmp = dirname($tmpFile);
    }

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
 * Sapphire class autoloader.  Requires the ManifestBuilder to work.
 * $_CLASS_MANIFEST must have been loaded up by ManifestBuilder for this to successfully load
 * classes.  Classes will be loaded from any PHP file within the application.
 * If your class contains an underscore, for example, Page_Controller, then the filename is
 * expected to be the stuff before the underscore.  In this case, Page.php.
 */
function __autoload($className) {
	global $_CLASS_MANIFEST;
	if(($pos = strpos($className,'_')) !== false) $className = substr($className,0,$pos);
	if(isset($_CLASS_MANIFEST[$className])) include_once($_CLASS_MANIFEST[$className]);
}

/**
 * Return the file where that class is stored
 */
function getClassFile($className) {
	global $_CLASS_MANIFEST;
	if(($pos = strpos($className,'_')) !== false) $className = substr($className,0,$pos);
	if($_CLASS_MANIFEST[$className]) return $_CLASS_MANIFEST[$className];
}

function singleton($className) {
	static $_SINGLETONS;
	if(!isset($className)) user_error("singleton() Called without a class", E_USER_ERROR);
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
 * This is the main translator function. Returns the string defined by $class and $entity according to the currently set locale
 *
 * @param string $entity Entity that identifies the string. It must be in the form "Namespace.Entity" where Namespace will be usually
 * 						 the class name where this string is used and Entity identifies the string inside the namespace.
 * @param string $string The original string itself. In a usual call this is a mandatory parameter, but if you are reusing a string which
 *				 has already been "declared" (using another call to this function, with the same class and entity), you can omit it.
 * @param string $priority Optional parameter to set a translation priority. If a string is widely used, should have a high priority (PR_HIGH),
 * 				    in this way translators will be able to prioritise this strings. If a string is rarely shown, you should use PR_LOW.
 *				    You can use PR_MEDIUM as well. Leaving this field blank will be interpretated as a "normal" priority (less than PR_MEDIUM).
 * @param string $context If the string can be difficult to translate by any reason, you can help translators with some more info using this param
 *
 * @return string The translated string, according to the currently set locale {@link i18n::set_locale()}
 */
function _t($entity, $string = "", $priority = 40, $context = "") {
	global $lang;
	$locale = i18n::get_locale();
	$entityParts = explode('.',$entity);
	$realEntity = array_pop($entityParts);
	$class = implode('.',$entityParts);
	if(!isset($lang[$locale][$class])) i18n::include_by_class($class);
	$transEntity = isset($lang[i18n::get_locale()][$class][$realEntity]) ? $lang[i18n::get_locale()][$class][$realEntity] : $string;
	return (is_array($transEntity) ? $transEntity[0] : $transEntity);
}


?>