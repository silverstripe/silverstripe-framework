<?php

/**
 * A service that manages authentication in the system
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 * 
 * @package sapphire
 * @subpackage auth
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