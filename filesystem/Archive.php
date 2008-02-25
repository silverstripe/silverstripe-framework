<?php

abstract class Archive extends Object {
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