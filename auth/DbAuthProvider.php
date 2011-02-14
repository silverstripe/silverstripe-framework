<?php

/**
 * An authentication provider that can authenticate a user against a database
 * 
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 *
 * @package sapphire.auth
 */
class DbAuthProvider {
	/**
	 * 
	 * The field on a user class that is used for identification. 
	 *
	 * @var String
	 */
	private $identifierField;
	
	public function __construct($identifierField = 'Email') {
		$this->identifierField = $identifierField;
	}
	
	/**
	 * Authenticate against a database
	 *
	 * @param string $identifier
	 * @param string $password 
	 */
	public function authenticate($identifier, $password) {
		$details = array('Email' => $identifier, 'Password' => $password);
		return MemberAuthenticator::authenticate($details);
	}
}
