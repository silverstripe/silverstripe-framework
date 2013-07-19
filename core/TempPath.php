<?php

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

	$ssTmp = $base . "/silverstripe-cache";
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
		$ssTmp = $base . "/silverstripe-cache";
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
