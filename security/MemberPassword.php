<?php
/**
 * Keep track of users' previous passwords, so that we can check that new passwords aren't changed back to old ones.
 * @package framework
 * @subpackage security
 */
class MemberPassword extends DataObject {
	static $db = array(
		'Password' => 'Varchar(160)',
		'Salt' => 'Varchar(50)',
		'PasswordEncryption' => 'Varchar(50)',
	);
	
	static $has_one = array(
		'Member' => 'Member'
	);
	
	static $has_many = array();
	
	static $many_many = array();
	
	static $belongs_many_many = array();
	
	/**
	 * Log a password change from the given member.
	 * Call MemberPassword::log($this) from within Member whenever the password is changed.
	 */
	static function log($member) {
		$record = new MemberPassword();
		$record->MemberID = $member->ID;
		$record->Password = $member->Password;
		$record->PasswordEncryption = $member->PasswordEncryption;
		$record->Salt = $member->Salt;
		$record->write();
	}
	
	/**
	 * Check if the given password is the same as the one stored in this record.
	 * See {@link Member->checkPassword()}.
	 * 
	 * @param String $password Cleartext password
	 * @return Boolean
	 */	
	function checkPassword($password) {
		$e = PasswordEncryptor::create_for_algorithm($this->PasswordEncryption);
		return $e->check($this->Password, $password, $this->Salt, $this->Member());
	}
	
	
}
