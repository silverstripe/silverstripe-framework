<?php

use SilverStripe\Framework\Injector\Factory;

/**
 * A class for creating new objects by the injector.
 *
 * @package framework
 * @subpackage injector
 */
class InjectionCreator implements Factory {

	public function create($class, array $params = array()) {
		$reflector = new ReflectionClass($class);

		if (count($params)) {
			return $reflector->newInstanceArgs($params);
		}

		return $reflector->newInstance();
	}

}
