<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class MemberAuthenticatorTest extends SapphireTest {
	function testLegacyPasswordHashMigrationUponLogin() {
		$member = new Member();
		
		$field=Member::get_unique_identifier_field();
		
		$member->$field = 'test1@test.com';
		$member->PasswordEncryption = "sha1";
		$member->Password = "mypassword";
		$member->write();
		
		$data = array(
			'Email' => $member->$field,
			'Password' => 'mypassword'
		);
		MemberAuthenticator::authenticate($data);
		
		$member = DataObject::get_by_id('Member', $member->ID);
		$this->assertEquals($member->PasswordEncryption, "sha1_v2.4");
		$result = $member->checkPassword('mypassword');
		$this->assertTrue($result->valid());
	}
	
	function testNoLegacyPasswordHashMigrationOnIncompatibleAlgorithm() {
		PasswordEncryptor::register('crc32', 'PasswordEncryptor_PHPHash("crc32")');
		
		$field=Member::get_unique_identifier_field();
		
		$member = new Member();
		$member->$field = 'test2@test.com';
		$member->PasswordEncryption = "crc32";
		$member->Password = "mypassword";
		$member->write();
		
		$data = array(
			'Email' => $member->$field,
			'Password' => 'mypassword'
		);
		MemberAuthenticator::authenticate($data);
		
		$member = DataObject::get_by_id('Member', $member->ID);
		$this->assertEquals($member->PasswordEncryption, "crc32");
		$result = $member->checkPassword('mypassword');
		$this->assertTrue($result->valid());
	}
	
	function testCustomIdentifierField(){
		
		$origField = Member::get_unique_identifier_field();
		Member::set_unique_identifier_field('Username');

		$label=singleton('Member')->fieldLabel(Member::get_unique_identifier_field());
		
		$this->assertEquals($label, 'Username');
		
		Member::set_unique_identifier_field($origField);
	}
}