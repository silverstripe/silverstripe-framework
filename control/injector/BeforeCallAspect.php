<?php

/**
 * A BeforeCallAspect is run before a method is executed.
 *
 * This is a declared interface, but isn't actually required
 * as PHP doesn't really care about types... 
 * 
 * @package framework
 * @subpackage injector
 */
interface BeforeCallAspect {
	
	/**
	 * Call this aspect before a method is executed
	 * 
	 * @param object $proxied
	 *				The object having the method called upon it. 
	 * @param string $method
	 *				The name of the method being called
	 * @param string $args
	 *				The arguments that were passed to the method call
	 */
	public function beforeCall($proxied, $method, $args);
}
