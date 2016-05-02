<?php
class PasswordEncryptorTest extends SapphireTest {

	/**
	 *
	 * @var Config
	 */
	private $config = null;

	public function setUp() {
		parent::setUp();
		$this->config = clone(Config::inst());
	}

	public function tearDown() {
		parent::tearDown();
		Config::set_instance($this->config);
		PasswordEncryptor_Blowfish::set_cost(10);
	}

	public function testCreateForCode() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test'=>array('PasswordEncryptorTest_TestEncryptor'=>null)));
		$e = PasswordEncryptor::create_for_algorithm('test');
		$this->assertInstanceOf('PasswordEncryptorTest_TestEncryptor', $e );
	}

	/**
	 * @expectedException PasswordEncryptor_NotFoundException
	 */
	public function testCreateForCodeNotFound() {
		PasswordEncryptor::create_for_algorithm('unknown');
	}

	public function testRegister() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test'=>array('PasswordEncryptorTest_TestEncryptor'=>null)));
		$encryptors = PasswordEncryptor::get_encryptors();
		$this->assertContains('test', array_keys($encryptors));
		$encryptor = $encryptors['test'];
		$this->assertContains('PasswordEncryptorTest_TestEncryptor', key($encryptor));
	}

	public function testUnregister() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test'=>array('PasswordEncryptorTest_TestEncryptor'=>null)));
		Config::inst()->remove('PasswordEncryptor', 'encryptors', 'test');
		$this->assertNotContains('test', array_keys(PasswordEncryptor::get_encryptors()));
	}

	public function testEncryptorPHPHashWithArguments() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test_md5'=>array('PasswordEncryptor_PHPHash'=>'md5')));
		$e = PasswordEncryptor::create_for_algorithm('test_md5');
		$this->assertEquals('md5', $e->getAlgorithm());
	}

	public function testEncryptorPHPHash() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test_sha1'=>array('PasswordEncryptor_PHPHash'=>'sha1')));
		$e = PasswordEncryptor::create_for_algorithm('test_sha1');
		$password = 'mypassword';
		$salt = 'mysalt';
		$this->assertEquals(
			hash('sha1', $password . $salt),
			$e->encrypt($password, $salt)
		);
	}

	public function testEncryptorBlowfish() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test_blowfish'=>array('PasswordEncryptor_Blowfish'=>'')));
		$e = PasswordEncryptor::create_for_algorithm('test_blowfish');

		$password = 'mypassword';

		$salt = $e->salt($password);
		$modSalt = substr($salt, 0, 3) . str_shuffle(substr($salt, 3, strlen($salt)));

		$this->assertTrue($e->checkAEncryptionLevel() == 'y' || $e->checkAEncryptionLevel() == 'x'
			|| $e->checkAEncryptionLevel() == 'a');
		$this->assertTrue($e->check($e->encrypt($password, $salt), "mypassword", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "anotherpw", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "mypassword", $modSalt));

		PasswordEncryptor_Blowfish::set_cost(1);
		$salt = $e->salt($password);
		$modSalt = substr($salt, 0, 3) . str_shuffle(substr($salt, 3, strlen($salt)));

		$this->assertNotEquals(1, PasswordEncryptor_Blowfish::get_cost());
		$this->assertEquals(4, PasswordEncryptor_Blowfish::get_cost());

		$this->assertTrue($e->check($e->encrypt($password, $salt), "mypassword", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "anotherpw", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "mypassword", $modSalt));

		PasswordEncryptor_Blowfish::set_cost(11);
		$salt = $e->salt($password);
		$modSalt = substr($salt, 0, 3) . str_shuffle(substr($salt, 3, strlen($salt)));

		$this->assertEquals(11, PasswordEncryptor_Blowfish::get_cost());

		$this->assertTrue($e->check($e->encrypt($password, $salt), "mypassword", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "anotherpw", $salt));
		$this->assertFalse($e->check($e->encrypt($password, $salt), "mypassword", $modSalt));


		PasswordEncryptor_Blowfish::set_cost(35);

		$this->assertNotEquals(35, PasswordEncryptor_Blowfish::get_cost());
		$this->assertEquals(31, PasswordEncryptor_Blowfish::get_cost());

		//Don't actually test this one. It takes too long. 31 takes too long to process
	}

	public function testEncryptorPHPHashCheck() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test_sha1'=>array('PasswordEncryptor_PHPHash'=>'sha1')));
		$e = PasswordEncryptor::create_for_algorithm('test_sha1');
		$this->assertTrue($e->check(sha1('mypassword'), 'mypassword'));
		$this->assertFalse($e->check(sha1('mypassword'), 'mywrongpassword'));
	}

	/**
	 * See http://open.silverstripe.org/ticket/3004
	 *
	 * Handy command for reproducing via CLI on different architectures:
	 * 	php -r "echo(base_convert(sha1('mypassword'), 16, 36));"
	 */
	public function testEncryptorLegacyPHPHashCheck() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('test_sha1legacy'=>array('PasswordEncryptor_LegacyPHPHash'=>'sha1')));
		$e = PasswordEncryptor::create_for_algorithm('test_sha1legacy');
		// precomputed hashes for 'mypassword' from different architectures
		$amdHash = 'h1fj0a6m4o6k0sosks88oo08ko4gc4s';
		$intelHash = 'h1fj0a6m4o0g04ocg00o4kwoc4wowws';
		$wrongHash = 'h1fjxxxxxxxxxxxxxxxxxxxxxxxxxxx';
		$this->assertTrue($e->check($amdHash, "mypassword"));
		$this->assertTrue($e->check($intelHash, "mypassword"));
		$this->assertFalse($e->check($wrongHash, "mypassword"));
	}
}

class PasswordEncryptorTest_TestEncryptor extends PasswordEncryptor implements TestOnly {
	public function encrypt($password, $salt = null, $member = null) {
		return 'password';
	}

	public function salt($password, $member = null) {
		return 'salt';
	}
}
