<?php
/**
 * @package framework
 * @subpackage tests
 */
class MemberTest extends FunctionalTest {
	protected static $fixture_file = 'MemberTest.yml';

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

	public function __construct() {
		parent::__construct();

		//Setting the locale has to happen in the constructor (using the setUp and tearDown methods doesn't work)
		//This is because the test relies on the yaml file being interpreted according to a particular date format
		//and this setup occurs before the setUp method is run
		$this->local = i18n::default_locale();
		i18n::set_default_locale('en_US');
	}

	public function __destruct() {
		i18n::set_default_locale($this->local);
	}

	public function setUp() {
		parent::setUp();

		$this->orig['Member_unique_identifier_field'] = Member::config()->unique_identifier_field;
		Member::config()->unique_identifier_field = 'Email';
		Member::set_password_validator(null);
	}

	public function tearDown() {
		Member::config()->unique_identifier_field = $this->orig['Member_unique_identifier_field'];
		parent::tearDown();
	}

	public function testPasswordEncryptionUpdatedOnChangedPassword()
	{
		Config::inst()->update('Security', 'password_encryption_algorithm', 'none');
		$member = Member::create();
		$member->SetPassword = 'password';
		$member->write();
		$this->assertEquals('password', $member->Password);
		$this->assertEquals('none', $member->PasswordEncryption);
		Config::inst()->update('Security', 'password_encryption_algorithm', 'blowfish');
		$member->SetPassword = 'newpassword';
		$member->write();
		$this->assertNotEquals('password', $member->Password);
		$this->assertNotEquals('newpassword', $member->Password);
		$this->assertEquals('blowfish', $member->PasswordEncryption);
	}

	/**
	 * @expectedException ValidationException
	 */
	public function testWriteDoesntMergeNewRecordWithExistingMember() {
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
	public function testWriteDoesntMergeExistingMemberOnIdentifierChange() {
		$m1 = new Member();
		$m1->Email = 'member@test.com';
		$m1->write();

		$m2 = new Member();
		$m2->Email = 'member_new@test.com';
		$m2->write();

		$m2->Email = 'member@test.com';
		$m2->write();
	}

	public function testDefaultPasswordEncryptionOnMember() {
		$memberWithPassword = new Member();
		$memberWithPassword->Password = 'mypassword';
		$memberWithPassword->write();
		$this->assertEquals(
			$memberWithPassword->PasswordEncryption,
			Security::config()->password_encryption_algorithm,
			'Password encryption is set for new member records on first write (with setting "Password")'
		);

		$memberNoPassword = new Member();
		$memberNoPassword->write();
		$this->assertNull(
			$memberNoPassword->PasswordEncryption,
			'Password encryption is not set for new member records on first write, when not setting a "Password")'
		);
	}

	public function testKeepsEncryptionOnEmptyPasswords() {
		$member = new Member();
		$member->Password = 'mypassword';
		$member->PasswordEncryption = 'sha1_v2.4';
		$member->write();

		$member->Password = '';
		$member->write();

		$this->assertEquals(
			Security::config()->get('password_encryption_algorithm'),
            $member->PasswordEncryption
		);
		$result = $member->checkPassword('');
		$this->assertTrue($result->valid());
	}

	public function testSetPassword() {
		$member = $this->objFromFixture('Member', 'test');
		$member->Password = "test1";
		$member->write();
		$result = $member->checkPassword('test1');
		$this->assertTrue($result->valid());
	}

	/**
	 * Test that password changes are logged properly
	 */
	public function testPasswordChangeLogging() {
		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$member->Password = "test1";
		$member->write();

		$member->Password = "test2";
		$member->write();

		$member->Password = "test3";
		$member->write();

		$passwords = DataObject::get("MemberPassword", "\"MemberID\" = $member->ID", "\"Created\" DESC, \"ID\" DESC")
			->getIterator();
		$this->assertNotNull($passwords);
		$passwords->rewind();
		$this->assertTrue($passwords->current()->checkPassword('test3'), "Password test3 not found in MemberRecord");

		$passwords->next();
		$this->assertTrue($passwords->current()->checkPassword('test2'), "Password test2 not found in MemberRecord");

		$passwords->next();
		$this->assertTrue($passwords->current()->checkPassword('test1'), "Password test1 not found in MemberRecord");

		$passwords->next();
		$this->assertInstanceOf('DataObject', $passwords->current());
		$this->assertTrue($passwords->current()->checkPassword('1nitialPassword'),
			"Password 1nitialPassword not found in MemberRecord");

		//check we don't retain orphaned records when a member is deleted
		$member->delete();

		$passwords = MemberPassword::get()->filter('MemberID', $member->OldID);

		$this->assertCount(0, $passwords);
	}

	/**
	 * Test that changed passwords will send an email
	 */
	public function testChangedPasswordEmaling() {
		Config::inst()->update('Member', 'notify_password_change', true);

		$this->clearEmails();

		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$valid = $member->changePassword('32asDF##$$%%');
		$this->assertTrue($valid->valid());

		$this->assertEmailSent('testuser@example.com', null, 'Your password has been changed',
			'/testuser@example\.com/');

	}

	/**
	 * Test that triggering "forgotPassword" sends an Email with a reset link
	 */
	public function testForgotPasswordEmaling() {
		$this->clearEmails();
		$this->autoFollowRedirection = false;

		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);

		// Initiate a password-reset
		$response = $this->post('Security/LostPasswordForm', array('Email' => $member->Email));

		$this->assertEquals($response->getStatusCode(), 302);

		// We should get redirected to Security/passwordsent
		$this->assertContains('Security/passwordsent/testuser@example.com',
			urldecode($response->getHeader('Location')));

		// Check existance of reset link
		$this->assertEmailSent("testuser@example.com", null, 'Your password reset link',
			'/Security\/changepassword\?m='.$member->ID.'&t=[^"]+/');
	}

