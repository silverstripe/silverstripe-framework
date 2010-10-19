<?php
/**
 * Implementation of .tar, .tar.gz, and .tar.bz2 archive handler.
 * @package sapphire
 * @subpackage filesystem
 */
class TarballArchive extends Archive {
	private $filename = '';
	private $compressionModifiers = '';
	

	function __construct($filename) {
		$this->filename = $filename;
		
		if(substr($filename, strlen($filename) - strlen('.gz')) == '.gz' ||
			substr($filename, strlen($filename) - strlen('.tgz')) == '.tgz') {
			$this->compressionModifiers = 'z';	
		} else if(substr($filename, strlen($filename) - strlen('.bz2')) == '.bz2') {
			$compressionModifiers = 'j';
		}
	}
	
	function listing() {
		// Call tar on the command line to get the info we need
		$base = BASE_PATH;
		$command = "tar -tv{$this->compressionModifiers}f $base/$this->filename";
		$consoleList = `$command`;
		
		$listing = array();
		// Seperate into an array of lines
		$listItems = explode("\n", $consoleList);
		
		foreach($listItems as $listItem) {
			// The path is the last thing on the line
			$fullpath = substr($listItem, strrpos($listItem, ' ') + 1);
			$path = explode('/', $fullpath);
			$item = array();
			
			// The first part of the line is the permissions - the first character will be d if it is a directory
			$item['type'] = (substr($listItem, 0, 1) == 'd') ? 'directory' : 'file';
			if($item['type'] == 'directory') {
				$item['listing'] = array();
				// If it's a directory, the path will have a slash on the end, so get rid of it.
				array_pop($path);
			}
			
			// The name of the file/directory is the last item on the path
			$name = array_pop($path);
			if($name == '') {
				continue;
			}
			
			$item['path'] = implode('/', $path);
			
			// Put the item in the right place
			$dest = &$listing;
			foreach($path as $folder) {
				// If the directory doesn't exist, create it
				if(!isset($dest[$folder])) {
					$dest[$folder] = array();
					$dest[$folder]['listing'] = array();
					$dest[$folder]['type'] = 'directory';
				}
				$dest = &$dest[$folder]['listing'];
			}
			
			// If this is a directory and it's listing has already been created, copy the the listing
			if($item['type'] == 'directory' && isset($dest[$name]['listing'])) {
				$item['listing'] = $dest[$name]['listing'];
			}
			$dest[$name] = $item;
		}
		
		
		return $listing;
	}
	
	function extractTo($destination, $entries = null) {
		if(!isset($entries)) {
			$command = "tar -xv{$this->compressionModifiers}f ../$this->filename --directory $destination";
			$output = `$command`;
		}
	}
	
}

?>