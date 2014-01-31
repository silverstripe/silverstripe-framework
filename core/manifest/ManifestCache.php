<?php

/**
 * A basic caching interface that manifests use to store data.
 *
 * @package framework
 * @subpackage manifest
 */
interface ManifestCache {
	public function __construct($name);
	public function load($key);
	public function save($data, $key);
	public function clear();
}

/**
 * Stores manifest data in files in TEMP_DIR dir on filesystem
 *
 * @package framework
 * @subpackage manifest
 */
class ManifestCache_File implements ManifestCache {
	function __construct($name) {
		$this->folder = TEMP_FOLDER.'/'.$name;
		if (!is_dir($this->folder)) mkdir($this->folder);
	}

	function load($key) {
		$file = $this->folder.'/cache_'.$key;
		return file_exists($file) ? unserialize(file_get_contents($file)) : null;
	}

	function save($data, $key) {
		$file = $this->folder.'/cache_'.$key;
		file_put_contents($file, serialize($data));
	}

	function clear() {
		array_map('unlink', glob($this->folder.'/cache_*'));
	}
}

/**
 * Same as ManifestCache_File, but stores the data as valid PHP which gets included to load
 * This is a bit faster if you have an opcode cache installed, but slower otherwise
 *
 * @package framework
 * @subpackage manifest
 */
class ManifestCache_File_PHP extends ManifestCache_File {
	function load($key) {
		global $loaded_manifest;
		$loaded_manifest = null;

		$file = $this->folder.'/cache_'.$key;
		if (file_exists($file)) include $file;

		return $loaded_manifest;
	}

	function save($data, $key) {
		$file = $this->folder.'/cache_'.$key;
		file_put_contents($file, '<?php $loaded_manifest = '.var_export($data, true).';');
	}
}

/**
 * Stores manifest data in APC.
 * Note: benchmarks seem to indicate this is not particularly faster than _File
 *
 * @package framework
 * @subpackage manifest
 */
class ManifestCache_APC implements ManifestCache {
	protected $pre;

	function __construct($name) {
		$this->pre = $name;
	}

	function load($key) {
		return apc_fetch($this->pre.$key);
	}

	function save($data, $key) {
		apc_store($this->pre.$key, $data);
	}

	function clear() {
	}
}