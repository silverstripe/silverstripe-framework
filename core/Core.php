<?php
/**
 * This file contains several methods that control the core behaviour of Sapphire.
 *
 * @package sapphire
 * @subpackage core
 */

/**
 * Returns the temporary folder that sapphire/silverstripe should use for its cache files
 * This is loaded into the TEMP_FOLDER define on start up
 */
function getTempFolder() {
	$cachefolder = "silverstripe-cache" . str_replace(array("/",":", "\\"),"-", substr($_SERVER['SCRIPT_FILENAME'], 0, strlen($_SERVER['SCRIPT_FILENAME']) - strlen('/sapphire/main.php')));
	$ssTmp = dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . "/silverstripe-cache";
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
    	$ssTmp = dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . "/silverstripe-cache";
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
 * Define the temporary folder if it wasn't defined yet
 */
if(!defined('TEMP_FOLDER')) {
	define('TEMP_FOLDER', getTempFolder());
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
    if(!class_exists($className)) user_error("Bad class to singleton() - $className", E_USER_ERROR);
	if(!isset($_SINGLETONS[$className])) {
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
 * Priorities definition. These constants are used in calls to _t() as an optional argument
 */
define('PR_HIGH',100);
define('PR_MEDIUM',50);
define('PR_LOW',10);

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
