<?php

/**
 * @package sapphire
 * @subpackage filesystem
 */

/**
 * A collection of static methods for manipulating the filesystem.
 * @package sapphire
 * @subpackage filesystem
 */
class Filesystem extends Object {
	
	public static $file_create_mask = 02775;
	
	public static $folder_create_mask = 02775;
	
	/**
	 * Create a folder, recursively
	 */
	static function makeFolder($folder) {
		if(!file_exists($base = dirname($folder))) self::makeFolder($base);
		if(!file_exists($folder)) mkdir($folder, Filesystem::$folder_create_mask);
	}
	
	/**
	 * Remove a directory and all subdirectories and files
	 */
	static function removeFolder( $folder ) {
		
		// remove a file encountered by a recursive call.
		if( !is_dir( $folder ) || is_link($folder) )
			unlink( $folder );
		else {
			
			$dir = opendir( $folder );
			
			while( $file = readdir( $dir ) )
				if( !preg_match( '/\.{1,2}$/', $file ) )
					self::removeFolder( $folder.'/'.$file );
			
			closedir($dir);
			rmdir($folder);
		}
	}
	
	/*
	 * Return the most recent modification time of anything in the folder.
	 * @param $folder The folder, relative to the site root
	 * @param $extensionList An option array of file extensions to limit the search to
	 */
	 
	protected static $cache_folderModTime;
	static function folderModTime($folder, $extensionList = null, $recursiveCall = false) {
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
	 * Works on Linux and Windows
	 */
	static function isAbsolute($filename) {
		if($_ENV['OS'] == "Windows_NT" || $_SERVER['WINDIR']) return $filename[1] == ':' && $filename[2] == '/';
		else return $filename[0] == '/';
	}


}

?>