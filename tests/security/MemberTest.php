<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class MemberTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/security/MemberTest.yml';
	
	protected $orig = array();
	protected $local = null; 
	
	protected $illegalExtensions = array(
		'Member' => array(
			// TODO Coupling with modules, this should be resolved by automatically
			// removing all applied extensions before a unit test
			'ForumRole',
			'OpenIDAuthenticatedRole'
		)
	);

	function __construct() {
		parent::__construct();

		//Setting the locale has to happen in the constructor (using the setUp and tearDown methods doesn't work)
		//This is because the test relies on the yaml file being interpreted according to a particular date format
		//and this setup occurs before the setUp method is run 
		$this->local = i18n::default_locale();
		i18n::set_default_locale('en_US');
	}

	function __destruct() {
        i18n::set_default_locale($this->local);
    }

	function setUp() {
		parent::setUp();
		
		$this->orig['Member_unique_identifier_field'] = Member::get_unique_identifier_field();
		Member::set_unique_identifier_field('Email');
		Member::set_password_validator(null);
	}
	
	function tearDown() {
		Member::set_unique_identifier_field($this->orig['Member_unique_identifier_field']);

		parent::tearDown();
	}

	/**
	 * @expectedException ValidationException
	 */
	function testWriteDoesntMergeNewRecordWithExistingMember() {
		$m1 = new Member();
		$m1->Email = 'member@test.com';
		$m1->write();
		
		$m2 = new Member();
		$m2->Email = 'member@test.com';
		$m2->write();
	}
	
	/**
	 * @expectedException ValidationException
	 */
	function testWriteDoesntMergeExistingMemberOnIdentifierChange() {
		$m1 = new Member();
		$m1->Email = 'member@test.com';
		$m1->write();
		
		$m2 = new Member();
		$m2->Email = 'member_new@test.com';
		$m2->write();
		
		$m2->Email = 'member@test.com';
		$m2->write();
	}
	
	function testDefaultPasswordEncryptionOnMember() {
		$memberWithPassword = new Member();
		$memberWithPassword->Password = 'mypassword';
		$memberWithPassword->write();
		$this->assertEquals(
			$memberWithPassword->PasswordEncryption, 
			Security::get_password_encryption_algorithm(),
			'Password encryption is set for new member records on first write (with setting "Password")'
		);
		
		$memberNoPassword = new Member();
		$memberNoPassword->write();
		$this->assertNull(
			$memberNoPassword->PasswordEncryption,
			'Password encryption is not set for new member records on first write, when not setting a "Password")'
		);
	}
	
	function testDefaultPasswordEncryptionDoesntChangeExistingMembers() {
		$member = new Member();
		$member->Password = 'mypassword';
		$member->PasswordEncryption = 'sha1_v2.4';
		$member->write();
		
		$origAlgo = Security::get_password_encryption_algorithm();
		Security::set_password_encryption_algorithm('none');
	
		$member->Password = 'mynewpassword';
		$member->write();
		
		$this->assertEquals(
			$member->PasswordEncryption, 
			'sha1_v2.4'
		);
		$result = $member->checkPassword('mynewpassword');
		$this->assertTrue($result->valid());
		
		Security::set_password_encryption_algorithm($origAlgo);
	}

	public function testKeepsEncryptionOnEmptyPasswords() {
		$member = new Member();
		$member->Password = 'mypassword';
		$member->PasswordEncryption = 'sha1_v2.4';
		$member->write();
		
		$member->Password = '';
		$member->write();
		
		$this->assertEquals(
			$member->PasswordEncryption, 
			'sha1_v2.4'
		);
		$result = $member->checkPassword('');
		$this->assertTrue($result->valid());
	}
	
	function testSetPassword() {
		$member = $this->objFromFixture('Member', 'test');
		$member->Password = "test1";
		$member->write();
		$result = $member->checkPassword('test1');
		$this->assertTrue($result->valid());
	}
	
	/**
	 * Test that password changes are logged properly
	 */
	function testPasswordChangeLogging() {
		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$member->Password = "test1";
		$member->write();
	
		$member->Password = "test2";
		$member->write();
	
		$member->Password = "test3";
		$member->write();
	
		$passwords = DataObject::get("MemberPassword", "\"MemberID\" = $member->ID", "\"Created\" DESC, \"ID\" DESC")->getIterator();
		$this->assertNotNull($passwords);
		$record = $passwords->rewind();
		$this->assertTrue($record->checkPassword('test3'), "Password test3 not found in MemberRecord");
	
		$record = $passwords->next();
		$this->assertTrue($record->checkPassword('test2'), "Password test2 not found in MemberRecord");
	
		$record = $passwords->next();
		$this->assertTrue($record->checkPassword('test1'), "Password test1 not found in MemberRecord");
	
		$record = $passwords->next();
		$this->assertType('DataObject', $record);
		$this->assertTrue($record->checkPassword('1nitialPassword'), "Password 1nitialPassword not found in MemberRecord");
	}
	
	/**
	 * Test that changed passwords will send an email
	 */
	function testChangedPasswordEmaling() {
		$this->clearEmails();
	
		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$valid = $member->changePassword('32asDF##$$%%');
		$this->assertTrue($valid->valid());
		/*
		$this->assertEmailSent("sam@silverstripe.com", null, "/changed password/", '/sam@silverstripe\.com.*32asDF##\$\$%%/');
		*/
	}
	
	/**
	 * Test that passwords validate against NZ e-government guidelines
	 *  - don't allow the use of the last 6 passwords
	 *  - require at least 3 of lowercase, uppercase, digits and punctuation
	 *  - at least 7 characters long
	 */
	function testValidatePassword() {
		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		
		Member::set_password_validator(new NZGovtPasswordValidator());
	
		// BAD PASSWORDS
		
		$valid = $member->changePassword('shorty');
		$this->assertFalse($valid->valid());
		$this->assertContains("TOO_SHORT", $valid->codeList());
	
		$valid = $member->changePassword('longone');
		$this->assertNotContains("TOO_SHORT", $valid->codeList());
		$this->assertContains("LOW_CHARACTER_STRENGTH", $valid->codeList());
		$this->assertFalse($valid->valid());
	
		$valid = $member->changePassword('w1thNumb3rs');
		$this->assertNotContains("LOW_CHARACTER_STRENGTH", $valid->codeList());
		$this->assertTrue($valid->valid());
		
		// Clear out the MemberPassword table to ensure that the system functions properly in that situation
		DB::query("DELETE FROM \"MemberPassword\"");
	
		// GOOD PASSWORDS
		
		$valid = $member->changePassword('withSym###Ls');
		$this->assertNotContains("LOW_CHARACTER_STRENGTH", $valid->codeList());
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls2');
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls3');
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls4');
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls5');
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls6');
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls7');
		$this->assertTrue($valid->valid());
	
		// CAN'T USE PASSWORDS 2-7, but I can use pasword 1
	
		$valid = $member->changePassword('withSym###Ls2');
		$this->assertFalse($valid->valid());
		$this->assertContains("PREVIOUS_PASSWORD", $valid->codeList());
	
		$valid = $member->changePassword('withSym###Ls5');
		$this->assertFalse($valid->valid());
		$this->assertContains("PREVIOUS_PASSWORD", $valid->codeList());
	
		$valid = $member->changePassword('withSym###Ls7');
		$this->assertFalse($valid->valid());
		$this->assertContains("PREVIOUS_PASSWORD", $valid->codeList());
		
		$valid = $member->changePassword('withSym###Ls');
		$this->assertTrue($valid->valid());
		
		// HAVING DONE THAT, PASSWORD 2 is now available from the list
	
		$valid = $member->changePassword('withSym###Ls2');
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls3');
		$this->assertTrue($valid->valid());
	
		$valid = $member->changePassword('withSym###Ls4');
		$this->assertTrue($valid->valid());
	
		Member::set_password_validator(null);
	}
	
	/**
	 * Test that the PasswordExpiry date is set when passwords are changed
	 */
	function testPasswordExpirySetting() {
		Member::set_password_expiry(90);
		
		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$valid = $member->changePassword("Xx?1234234");
		$this->assertTrue($valid->valid());
		
		$expiryDate = date('Y-m-d', time() + 90*86400);		
		$this->assertEquals($expiryDate, $member->PasswordExpiry);
	
		Member::set_password_expiry(null);
		$valid = $member->changePassword("Xx?1234235");
		$this->assertTrue($valid->valid());
	
		$this->assertNull($member->PasswordExpiry);
	}
	
	function testIsPasswordExpired() {
		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$this->assertFalse($member->isPasswordExpired());
	
		$member = $this->objFromFixture('Member', 'noexpiry');
		$member->PasswordExpiry = null;
		$this->assertFalse($member->isPasswordExpired());
	
		$member = $this->objFromFixture('Member', 'expiredpassword');
		$this->assertTrue($member->isPasswordExpired());
		
		// Check the boundary conditions
		// If PasswordExpiry == today, then it's expired
		$member->PasswordExpiry = date('Y-m-d');
		$this->assertTrue($member->isPasswordExpired());
	
		// If PasswordExpiry == tomorrow, then it's not
		$member->PasswordExpiry = date('Y-m-d', time() + 86400);
		$this->assertFalse($member->isPasswordExpired());
		
	}
	
	function testMemberWithNoDateFormatFallsbackToGlobalLocaleDefaultFormat() {
		$member = $this->objFromFixture('Member', 'noformatmember');
		$this->assertEquals('MM/dd/yyyy', $member->DateFormat);
		$this->assertEquals('hh:mm a', $member->TimeFormat);
	}
	
	function testMemberWithNoDateFormatFallsbackToTheirLocaleDefaultFormat() {
		$member = $this->objFromFixture('Member', 'delocalemember');
		$this->assertEquals('dd.MM.yyyy', $member->DateFormat);
		$this->assertEquals('HH:mm:ss', $member->TimeFormat);
	}
	
	function testInGroups() {
		$staffmember = $this->objFromFixture('Member', 'staffmember');
		$managementmember = $this->objFromFixture('Member', 'managementmember');
		$accountingmember = $this->objFromFixture('Member', 'accountingmember');
		$ceomember = $this->objFromFixture('Member', 'ceomember');
		
		$staffgroup = $this->objFromFixture('Group', 'staffgroup');
		$managementgroup = $this->objFromFixture('Group', 'managementgroup');
		$accountinggroup = $this->objFromFixture('Group', 'accountinggroup');
		$ceogroup = $this->objFromFixture('Group', 'ceogroup');
		
		$this->assertTrue(
			$staffmember->inGroups(array($staffgroup, $managementgroup)),
			'inGroups() succeeds if a membership is detected on one of many passed groups'
		);
		$this->assertFalse(
			$staffmember->inGroups(array($ceogroup, $managementgroup)),
			'inGroups() fails if a membership is detected on none of the passed groups'
		);
		$this->assertFalse(
			$ceomember->inGroups(array($staffgroup, $managementgroup), true),
			'inGroups() fails if no direct membership is detected on any of the passed groups (in strict mode)'
		);
	}
	
	function testAddToGroupByCode() {
		$grouplessMember = $this->objFromFixture('Member', 'grouplessmember');
		$memberlessGroup = $this->objFromFixture('Group','memberlessgroup');
		
		$this->assertFalse($grouplessMember->Groups()->exists());
		$this->assertFalse($memberlessGroup->Members()->exists());
	
		$grouplessMember->addToGroupByCode('memberless');
	
		$this->assertEquals($memberlessGroup->Members()->Count(), 1);
		$this->assertEquals($grouplessMember->Groups()->Count(), 1);
		
		$grouplessMember->addToGroupByCode('somegroupthatwouldneverexist', 'New Group');
		$this->assertEquals($grouplessMember->Groups()->Count(), 2);
		
		$group = DataObject::get_one('Group', "\"Code\" = 'somegroupthatwouldneverexist'");
		$this->assertNotNull($group);
		$this->assertEquals($group->Code, 'somegroupthatwouldneverexist');
		$this->assertEquals($group->Title, 'New Group');
		
	}
	
	function testInGroup() {
		$staffmember = $this->objFromFixture('Member', 'staffmember');
		$managementmember = $this->objFromFixture('Member', 'managementmember');
		$accountingmember = $this->objFromFixture('Member', 'accountingmember');
		$ceomember = $this->objFromFixture('Member', 'ceomember');
		
		$staffgroup = $this->objFromFixture('Group', 'staffgroup');
		$managementgroup = $this->objFromFixture('Group', 'managementgroup');
		$accountinggroup = $this->objFromFixture('Group', 'accountinggroup');
		$ceogroup = $this->objFromFixture('Group', 'ceogroup');
		
		$this->assertTrue(
			$staffmember->inGroup($staffgroup),
			'Direct group membership is detected'
		);
		$this->assertTrue(
			$managementmember->inGroup($staffgroup),
			'Users of child group are members of a direct parent group (if not in strict mode)'
		);
		$this->assertTrue(
			$accountingmember->inGroup($staffgroup),
			'Users of child group are members of a direct parent group (if not in strict mode)'
		);
		$this->assertTrue(
			$ceomember->inGroup($staffgroup),
			'Users of indirect grandchild group are members of a parent group (if not in strict mode)'
		);
		$this->assertTrue(
			$ceomember->inGroup($ceogroup, true),
			'Direct group membership is dected (if in strict mode)'
		);
		$this->assertFalse(
			$ceomember->inGroup($staffgroup, true),
			'Users of child group are not members of a direct parent group (if in strict mode)'
		);
		$this->assertFalse(
			$staffmember->inGroup($managementgroup),
			'Users of parent group are not members of a direct child group'
		);
		$this->assertFalse(
			$staffmember->inGroup($ceogroup),
			'Users of parent group are not members of an indirect grandchild group'
		);
		$this->assertFalse(
			$accountingmember->inGroup($managementgroup),
			'Users of group are not members of any siblings'
		);
		$this->assertFalse(
			$staffmember->inGroup('does-not-exist'),
			'Non-existant group returns false'
		);
	}
	
	/**
	 * Tests that the user is able to view their own record, and in turn, they can
	 * edit and delete their own record too.
	 */
	public function testCanManipulateOwnRecord() {
		$extensions = $this->removeExtensions(Object::get_extensions('Member'));
		$member = $this->objFromFixture('Member', 'test');
		$member2 = $this->objFromFixture('Member', 'staffmember');
		
		$this->session()->inst_set('loggedInAs', null);
		
		/* Not logged in, you can't view, delete or edit the record */
		$this->assertFalse($member->canView());
		$this->assertFalse($member->canDelete());
		$this->assertFalse($member->canEdit());
		
		/* Logged in users can edit their own record */
		$this->session()->inst_set('loggedInAs', $member->ID);
		$this->assertTrue($member->canView());
		$this->assertTrue($member->canDelete());
		$this->assertTrue($member->canEdit());
		
		/* Other uses cannot view, delete or edit others records */
		$this->session()->inst_set('loggedInAs', $member2->ID);
		$this->assertFalse($member->canView());
		$this->assertFalse($member->canDelete());
		$this->assertFalse($member->canEdit());
	
		$this->addExtensions($extensions);
		$this->session()->inst_set('loggedInAs', null);
	}
	
	public function testAuthorisedMembersCanManipulateOthersRecords() {
		$extensions = $this->removeExtensions(Object::get_extensions('Member'));
		$member = $this->objFromFixture('Member', 'test');
		$member2 = $this->objFromFixture('Member', 'staffmember');
		
		/* Group members with SecurityAdmin permissions can manipulate other records */
		$this->session()->inst_set('loggedInAs', $member->ID);
		$this->assertTrue($member2->canView());
		$this->assertTrue($member2->canDelete());
		$this->assertTrue($member2->canEdit());
		
		$this->addExtensions($extensions);
		$this->session()->inst_set('loggedInAs', null);
	}
	
	public function testDecoratedCan() {
		$extensions = $this->removeExtensions(Object::get_extensions('Member'));
		$member = $this->objFromFixture('Member', 'test');
		
		/* Normal behaviour is that you can't view a member unless canView() on an extension returns true */
		$this->assertFalse($member->canView());
		$this->assertFalse($member->canDelete());
		$this->assertFalse($member->canEdit());
		
		/* Apply a decorator that allows viewing in any case (most likely the case for member profiles) */
		Object::add_extension('Member', 'MemberTest_ViewingAllowedExtension');
		$member2 = $this->objFromFixture('Member', 'staffmember');
		
		$this->assertTrue($member2->canView());
		$this->assertFalse($member2->canDelete());
		$this->assertFalse($member2->canEdit());
	
		/* Apply a decorator that denies viewing of the Member */
		Object::remove_extension('Member', 'MemberTest_ViewingAllowedExtension');
		Object::add_extension('Member', 'MemberTest_ViewingDeniedExtension');
		$member3 = $this->objFromFixture('Member', 'managementmember');
		
		$this->assertFalse($member3->canView());
		$this->assertFalse($member3->canDelete());
		$this->assertFalse($member3->canEdit());
	
		/* Apply a decorator that allows viewing and editing but denies deletion */
		Object::remove_extension('Member', 'MemberTest_ViewingDeniedExtension');
		Object::add_extension('Member', 'MemberTest_EditingAllowedDeletingDeniedExtension');
		$member4 = $this->objFromFixture('Member', 'accountingmember');
		
		$this->assertTrue($member4->canView());
		$this->assertFalse($member4->canDelete());
		$this->assertTrue($member4->canEdit());
		
		Object::remove_extension('Member', 'MemberTest_EditingAllowedDeletingDeniedExtension');
		$this->addExtensions($extensions);
	}
	
	/**
	 * Tests for {@link Member::getName()} and {@link Member::setName()}
	 */
	function testName() {
		$member = $this->objFromFixture('Member', 'test');
		$member->setName('Test Some User');
		$this->assertEquals('Test Some User', $member->getName());
		$member->setName('Test');
		$this->assertEquals('Test', $member->getName());
		$member->FirstName = 'Test';
		$member->Surname = '';
		$this->assertEquals('Test', $member->getName());
	}
	
	function testMembersWithSecurityAdminAccessCantEditAdminsUnlessTheyreAdminsThemselves() {
		$adminMember = $this->objFromFixture('Member', 'admin');
		$otherAdminMember = $this->objFromFixture('Member', 'other-admin');
		$securityAdminMember = $this->objFromFixture('Member', 'test');
		$ceoMember = $this->objFromFixture('Member', 'ceomember');
		
		// Careful: Don't read as english language.
		// More precisely this should read canBeEditedBy()
		
		$this->assertTrue($adminMember->canEdit($adminMember), 'Admins can edit themselves');
		$this->assertTrue($otherAdminMember->canEdit($adminMember), 'Admins can edit other admins');
		$this->assertTrue($securityAdminMember->canEdit($adminMember), 'Admins can edit other members');
		
		$this->assertTrue($securityAdminMember->canEdit($securityAdminMember), 'Security-Admins can edit themselves');
		$this->assertFalse($adminMember->canEdit($securityAdminMember), 'Security-Admins can not edit other admins');
		$this->assertTrue($ceoMember->canEdit($securityAdminMember), 'Security-Admins can edit other members');
	}
	
	function testOnChangeGroups() {
		$staffGroup = $this->objFromFixture('Group', 'staffgroup');
		$adminGroup = $this->objFromFixture('Group', 'admingroup');
		$staffMember = $this->objFromFixture('Member', 'staffmember');
		$adminMember = $this->objFromFixture('Member', 'admin');
		$newAdminGroup = new Group(array('Title' => 'newadmin'));
		$newAdminGroup->write();
		Permission::grant($newAdminGroup->ID, 'ADMIN');
		$newOtherGroup = new Group(array('Title' => 'othergroup'));
		$newOtherGroup->write();
		
		$this->assertTrue(
			$staffMember->onChangeGroups(array($staffGroup->ID)),
			'Adding existing non-admin group relation is allowed for non-admin members'
		);
		$this->assertTrue(
			$staffMember->onChangeGroups(array($newOtherGroup->ID)),
			'Adding new non-admin group relation is allowed for non-admin members'
		);
		$this->assertFalse(
			$staffMember->onChangeGroups(array($newAdminGroup->ID)),
			'Adding new admin group relation is not allowed for non-admin members'
		);
		$this->assertTrue(
			$adminMember->onChangeGroups(array($newAdminGroup->ID)),
			'Adding new admin group relation is allowed for admin members'
		);
	}

	public function testGenerateAutologinTokenAndStoreHash() {
		$enc = new PasswordEncryptor_PHPHash('sha1');

		$m = new Member();
		$m->PasswordEncryption = 'sha1';
		$m->Salt = $enc->salt('123');

		$token = $m->generateAutologinTokenAndStoreHash();

		$this->assertEquals($m->encryptWithUserSettings($token), $m->AutoLoginHash, 'Stores the token as ahash.');
	}

	public function testValidateAutoLoginToken() {
		$enc = new PasswordEncryptor_PHPHash('sha1');

		$m1 = new Member();
		$m1->PasswordEncryption = 'sha1';
		$m1->Salt = $enc->salt('123');
		$m1Token = $m1->generateAutologinTokenAndStoreHash();

		$m2 = new Member();
		$m2->PasswordEncryption = 'sha1';
		$m2->Salt = $enc->salt('456');
		$m2Token = $m2->generateAutologinTokenAndStoreHash();
		
		$this->assertTrue($m1->validateAutoLoginToken($m1Token), 'Passes token validity test against matching member.');
		$this->assertFalse($m2->validateAutoLoginToken($m1Token), 'Fails token validity test against other member.');
	}

	/**
	 * Add the given array of member extensions as class names.
	 * This is useful for re-adding extensions after being removed
	 * in a test case to produce an unbiased test.
	 * 
	 * @param array $extensions
	 * @return array The added extensions
	 */
	protected function addExtensions($extensions) {
		if($extensions) foreach($extensions as $extension) {
			Object::add_extension('Member', $extension);
		}
		return $extensions;
	}

	/**
	 * Remove given extensions from Member. This is useful for
	 * removing extensions that could produce a biased
	 * test result, as some extensions applied by project
	 * code or modules can do this.
	 * 
	 * @param array $extensions
	 * @return array The removed extensions
	 */
	protected function removeExtensions($extensions) {
		if($extensions) foreach($extensions as $extension) {
			Object::remove_extension('Member', $extension);
		}
		return $extensions;
	}

}
class MemberTest_ViewingAllowedExtension extends DataObjectDecorator implements TestOnly {

	public function canView() {
		return true;
	}

}
class MemberTest_ViewingDeniedExtension extends DataObjectDecorator implements TestOnly {

	public function canView() {
		return false;
	}

}
class MemberTest_EditingAllowedDeletingDeniedExtension extends DataObjectDecorator implements TestOnly {

	public function canView() {
		return true;
	}

	public function canEdit() {
		return true;
	}

	public function canDelete() {
		return false;
	}

}
