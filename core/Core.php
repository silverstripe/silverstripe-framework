<?php
/**
 * Returns the temporary folder that sapphire/silverstripe should use for its cache files
 * This is loaded into the TEMP_FOLDER define on start up
 */
function getTempFolder() {
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
    $ssTmp = "$sysTmp/silverstripe-cache";
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

define('TEMP_FOLDER', getTempFolder());

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
?>
