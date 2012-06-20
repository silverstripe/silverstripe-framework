<?php

/**
 * An AfterCallAspect is run after a method is executed
 * 
 * This is a declared interface, but isn't actually required
 * as PHP doesn't really care about types... 
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @package framework
 * @subpackage injector
 * @license BSD http://silverstripe.org/BSD-license
 */
interface AfterCallAspect {
	
	/**
	 * Call this aspect after a method is executed
	 * 
	 * @param object $proxied
	 *				The object having the method called upon it. 
	 * @param string $method
	 *				The name of the method being called
	 * @param string $args
	 *				The arguments that were passed to the method call
	 */
	public function afterCall($proxied, $method, $args);
}
