<?php
class PasswordEncryptorTest extends SapphireTest {
	function testCreateForCode() {
		PasswordEncryptor::register('test', 'PasswordEncryptorTest_TestEncryptor');
		$e = PasswordEncryptor::create_for_algorithm('test');
		$this->assertType(
			'PasswordEncryptorTest_TestEncryptor',
			$e
		);
	}
	
	/**
	 * @expectedException PasswordEncryptor_NotFoundException
	 */
	function testCreateForCodeNotFound() {
		PasswordEncryptor::create_for_algorithm('unknown');
	}
	
	function testRegister() {
		PasswordEncryptor::register('test', 'PasswordEncryptorTest_TestEncryptor');
		$this->assertContains('test', array_keys(PasswordEncryptor::get_encryptors()));
		$this->assertContains('PasswordEncryptorTest_TestEncryptor', array_values(PasswordEncryptor::get_encryptors()));
	}
	
	function testUnregister() {
		PasswordEncryptor::register('test', 'PasswordEncryptorTest_TestEncryptor');
		PasswordEncryptor::unregister('test');
		$this->assertNotContains('test', array_keys(PasswordEncryptor::get_encryptors()));
	}
	
	function testEncrytorPHPHashWithArguments() {
		PasswordEncryptor::register('test_md5', 'PasswordEncryptor_PHPHash("md5")');
		$e = PasswordEncryptor::create_for_algorithm('test_md5');
		$this->assertEquals('md5', $e->getAlgorithm());
	}
	
	function testEncrytorPHPHash() {
		PasswordEncryptor::register('test_sha1', 'PasswordEncryptor_PHPHash("sha1")');
		$e = PasswordEncryptor::create_for_algorithm('test_sha1');
		$password = 'mypassword';
		$salt = 'mysalt';
		$this->assertEquals(
			hash('sha1', $password . $salt), 
			$e->encrypt($password, $salt)
		);
	}
	
	function testEncrytorPHPHashCompare() {
		PasswordEncryptor::register('test_sha1', 'PasswordEncryptor_PHPHash("sha1")');
		$e = PasswordEncryptor::create_for_algorithm('test_sha1');
		$this->assertTrue($e->compare(sha1('mypassword'), sha1('mypassword')));
		$this->assertFalse($e->compare(sha1('mypassword'), sha1('mywrongpassword')));
	}
	
	/**
	 * See http://open.silverstripe.org/ticket/3004
	 * 
	 * Handy command for reproducing via CLI on different architectures:
	 * 	php -r "echo(base_convert(sha1('mypassword'), 16, 36));"
	 */
	function testEncrytorLegacyPHPHashCompare() {
		PasswordEncryptor::register('test_sha1legacy', 'PasswordEncryptor_LegacyPHPHash("sha1")');
		$e = PasswordEncryptor::create_for_algorithm('test_sha1legacy');
		// precomputed hashes for 'mypassword' from different architectures
		$amdHash = 'h1fj0a6m4o6k0sosks88oo08ko4gc4s';
		$intelHash = 'h1fj0a6m4o0g04ocg00o4kwoc4wowws';
		$wrongHash = 'h1fjxxxxxxxxxxxxxxxxxxxxxxxxxxx';
		$this->assertTrue($e->compare($amdHash, $intelHash));
		$this->assertFalse($e->compare($amdHash, $wrongHash));
	}
}

class PasswordEncryptorTest_TestEncryptor extends PasswordEncryptor implements TestOnly {
	function encrypt($password, $salt = null, $member = null) {
		return 'password';
	}
	
	function salt($password, $member = null) {
		return 'salt';
	}
}