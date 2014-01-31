<?php
/**
 * Returns the temporary folder path that silverstripe should use for its cache files.
 *
 * @package framework
 * @subpackage core
 *
 * @param $base The base path to use for determining the temporary path
 * @return string Path to temp
 */
function getTempFolder($base = null) {
	$parent = getTempParentFolder($base);
	
	// The actual temp folder is a subfolder of getTempParentFolder(), named by username
	$subfolder = $parent . DIRECTORY_SEPARATOR . getTempFolderUsername();

	if(!@file_exists($subfolder)) {	
		mkdir($subfolder);
	}

	return $subfolder;
}

/**
 * Returns as best a representation of the current username as we can glean.
 *
 * @package framework
 * @subpackage core
 */
function getTempFolderUsername() {
	$user = getenv('APACHE_RUN_USER');
	if(!$user) $user = getenv('USER');
	if(!$user) $user = getenv('USERNAME');
	if(!$user && function_exists('posix_getuid')) {
		$userDetails = posix_getpwuid(posix_getuid());
		$user = $userDetails['name'];
	}
	if(!$user) $user = 'unknown';
	$user = preg_replace('/[^A-Za-z0-9_\-]/', '', $user);
	return $user;
}

/**
 * Return the parent folder of the temp folder.
 * The temp folder will be a subfolder of this, named by username.
 * This structure prevents permission problems.
 *
 * @package framework
 * @subpackage core
 */
function getTempParentFolder($base = null) {
	if(!$base && defined('BASE_PATH')) $base = BASE_PATH;

	$tempPath = '';
	$worked = true;

	// first, try finding a silverstripe-cache dir built off the base path
	$tempPath = $base . DIRECTORY_SEPARATOR . 'silverstripe-cache';
	if(@file_exists($tempPath)) {
		if((fileperms($tempPath) & 0777) != 0777) {
			@chmod($tempPath, 0777);
		}
		return $tempPath;
	}

	// failing the above, try finding a namespaced silverstripe-cache dir in the system temp
	$cacheFolder = DIRECTORY_SEPARATOR . 'silverstripe-cache' . str_replace(array(' ', '/', ':', '\\'), '-', $base);
	$tempPath = sys_get_temp_dir() . $cacheFolder;
	if(!@file_exists($tempPath)) {
		$oldUMask = umask(0);
		$worked = @mkdir($tempPath, 0777);
		umask($oldUMask);

	// if the folder already exists, correct perms
	} else {
		if((fileperms($tempPath) & 0777) != 0777) {
			@chmod($tempPath, 0777);
		}
	}

	// failing to use the system path, attempt to create a local silverstripe-cache dir
	if(!$worked) {
		$worked = true;
		$tempPath = $base . DIRECTORY_SEPARATOR . 'silverstripe-cache';
		if(!@file_exists($tempPath)) {
			$oldUMask = umask(0);
			$worked = @mkdir($tempPath, 0777);
			umask($oldUMask);
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
