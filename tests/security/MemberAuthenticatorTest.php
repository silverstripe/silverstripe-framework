<?php
/**
 * @package framework
 * @subpackage tests
 */
class MemberAuthenticatorTest extends SapphireTest {

	protected $usesDatabase = true;

	protected $defaultUsername = null;
	protected $defaultPassword = null;

	public function setUp() {
		parent::setUp();

		$this->defaultUsername = Security::default_admin_username();
		$this->defaultPassword = Security::default_admin_password();
		Security::clear_default_admin();
		Security::setDefaultAdmin('admin', 'password');
	}

	public function tearDown() {
		Security::setDefaultAdmin($this->defaultUsername, $this->defaultPassword);
		parent::tearDown();
	}

	public function testLegacyPasswordHashMigrationUponLogin() {
		$member = new Member();

		$field=Member::config()->unique_identifier_field;

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

	public function testNoLegacyPasswordHashMigrationOnIncompatibleAlgorithm() {
		Config::inst()->update('PasswordEncryptor', 'encryptors',
			array('crc32'=>array('PasswordEncryptor_PHPHash'=>'crc32')));
		$field=Member::config()->unique_identifier_field;

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

	public function testCustomIdentifierField(){

		$origField = Member::config()->unique_identifier_field;
		Member::config()->unique_identifier_field = 'Username';

		$label=singleton('Member')->fieldLabel(Member::config()->unique_identifier_field);

		$this->assertEquals($label, 'Username');

		Member::config()->unique_identifier_field = $origField;
	}

	public function testGenerateLoginForm() {
		$controller = new Security();

		// Create basic login form
		$frontendForm = MemberAuthenticator::get_login_form($controller);
		$this->assertTrue($frontendForm instanceof MemberLoginForm);

		// Supports cms login form
		$this->assertTrue(MemberAuthenticator::supports_cms());
		$cmsForm = MemberAuthenticator::get_cms_login_form($controller);
		$this->assertTrue($cmsForm instanceof CMSMemberLoginForm);
	}

	/**
	 * Test that a member can be authenticated via their temp id
	 */
	public function testAuthenticateByTempID() {
		$member = new Member();
		$member->Email = 'test1@test.com';
		$member->PasswordEncryption = "sha1";
		$member->Password = "mypassword";
		$member->write();

		// Make form
		$controller = new Security();
		$form = new Form($controller, 'Form', new FieldList(), new FieldList());

		// If the user has never logged in, then the tempid should be empty
		$tempID = $member->TempIDHash;
		$this->assertEmpty($tempID);

		// If the user logs in then they have a temp id
		$member->logIn(true);
		$tempID = $member->TempIDHash;
		$this->assertNotEmpty($tempID);

		// Test correct login
		$result = MemberAuthenticator::authenticate(array(
			'tempid' => $tempID,
			'Password' => 'mypassword'
		), $form);
		$this->assertNotEmpty($result);
		$this->assertEquals($result->ID, $member->ID);
		$this->assertEmpty($form->Message());

		// Test incorrect login
		$form->clearMessage();
		$result = MemberAuthenticator::authenticate(array(
			'tempid' => $tempID,
			'Password' => 'notmypassword'
		), $form);
		$this->assertEmpty($result);
		$this->assertEquals('The provided details don&#039;t seem to be correct. Please try again.', $form->Message());
		$this->assertEquals('bad', $form->MessageType());
	}

	/**
	 * Test that the default admin can be authenticated
	 */
	public function testDefaultAdmin() {
		// Make form
		$controller = new Security();
		$form = new Form($controller, 'Form', new FieldList(), new FieldList());

		// Test correct login
		$result = MemberAuthenticator::authenticate(array(
			'Email' => 'admin',
			'Password' => 'password'
		), $form);
		$this->assertNotEmpty($result);
		$this->assertEquals($result->Email, Security::default_admin_username());
		$this->assertEmpty($form->Message());

		// Test incorrect login
		$form->clearMessage();
		$result = MemberAuthenticator::authenticate(array(
			'Email' => 'admin',
			'Password' => 'notmypassword'
		), $form);
		$this->assertEmpty($result);
		$this->assertEquals('The provided details don&#039;t seem to be correct. Please try again.', $form->Message());
		$this->assertEquals('bad', $form->MessageType());
	}

	public function testDefaultAdminLockOut()
	{
		Config::inst()->update('Member', 'lock_out_after_incorrect_logins', 1);
		Config::inst()->update('Member', 'lock_out_delay_mins', 10);
		SS_Datetime::set_mock_now('2016-04-18 00:00:00');
		$controller = new Security();
		$form = new Form($controller, 'Form', new FieldList(), new FieldList());

		// Test correct login
		MemberAuthenticator::authenticate(array(
			'Email' => 'admin',
			'Password' => 'wrongpassword'
		), $form);

		$this->assertTrue(Member::default_admin()->isLockedOut());
		$this->assertEquals('2016-04-18 00:10:00', Member::default_admin()->LockedOutUntil);
	}

	public function testNonExistantMemberGetsLoginAttemptRecorded()
	{
		Config::inst()->update('Member', 'lock_out_after_incorrect_logins', 1);
		$email = 'notreal@example.com';
		$this->assertFalse(Member::get()->filter(array('Email' => $email))->exists());
		$this->assertCount(0, LoginAttempt::get());
		$response = MemberAuthenticator::authenticate(array(
			'Email' => $email,
			'Password' => 'password',
		));
		$this->assertNull($response);
		$this->assertCount(1, LoginAttempt::get());
		$attempt = LoginAttempt::get()->first();
		$this->assertEmpty($attempt->Email); // Doesn't store potentially sensitive data
		$this->assertEquals(sha1($email), $attempt->EmailHashed);
		$this->assertEquals('Failure', $attempt->Status);

	}

	public function testNonExistantMemberGetsLockedOut()
	{
		Config::inst()->update('Member', 'lock_out_after_incorrect_logins', 1);
		Config::inst()->update('Member', 'lock_out_delay_mins', 10);
		$email = 'notreal@example.com';

		$this->assertFalse(Member::get()->filter(array('Email' => $email))->exists());

		$response = MemberAuthenticator::authenticate(array(
			'Email' => $email,
			'Password' => 'password'
		));

		$this->assertNull($response);
		$member = new Member();
		$member->Email = $email;

		$this->assertTrue($member->isLockedOut());
		$this->assertFalse($member->canLogIn()->valid());
	}

	public function testDefaultAuthenticatorWontReturnIfDisabled()
	{
		Authenticator::unregister('MemberAuthenticator');
		$this->assertNotEquals('MemberAuthenticator', Authenticator::get_default_authenticator());
	}
}
