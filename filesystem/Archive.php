<?php
/**
 * Class for handling archives.
 * To implement a specific archive system, create a subclass of this abstract class, and amend the implementation of Archive::open().
 * @package sapphire
 * @subpackage filesystem
 */
abstract class Archive extends Object {
	/**
	 * Return an Archive object for the given file.
	 */
	static function open($filename) {
		if(substr($filename, strlen($filename) - strlen('.tar.gz')) == '.tar.gz' ||
			substr($filename, strlen($filename) - strlen('.tar.bz2')) == '.tar.bz2' ||
			substr($filename, strlen($filename) - strlen('.tar')) == '.tar') {
				return new TarballArchive($filename);
			}
	}
	
	function extractTo($destination, $entries = null) {
	}
	
	function listing($path) {
	}
}

?>