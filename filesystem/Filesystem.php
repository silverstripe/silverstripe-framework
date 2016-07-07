<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * A collection of static methods for manipulating the filesystem.
 *
 * @package framework
 * @subpackage filesystem
 */
class Filesystem extends Object {

	/**
	 * @config
	 * @var integer Integer
	 */
	private static $file_create_mask = 02775;

	/**
	 * @config
	 * @var integer Integer
	 */
	private static $folder_create_mask = 02775;

	/**
	 * @var int
	 */
	protected static $cache_folderModTime;

	/**
	 * Create a folder on the filesystem, recursively.
	 * Uses {@link Filesystem::$folder_create_mask} to set filesystem permissions.
	 * Use {@link Folder::findOrMake()} to create a {@link Folder} database
	 * record automatically.
	 *
	 * @param String $folder Absolute folder path
	 */
	public static function makeFolder($folder) {
		if(!file_exists($base = dirname($folder))) self::makeFolder($base);
		if(!file_exists($folder)) mkdir($folder, Config::inst()->get('Filesystem', 'folder_create_mask'));
	}

	/**
	 * Remove a directory and all subdirectories and files.
	 *
	 * @param String $folder Absolute folder path
	 * @param Boolean $contentsOnly If this is true then the contents of the folder will be removed but not the
	 *                              folder itself
	 */
	public static function removeFolder($folder, $contentsOnly = false) {

		// remove a file encountered by a recursive call.
		if(is_file($folder) || is_link($folder)) {
			unlink($folder);
		} else {
			$dir = opendir($folder);
			while($file = readdir($dir)) {
				if(($file == '.' || $file == '..')) continue;
				else {
					self::removeFolder($folder . '/' . $file);
				}
			}
			closedir($dir);
			if(!$contentsOnly) rmdir($folder);
		}
	}

	/**
	 * Remove a directory, but only if it is empty.
	 *
	 * @param string $folder Absolute folder path
	 * @param boolean $recursive Remove contained empty folders before attempting to remove this one
	 * @return boolean True on success, false on failure.
	 */
	public static function remove_folder_if_empty($folder, $recursive = true) {
		if (!is_readable($folder)) return false;
		$handle = opendir($folder);
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				// if an empty folder is detected, remove that one first and move on
				if($recursive && is_dir($entry) && self::remove_folder_if_empty($entry)) continue;
				// if a file was encountered, or a subdirectory was not empty, return false.
				return false;
			}
		}
		// if we are still here, the folder is empty.
		rmdir($folder);
		return true;
	}

	/**
	 * Cleanup function to reset all the Filename fields.  Visit File/fixfiles to call.
	 */
	public function fixfiles() {
		if(!Permission::check('ADMIN')) return Security::permissionFailure($this);

		$files = DataObject::get("File");
		foreach($files as $file) {
			$file->updateFilesystem();
			echo "<li>", $file->Filename;
			$file->write();
		}
		echo "<p>Done!";
	}

	/**
	 * Return the most recent modification time of anything in the folder.
	 *
	 * @param string $folder The folder, relative to the site root
	 * @param array $extensionList An option array of file extensions to limit the search to
	 * @param bool $recursiveCall Not used
	 * @return string Same as filemtime() format.
	 */
	public static function folderModTime($folder, $extensionList = null, $recursiveCall = false) {
		//$cacheID = $folder . ',' . implode(',', $extensionList);
		//if(!$recursiveCall && self::$cache_folderModTime[$cacheID]) return self::$cache_folderModTime[$cacheID];

		$modTime = 0;
		if(!Filesystem::isAbsolute($folder)) $folder = Director::baseFolder() . '/' . $folder;

		$items = scandir($folder);
		foreach($items as $item) {
			if($item[0] != '.') {
				// Recurse into folders
				if(is_dir("$folder/$item")) {
					$modTime = max($modTime, self::folderModTime("$folder/$item", $extensionList, true));

				// Check files
				} else {
					if($extensionList) $extension = strtolower(substr($item,strrpos($item,'.')+1));
					if(!$extensionList || in_array($extension, $extensionList)) {
						$modTime = max($modTime, filemtime("$folder/$item"));
					}
				}
			}
		}

		//if(!$recursiveCall) self::$cache_folderModTime[$cacheID] = $modTime;
		return $modTime;
	}

	/**
	 * Returns true if the given filename is an absolute file reference.
	 * Works on Linux and Windows.
	 *
	 * @param String $filename Absolute or relative filename, with or without path.
	 * @return Boolean
	 */
	public static function isAbsolute($filename) {
		if($_ENV['OS'] == "Windows_NT" || $_SERVER['WINDIR']) return $filename[1] == ':' && $filename[2] == '/';
		else return $filename[0] == '/';
	}
}
