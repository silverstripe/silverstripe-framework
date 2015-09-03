<?php

/**
 * Allows access to config values set on classes using private statics.
 *
 * @package framework
 * @subpackage manifest
 */
class SS_ConfigStaticManifest {

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
							Deprecation::notice('4.0', "Config static $class::\$$name must be marked as private",
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
}
