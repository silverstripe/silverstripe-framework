<?php

/**
 * Performs a permission check against a node
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class PermissionCheckAspect {
	
	protected $requiredPermission;
	
	public function __construct($permission) {
		$this->requiredPermission = $permission;
	}
	
	/**
	 * Performs a specific permission check 
	 *
	 * @param object $proxied
	 * @param String $method
	 * @param array $args 
	 */
	public function preCall($proxied, $method, $args) {
		return Permission::check($this->requiredPermission);
	}
}
