<?php

/**
 * A class for creating new objects by the injector.
 *
 * @package framework
 * @subpackage injector
 */
class InjectionCreator {

	/**
	 * @param string $object
	 *					A string representation of the class to create
	 * @param array $params
	 *					An array of parameters to be passed to the constructor
	 */
	public function create($class, $params = array()) {
		$reflector = new ReflectionClass($class);

		if (count($params)) {
			return $reflector->newInstanceArgs($params); 
		}
		
		return $reflector->newInstance();
	}
}