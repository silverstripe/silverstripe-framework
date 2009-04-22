<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class MemberTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/security/MemberTest.yml';
	
	/**
	 * Test that password changes are logged properly
	 */
	function testPasswordChangeLogging() {
		Member::set_password_validator(null);

		$member = $this->objFromFixture('Member', 'test');
		$this->assertNotNull($member);
		$member->Password = "test1";
		$member->write();

		$member->Password = "test2";
		$member->write();

		$member->Password = "test3";
		$member->write();

		$passwords = DataObject::get("MemberPassword", "MemberID = $member->ID", "Created DESC, ID DESC")->getIterator();
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
		DB::query("DELETE FROM MemberPassword");

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
}