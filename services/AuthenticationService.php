<?php

/**
 * A service that manages authentication related functionality
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 * @package sapphire.service
 */
class AuthenticationService {
	
	protected $providers = array();
	
	public function setProviders($providers) {
		$this->providers = $providers;
	}
	
	public function authenticate($identifier, $password) {
		foreach ($this->providers as $provider) {
			$user = $provider->authenticate($identifier, $password);
			if ($user) {
				return $user;
			}
		}
		
	}
}