	/**
	 * Test that passwords validate against NZ e-government guidelines
	 *  - don't allow the use of the last 6 passwords
	 *  - require at least 3 of lowercase, uppercase, digits and punctuation
	 *  - at least 7 characters long
	 */
	public function testValidatePassword() {
		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);

		Member::set_password_validator(new MemberTest_PasswordValidator());

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
	public function testPasswordExpirySetting() {
		Member::config()->password_expiry_days = 90;

		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$valid = $member->changePassword("Xx?1234234");
		$this->assertTrue($valid->valid());

		$expiryDate = date('Y-m-d', time() + 90*86400);
		$this->assertEquals($expiryDate, $member->PasswordExpiry);

		Member::config()->password_expiry_days = null;
		$valid = $member->changePassword("Xx?1234235");
		$this->assertTrue($valid->valid());

		$this->assertNull($member->PasswordExpiry);
	}

	public function testIsPasswordExpired() {
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

	public function testMemberWithNoDateFormatFallsbackToGlobalLocaleDefaultFormat() {
		Config::inst()->update('i18n', 'date_format', 'yyyy-MM-dd');
		Config::inst()->update('i18n', 'time_format', 'H:mm');
		$member = $this->objFromFixture('Member', 'noformatmember');
		$this->assertEquals('yyyy-MM-dd', $member->DateFormat);
		$this->assertEquals('H:mm', $member->TimeFormat);
	}

	public function testInGroups() {
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

	/**
	 * Assertions to check that Member_GroupSet is functionally equivalent to ManyManyList
	 */
	public function testRemoveGroups()
	{
		$staffmember = $this->objFromFixture('Member', 'staffmember');

		$staffgroup = $this->objFromFixture('Group', 'staffgroup');
		$managementgroup = $this->objFromFixture('Group', 'managementgroup');

		$this->assertTrue(
			$staffmember->inGroups(array($staffgroup, $managementgroup)),
			'inGroups() succeeds if a membership is detected on one of many passed groups'
		);

		$staffmember->Groups()->remove($managementgroup);
		$this->assertFalse(
			$staffmember->inGroup($managementgroup),
			'member was not removed from group using ->Groups()->remove()'
		);

		$staffmember->Groups()->removeAll();
		$this->assertEquals(
			0,
			$staffmember->Groups()->count(),
			'member was not removed from all groups using ->Groups()->removeAll()'
		);
	}

	public function testAddToGroupByCode() {
		$grouplessMember = $this->objFromFixture('Member', 'grouplessmember');
		$memberlessGroup = $this->objFromFixture('Group','memberlessgroup');

		$this->assertFalse($grouplessMember->Groups()->exists());
		$this->assertFalse($memberlessGroup->Members()->exists());

		$grouplessMember->addToGroupByCode('memberless');

		$this->assertEquals($memberlessGroup->Members()->Count(), 1);
		$this->assertEquals($grouplessMember->Groups()->Count(), 1);

		$grouplessMember->addToGroupByCode('somegroupthatwouldneverexist', 'New Group');
		$this->assertEquals($grouplessMember->Groups()->Count(), 2);

		$group = DataObject::get_one('Group', array(
			'"Group"."Code"' => 'somegroupthatwouldneverexist'
		));
		$this->assertNotNull($group);
		$this->assertEquals($group->Code, 'somegroupthatwouldneverexist');
		$this->assertEquals($group->Title, 'New Group');

	}

	public function testRemoveFromGroupByCode() {
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

		$grouplessMember->removeFromGroupByCode('memberless');
		$this->assertEquals($memberlessGroup->Members()->Count(), 0);
		$this->assertEquals($grouplessMember->Groups()->Count(), 1);

		$grouplessMember->removeFromGroupByCode('somegroupthatwouldneverexist');
		$this->assertEquals($grouplessMember->Groups()->Count(), 0);
	}

	public function testInGroup() {
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
		$this->assertFalse($member->canDelete());
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

	public function testExtendedCan() {
		$extensions = $this->removeExtensions(Object::get_extensions('Member'));
		$member = $this->objFromFixture('Member', 'test');

		/* Normal behaviour is that you can't view a member unless canView() on an extension returns true */
		$this->assertFalse($member->canView());
		$this->assertFalse($member->canDelete());
		$this->assertFalse($member->canEdit());

		/* Apply a extension that allows viewing in any case (most likely the case for member profiles) */
		Member::add_extension('MemberTest_ViewingAllowedExtension');
		$member2 = $this->objFromFixture('Member', 'staffmember');

		$this->assertTrue($member2->canView());
		$this->assertFalse($member2->canDelete());
		$this->assertFalse($member2->canEdit());

		/* Apply a extension that denies viewing of the Member */
		Member::remove_extension('MemberTest_ViewingAllowedExtension');
		Member::add_extension('MemberTest_ViewingDeniedExtension');
		$member3 = $this->objFromFixture('Member', 'managementmember');

		$this->assertFalse($member3->canView());
		$this->assertFalse($member3->canDelete());
		$this->assertFalse($member3->canEdit());

		/* Apply a extension that allows viewing and editing but denies deletion */
		Member::remove_extension('MemberTest_ViewingDeniedExtension');
		Member::add_extension('MemberTest_EditingAllowedDeletingDeniedExtension');
		$member4 = $this->objFromFixture('Member', 'accountingmember');

		$this->assertTrue($member4->canView());
		$this->assertFalse($member4->canDelete());
		$this->assertTrue($member4->canEdit());

		Member::remove_extension('MemberTest_EditingAllowedDeletingDeniedExtension');
		$this->addExtensions($extensions);
	}

	/**
	 * Tests for {@link Member::getName()} and {@link Member::setName()}
	 */
	public function testName() {
		$member = $this->objFromFixture('Member', 'test');
		$member->setName('Test Some User');
		$this->assertEquals('Test Some User', $member->getName());
		$member->setName('Test');
		$this->assertEquals('Test', $member->getName());
		$member->FirstName = 'Test';
		$member->Surname = '';
		$this->assertEquals('Test', $member->getName());
	}

	public function testMembersWithSecurityAdminAccessCantEditAdminsUnlessTheyreAdminsThemselves() {
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

	public function testOnChangeGroups() {
		$staffGroup = $this->objFromFixture('Group', 'staffgroup');
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

		$this->session()->inst_set('loggedInAs', $adminMember->ID);
		$this->assertTrue(
			$staffMember->onChangeGroups(array($newAdminGroup->ID)),
			'Adding new admin group relation is allowed for normal users, when granter is logged in as admin'
		);
		$this->session()->inst_set('loggedInAs', null);

		$this->assertTrue(
			$adminMember->onChangeGroups(array($newAdminGroup->ID)),
			'Adding new admin group relation is allowed for admin members'
		);
	}

	/**
	 * Test Member_GroupSet::add
	 */
	public function testOnChangeGroupsByAdd() {
		$staffMember = $this->objFromFixture('Member', 'staffmember');
		$adminMember = $this->objFromFixture('Member', 'admin');

		// Setup new admin group
		$newAdminGroup = new Group(array('Title' => 'newadmin'));
		$newAdminGroup->write();
		Permission::grant($newAdminGroup->ID, 'ADMIN');

		// Setup non-admin group
		$newOtherGroup = new Group(array('Title' => 'othergroup'));
		$newOtherGroup->write();

		// Test staff can be added to other group
		$this->assertFalse($staffMember->inGroup($newOtherGroup));
		$staffMember->Groups()->add($newOtherGroup);
		$this->assertTrue(
			$staffMember->inGroup($newOtherGroup),
			'Adding new non-admin group relation is allowed for non-admin members'
		);

		// Test staff member can't be added to admin groups
		$this->assertFalse($staffMember->inGroup($newAdminGroup));
		$staffMember->Groups()->add($newAdminGroup);
		$this->assertFalse(
			$staffMember->inGroup($newAdminGroup),
			'Adding new admin group relation is not allowed for non-admin members'
		);

		// Test staff member can be added to admin group by admins
		$this->logInAs($adminMember);
		$staffMember->Groups()->add($newAdminGroup);
		$this->assertTrue(
			$staffMember->inGroup($newAdminGroup),
			'Adding new admin group relation is allowed for normal users, when granter is logged in as admin'
		);

		// Test staff member can be added if they are already admin
		$this->session()->inst_set('loggedInAs', null);
		$this->assertFalse($adminMember->inGroup($newAdminGroup));
		$adminMember->Groups()->add($newAdminGroup);
		$this->assertTrue(
			$adminMember->inGroup($newAdminGroup),
			'Adding new admin group relation is allowed for admin members'
		);
	}

	/**
	 * Test Member_GroupSet::add
	 */
	public function testOnChangeGroupsBySetIDList() {
		$staffMember = $this->objFromFixture('Member', 'staffmember');

		// Setup new admin group
		$newAdminGroup = new Group(array('Title' => 'newadmin'));
		$newAdminGroup->write();
		Permission::grant($newAdminGroup->ID, 'ADMIN');

		// Test staff member can't be added to admin groups
		$this->assertFalse($staffMember->inGroup($newAdminGroup));
		$staffMember->Groups()->setByIDList(array($newAdminGroup->ID));
		$this->assertFalse(
			$staffMember->inGroup($newAdminGroup),
			'Adding new admin group relation is not allowed for non-admin members'
		);
	}

	/**
	 * Test that extensions using updateCMSFields() are applied correctly
	 */
	public function testUpdateCMSFields() {
		Member::add_extension('MemberTest_FieldsExtension');

		$member = singleton('Member');
		$fields = $member->getCMSFields();

		$this->assertNotNull($fields->dataFieldByName('Email'), 'Scaffolded fields are retained');
		$this->assertNull($fields->dataFieldByName('Salt'), 'Field modifications run correctly');
		$this->assertNotNull($fields->dataFieldByName('TestMemberField'), 'Extension is applied correctly');

		Member::remove_extension('MemberTest_FieldsExtension');
	}

	/**
	 * Test that all members are returned
	 */
	public function testMap_in_groupsReturnsAll() {
		$members = Member::map_in_groups();
		$this->assertEquals(13, count($members), 'There are 12 members in the mock plus a fake admin');
	}

	/**
	 * Test that only admin members are returned
	 */
	public function testMap_in_groupsReturnsAdmins() {
		$adminID = $this->objFromFixture('Group', 'admingroup')->ID;
		$members = Member::map_in_groups($adminID);

		$admin = $this->objFromFixture('Member', 'admin');
		$otherAdmin = $this->objFromFixture('Member', 'other-admin');

		$this->assertTrue(in_array($admin->getTitle(), $members),
			$admin->getTitle().' should be in the returned list.');
		$this->assertTrue(in_array($otherAdmin->getTitle(), $members),
			$otherAdmin->getTitle().' should be in the returned list.');
		$this->assertEquals(2, count($members), 'There should be 2 members from the admin group');
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
			Member::add_extension($extension);
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
			Member::remove_extension($extension);
		}
		return $extensions;
	}

	public function testGenerateAutologinTokenAndStoreHash() {
		$enc = new PasswordEncryptor_Blowfish();

		$m = new Member();
		$m->PasswordEncryption = 'blowfish';
		$m->Salt = $enc->salt('123');

		$token = $m->generateAutologinTokenAndStoreHash();

		$this->assertEquals($m->encryptWithUserSettings($token), $m->AutoLoginHash, 'Stores the token as ahash.');
	}

	public function testValidateAutoLoginToken() {
		$enc = new PasswordEncryptor_Blowfish();

		$m1 = new Member();
		$m1->PasswordEncryption = 'blowfish';
		$m1->Salt = $enc->salt('123');
		$m1Token = $m1->generateAutologinTokenAndStoreHash();

		$m2 = new Member();
		$m2->PasswordEncryption = 'blowfish';
		$m2->Salt = $enc->salt('456');
		$m2Token = $m2->generateAutologinTokenAndStoreHash();

		$this->assertTrue($m1->validateAutoLoginToken($m1Token), 'Passes token validity test against matching member.');
		$this->assertFalse($m2->validateAutoLoginToken($m1Token), 'Fails token validity test against other member.');
	}

	public function testCanDelete() {
		$admin1 = $this->objFromFixture('Member', 'admin');
		$admin2 = $this->objFromFixture('Member', 'other-admin');
		$member1 = $this->objFromFixture('Member', 'grouplessmember');
		$member2 = $this->objFromFixture('Member', 'noformatmember');

		$this->assertTrue(
			$admin1->canDelete($admin2),
			'Admins can delete other admins'
		);
		$this->assertTrue(
			$member1->canDelete($admin2),
			'Admins can delete non-admins'
		);
		$this->assertFalse(
			$admin1->canDelete($admin1),
			'Admins can not delete themselves'
		);
		$this->assertFalse(
			$member1->canDelete($member2),
			'Non-admins can not delete other non-admins'
		);
		$this->assertFalse(
			$member1->canDelete($member1),
			'Non-admins can not delete themselves'
		);
	}

	public function testFailedLoginCount() {
		$maxFailedLoginsAllowed = 3;
		//set up the config variables to enable login lockouts
		Config::inst()->update('Member', 'lock_out_after_incorrect_logins', $maxFailedLoginsAllowed);

		$member = $this->objFromFixture('Member', 'test');
		$failedLoginCount = $member->FailedLoginCount;

		for ($i = 1; $i < $maxFailedLoginsAllowed; ++$i) {
			$member->registerFailedLogin();

			$this->assertEquals(
				++$failedLoginCount,
				$member->FailedLoginCount,
				'Failed to increment $member->FailedLoginCount'
			);

			$this->assertFalse(
				$member->isLockedOut(),
				"Member has been locked out too early"
			);
		}
	}

	public function testMemberValidator()
	{
		// clear custom requirements for this test
		Config::inst()->update('Member_Validator', 'customRequired', null);
		$memberA = $this->objFromFixture('Member', 'admin');
		$memberB = $this->objFromFixture('Member', 'test');

		// create a blank form
		$form = new MemberTest_ValidatorForm();

		$validator = new Member_Validator();
		$validator->setForm($form);

		// Simulate creation of a new member via form, but use an existing member identifier
		$fail = $validator->php(array(
			'FirstName' => 'Test',
			'Email' => $memberA->Email
		));

		$this->assertFalse(
			$fail,
			'Member_Validator must fail when trying to create new Member with existing Email.'
		);

		// populate the form with values from another member
		$form->loadDataFrom($memberB);

		// Assign the validator to an existing member
		// (this is basically the same as passing the member ID with the form data)
		$validator->setForMember($memberB);

		// Simulate update of a member via form and use an existing member Email
		$fail = $validator->php(array(
			'FirstName' => 'Test',
			'Email' => $memberA->Email
		));

		// Simulate update to a new Email address
		$pass1 = $validator->php(array(
			'FirstName' => 'Test',
			'Email' => 'membervalidatortest@testing.com'
		));

		// Pass in the same Email address that the member already has. Ensure that case is valid
		$pass2 = $validator->php(array(
			'FirstName' => 'Test',
			'Surname' => 'User',
			'Email' => $memberB->Email
		));

		$this->assertFalse(
			$fail,
			'Member_Validator must fail when trying to update existing member with existing Email.'
		);

		$this->assertTrue(
			$pass1,
			'Member_Validator must pass when Email is updated to a value that\'s not in use.'
		);

		$this->assertTrue(
			$pass2,
			'Member_Validator must pass when Member updates his own Email to the already existing value.'
		);
	}

	public function testMemberValidatorWithExtensions()
	{
		// clear custom requirements for this test
		Config::inst()->update('Member_Validator', 'customRequired', null);

		// create a blank form
		$form = new MemberTest_ValidatorForm();

		// Test extensions
		Member_Validator::add_extension('MemberTest_MemberValidator_SurnameMustMatchFirstNameExtension');
		$validator = new Member_Validator();
		$validator->setForm($form);

		// This test should fail, since the extension enforces FirstName == Surname
		$fail = $validator->php(array(
			'FirstName' => 'Test',
			'Surname' => 'User',
			'Email' => 'test-member-validator-extension@testing.com'
		));

		$pass = $validator->php(array(
			'FirstName' => 'Test',
			'Surname' => 'Test',
			'Email' => 'test-member-validator-extension@testing.com'
		));

		$this->assertFalse(
			$fail,
			'Member_Validator must fail because of added extension.'
		);

		$this->assertTrue(
			$pass,
			'Member_Validator must succeed, since it meets all requirements.'
		);

		// Add another extension that always fails. This ensures that all extensions are considered in the validation
		Member_Validator::add_extension('MemberTest_MemberValidator_AlwaysFailsExtension');
		$validator = new Member_Validator();
		$validator->setForm($form);

		// Even though the data is valid, This test should still fail, since one extension always returns false
		$fail = $validator->php(array(
			'FirstName' => 'Test',
			'Surname' => 'Test',
			'Email' => 'test-member-validator-extension@testing.com'
		));

		$this->assertFalse(
			$fail,
			'Member_Validator must fail because of added extensions.'
		);

		// Remove added extensions
		Member_Validator::remove_extension('MemberTest_MemberValidator_AlwaysFailsExtension');
		Member_Validator::remove_extension('MemberTest_MemberValidator_SurnameMustMatchFirstNameExtension');
	}

	public function testCustomMemberValidator()
	{
		// clear custom requirements for this test
		Config::inst()->update('Member_Validator', 'customRequired', null);

		$member = $this->objFromFixture('Member', 'admin');

		$form = new MemberTest_ValidatorForm();
		$form->loadDataFrom($member);

		$validator = new Member_Validator();
		$validator->setForm($form);

		$pass = $validator->php(array(
			'FirstName' => 'Borris',
			'Email' => 'borris@silverstripe.com'
		));

		$fail = $validator->php(array(
			'Email' => 'borris@silverstripe.com',
			'Surname' => ''
		));

		$this->assertTrue($pass, 'Validator requires a FirstName and Email');
		$this->assertFalse($fail, 'Missing FirstName');

		$ext = new MemberTest_ValidatorExtension();
		$ext->updateValidator($validator);

		$pass = $validator->php(array(
			'FirstName' => 'Borris',
			'Email' => 'borris@silverstripe.com'
		));

		$fail = $validator->php(array(
			'Email' => 'borris@silverstripe.com'
		));

		$this->assertFalse($pass, 'Missing surname');
		$this->assertFalse($fail, 'Missing surname value');

		$fail = $validator->php(array(
			'Email' => 'borris@silverstripe.com',
			'Surname' => 'Silverman'
		));

		$this->assertTrue($fail, 'Passes with email and surname now (no firstname)');
	}

	public function testCurrentUser() {
		$this->assertNull(Member::currentUser());

		$adminMember = $this->objFromFixture('Member', 'admin');
		$this->logInAs($adminMember);

		$userFromSession = Member::currentUser();
		$this->assertEquals($adminMember->ID, $userFromSession->ID);
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class MemberTest_ValidatorForm extends Form implements TestOnly {

	public function __construct() {
		parent::__construct(Controller::curr(), __CLASS__, new FieldList(
			new TextField('Email'),
			new TextField('Surname'),
			new TextField('ID'),
			new TextField('FirstName')
		), new FieldList(
			new FormAction('someAction')
		));
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class MemberTest_ValidatorExtension extends DataExtension implements TestOnly {

	public function updateValidator(&$validator) {
		$validator->addRequiredField('Surname');
		$validator->removeRequiredField('FirstName');
	}
}

/**
 * Extension that adds additional validation criteria
 * @package framework
 * @subpackage tests
 */
class MemberTest_MemberValidator_SurnameMustMatchFirstNameExtension extends DataExtension implements TestOnly
{
	public function updatePHP($data, $form) {
		return $data['FirstName'] == $data['Surname'];
	}
}

/**
 * Extension that adds additional validation criteria
 * @package framework
 * @subpackage tests
 */
class MemberTest_MemberValidator_AlwaysFailsExtension extends DataExtension implements TestOnly
{
	public function updatePHP($data, $form) {
		return false;
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class MemberTest_ViewingAllowedExtension extends DataExtension implements TestOnly {

	public function canView($member = null) {
		return true;
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class MemberTest_ViewingDeniedExtension extends DataExtension implements TestOnly {

	public function canView($member = null) {
		return false;
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class MemberTest_FieldsExtension extends DataExtension implements TestOnly {

	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab('Root.Main', new TextField('TestMemberField', 'Test'));
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class MemberTest_EditingAllowedDeletingDeniedExtension extends DataExtension implements TestOnly {

	public function canView($member = null) {
		return true;
	}

	public function canEdit($member = null) {
		return true;
	}

	public function canDelete($member = null) {
		return false;
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class MemberTest_PasswordValidator extends PasswordValidator {
	public function __construct() {
		parent::__construct();
		$this->minLength(7);
		$this->checkHistoricalPasswords(6);
		$this->characterStrength(3, array('lowercase','uppercase','digits','punctuation'));
	}

}
