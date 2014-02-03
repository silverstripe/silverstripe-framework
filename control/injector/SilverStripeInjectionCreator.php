<?php

use SilverStripe\Framework\Injector\Factory;

/**
 * @package framework
 * @subpackage injector
 */
class SilverStripeInjectionCreator implements Factory {

	public function create($class, array $params = array()) {
		$class = Object::getCustomClass($class);
		$reflector = new ReflectionClass($class);

		return $params ? $reflector->newInstanceArgs($params) : $reflector->newInstance();
	}

}
