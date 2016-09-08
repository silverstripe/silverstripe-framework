<?php

namespace SilverStripe\Core\Manifest;

use ReflectionClass;

/**
 * Allows access to config values set on classes using private statics.
 */
class SS_ConfigStaticManifest {

	/**
	 * @param string $class
	 * @param string $name
	 * @return mixed
	 */
	public function get($class, $name) {
		if(class_exists($class)) {

			// The config system is case-sensitive so we need to check the exact value
			$reflection = new ReflectionClass($class);
			if(strcmp($reflection->name, $class) === 0) {

				if($reflection->hasProperty($name)) {
					$property = $reflection->getProperty($name);
					if($property->isStatic() && $property->isPrivate()) {
						$property->setAccessible(true);
						return $property->getValue();
					}
				}

			}
		}
		return null;
	}
}
