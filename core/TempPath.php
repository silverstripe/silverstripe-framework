<?php
/**
 * Returns the temporary folder path that silverstripe should use for its cache files.
 *
 * @param $base The base path to use for determining the temporary path
 * @return string Path to temp
 */
function getTempFolder($base = null) {
	if(!$base && defined('BASE_PATH')) $base = BASE_PATH;

	$tempPath = '';
	$worked = true;

	// first, try finding a silverstripe-cache dir built off the base path
	$tempPath = $base . '/silverstripe-cache';
	if(@file_exists($tempPath)) {
		return $tempPath;
	}

	// failing the above, try finding a namespaced silverstripe-cache dir in the system temp
	$cacheFolder = '/silverstripe-cache' . str_replace(array(' ', '/', ':', '\\'), '-', $base);
	$tempPath = sys_get_temp_dir() . $cacheFolder;
	if(!@file_exists($tempPath)) {
		$worked = @mkdir($tempPath);
	}

	// failing to use the system path, attempt to create a local silverstripe-cache dir
	if(!$worked) {
		$worked = true;
		$tempPath = $base . '/silverstripe-cache';
		if(!@file_exists($tempPath)) {
			$worked = @mkdir($tempPath);
		}
	}

	if(!$worked) {
		throw new Exception(
			'Permission problem gaining access to a temp folder. ' .
			'Please create a folder named silverstripe-cache in the base folder ' .
			'of the installation and ensure it has the correct permissions'
		);
	}

	return $tempPath;
}

