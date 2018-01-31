<?php

use SilverStripe\Framework\Injector\Factory;

/**
 * @package framework
 * @subpackage injector
 */
class SilverStripeInjectionCreator implements Factory {

	public function create($class, array $params = array()) {
		$class = SS_Object::getCustomClass($class);
		$reflector = new ReflectionClass($class);

		return $params ? $reflector->newInstanceArgs($params) : $reflector->newInstance();
	}

}
