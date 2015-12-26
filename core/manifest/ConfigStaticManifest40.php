<?php

/**
 * Allows access to config values set on classes using private statics.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigStaticManifest_40 extends SS_ConfigStaticManifest {

	/**
	 * Constructs and initialises a new config static manifest, either loading the data
	 * from the cache or re-scanning for classes.
	 *
	 * @param string $base The manifest base path.
	 * @param bool   $includeTests Include the contents of "tests" directories.
	 * @param bool   $forceRegen Force the manifest to be regenerated.
	 * @param bool   $cache If the manifest is regenerated, cache it.
	 */
	public function __construct($base, $includeTests = false, $forceRegen = false, $cache = true) {
		// Stubbed as these parameters are not needed for the newer SS_ConficStaticManifest version.
	}

	/**
	 * Completely regenerates the manifest file.
	 */
	public function regenerate($cache = true) {
		Deprecation::notice('3.3', 'This is no longer available as SS_ConfigStaticManifest now uses Reflection.');
	}

	/**
	 * @param string $class
	 * @param string $name
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function get($class, $name, $default = null) {
		if(class_exists($class)) {

			// The config system is case-sensitive so we need to check the exact value
			$reflection = new ReflectionClass($class);
			if(strcmp($reflection->name, $class) === 0) {

				if($reflection->hasProperty($name)) {
					$property = $reflection->getProperty($name);
					if($property->isStatic()) {
						if(!$property->isPrivate()) {
							Deprecation::notice('3.3', "Config static $class::\$$name must be marked as private",
								Deprecation::SCOPE_GLOBAL);
							return null;
						}
						$property->setAccessible(true);
						return $property->getValue();
					}
				}

			}
		}
		return null;
	}

	public function getStatics() {
		Deprecation::notice('3.3', 'This is no longer available as SS_ConfigStaticManifest now uses Reflection.');
		return array();
	}
}