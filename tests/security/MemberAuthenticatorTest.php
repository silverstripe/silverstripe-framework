<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class MemberAuthenticatorTest extends SapphireTest {
	
	protected $usesDatabase = true;
	
	function testLegacyPasswordHashMigrationUponLogin() {
		$member = new Member();
		$member->Email = 'test@test.com';
		$member->PasswordEncryption = "sha1";
		$member->Password = "mypassword";
		$member->write();
		
		$data = array(
			'Email' => $member->Email,
			'Password' => 'mypassword'
		);
		MemberAuthenticator::authenticate($data);
		
		$member = DataObject::get_by_id('Member', $member->ID);
		$this->assertEquals($member->PasswordEncryption, "sha1_v2.4");
		$this->assertTrue($member->checkPassword('mypassword'));
	}
	
	function testNoLegacyPasswordHashMigrationOnIncompatibleAlgorithm() {
		PasswordEncryptor::register('crc32', 'PasswordEncryptor_PHPHash("crc32")');
		
		$member = new Member();
		$member->Email = 'test@test.com';
		$member->PasswordEncryption = "crc32";
		$member->Password = "mypassword";
		$member->write();
		
		$data = array(
			'Email' => $member->Email,
			'Password' => 'mypassword'
		);
		MemberAuthenticator::authenticate($data);
		
		$member = DataObject::get_by_id('Member', $member->ID);
		$this->assertEquals($member->PasswordEncryption, "crc32");
		$this->assertTrue($member->checkPassword('mypassword'));
	}
}