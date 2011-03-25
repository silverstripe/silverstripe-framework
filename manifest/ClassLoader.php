<?php
/**
 * A class that handles loading classes and interfaces from a class manifest
 * instance.
 *
 * @package    sapphire
 * @subpackage manifest
 */
class SS_ClassLoader {

	/**
	 * @var SS_ClassLoader
	 */
	private static $instance;

	/**
	 * @var SS_ClassManifest[]
	 */
	protected $manifests = array();

	/**
	 * @return SS_ClassLoader
	 */
	public static function instance() {
		return self::$instance ? self::$instance : self::$instance = new self();
	}

	/**
	 * Returns the currently active class manifest instance that is used for
	 * loading classes.
	 *
	 * @return SS_ClassManifest
	 */
	public function getManifest() {
		return $this->manifests[count($this->manifests) - 1];
	}

	/**
	 * Pushes a class manifest instance onto the top of the stack. This will
	 * also include any module configuration files at the same time.
	 *
	 * @param SS_ClassManifest $manifest
	 */
	public function pushManifest(SS_ClassManifest $manifest) {
		$this->manifests[] = $manifest;

		foreach ($manifest->getConfigs() as $config) {
			require_once $config;
		}
	}

	/**
	 * @return SS_ClassManifest
	 */
	public function popManifest() {
		return array_pop($this->manifests);
	}

	public function registerAutoloader() {
		spl_autoload_register(array($this, 'loadClass'));
	}

	/**
	 * Loads a class or interface if it is present in the currently active
	 * manifest.
	 *
	 * @param string $class
	 */
	public function loadClass($class) {
		if ($path = $this->getManifest()->getItemPath($class)) {
			require_once $path;
		}
	}

	/**
	 * Returns true if a class or interface name exists in the manifest.
	 *
	 * @param  string $class
	 * @return bool
	 */
	public function classExists($class) {
		return class_exists($class, false) || $this->getManifest()->getItemPath($class);
	}

}