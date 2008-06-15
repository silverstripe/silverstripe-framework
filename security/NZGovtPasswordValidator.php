<?php
/**
 * This {@link PasswordValidator} implements the NZ E-Government Guidelines for passwords
 * @package sapphire
 * @subpackage security
 */
class NZGovtPasswordValidator extends PasswordValidator {
	function __construct() {
		parent::__construct();
		$this->minLength(7);
		$this->checkHistoricalPasswords(6);
		$this->characterStrength(3, array('lowercase','uppercase','digits','punctuation'));
	}
	
}