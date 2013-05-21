<?php

/**
 * @package framework
 * @subpackage injector
 */

class SilverStripeInjectionCreator {
	/**
	 *
	 * @param string $object
	 *					A string representation of the class to create
	 * @param array $params
	 *					An array of parameters to be passed to the constructor
	 */
	public function create($class, $params = array()) {
		$class = Object::getCustomClass($class);
		$reflector = new ReflectionClass($class);
		
		return $reflector->newInstanceArgs($params);
	}
}