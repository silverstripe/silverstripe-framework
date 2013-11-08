<?php
/**
 * A class for creating new objects by the injector.
 *
 * @package framework
 * @subpackage injector
 */
class InjectionCreator implements InjectorFactory {

	public function create($class, $params = array()) {
		$reflector = new ReflectionClass($class);

		if (count($params)) {
			return $reflector->newInstanceArgs($params); 
		}

		return $reflector->newInstance();
	}

}