<?php
/**
 * A class that handles loading classes and interfaces from a class manifest
 * instance.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ClassLoader {

	/**
	 * A map of legacy class names to automatically alias.
	 *
	 * @var array
	 */
	public static $legacy_classes = array(
		'cookie' => 'SilverStripe\\Framework\\Http\\Cookie',
		'http'   => 'SilverStripe\\Framework\\Http\\Http'
	);

	/**
	 * @var SS_ClassLoader
	 */
	private static $instance;

	/**
	 * @var array Map of 'instance' (SS_ClassManifest) and other options.
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
		return $this->manifests[count($this->manifests) - 1]['instance'];
	}
	
	/**
	 * Returns true if this class loader has a manifest.
	 */
	public function hasManifest() {
		return (bool)$this->manifests;
	}

	/**
	 * Pushes a class manifest instance onto the top of the stack. This will
	 * also include any module configuration files at the same time.
	 *
	 * @param SS_ClassManifest $manifest
	 * @param Boolean Marks the manifest as exclusive. If set to FALSE, will
	 * look for classes in earlier manifests as well.
	 */
	public function pushManifest(SS_ClassManifest $manifest, $exclusive = true) {
		$this->manifests[] = array('exclusive' => $exclusive, 'instance' => $manifest);

		foreach ($manifest->getConfigs() as $config) {
			require_once $config;
		}
	}

	/**
	 * @return SS_ClassManifest
	 */
	public function popManifest() {
		$manifest = array_pop($this->manifests);
		return $manifest['instance'];
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
		$lower = strtolower($class);

		if(isset(self::$legacy_classes[$lower])) {
			$new = self::$legacy_classes[$lower];

			Deprecation::notice('3.2.0', "The '$class' class has been renamed to '$new'");
			class_alias($new, $class);
		} elseif($path = $this->getItemPath($class)) {
			require_once $path;
		}
	}
	
	/**
	 * Returns the path for a class or interface in the currently active manifest,
	 * or any previous ones if later manifests aren't set to "exclusive".
	 * 
	 * @return String
	 */
	public function getItemPath($class) {
		foreach(array_reverse($this->manifests) as $manifest) {
			$manifestInst = $manifest['instance'];
			if ($path = $manifestInst->getItemPath($class)) return $path;
			if($manifest['exclusive']) break;
		}
		return false;
	}

	/**
	 * Returns true if a class or interface name exists in the manifest.
	 *
	 * @param  string $class
	 * @return bool
	 */
	public function classExists($class) {
		return class_exists($class, false) || $this->getItemPath($class);
	}

}
