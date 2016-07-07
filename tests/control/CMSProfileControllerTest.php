<?php

use SilverStripe\ORM\DataExtension;

/**
 * @package framework
 * @subpackage tests
 */
class CMSProfileControllerTest extends FunctionalTest {

	protected static $fixture_file = 'CMSProfileControllerTest.yml';

	public $autoFollowRedirection = false;

	public function testMemberCantEditAnother() {
		$member = $this->objFromFixture('SilverStripe\\Security\\Member', 'user1');
		$anotherMember = $this->objFromFixture('SilverStripe\\Security\\Member', 'user2');
		$this->session()->inst_set('loggedInAs', $member->ID);

		$response = $this->post('admin/myprofile/EditForm', array(
			'action_save' => 1,
			'ID' => $anotherMember->ID,
			'FirstName' => 'JoeEdited',
			'Surname' => 'BloggsEdited',
			'Email' => $member->Email,
			'Locale' => $member->Locale,
			'Password[_Password]' => 'password',
			'Password[_ConfirmPassword]' => 'password',
		));

		$anotherMember = $this->objFromFixture('SilverStripe\\Security\\Member', 'user2');

		$this->assertNotEquals($anotherMember->FirstName, 'JoeEdited', 'FirstName field stays the same');
	}

	public function testMemberEditsOwnProfile() {
		$member = $this->objFromFixture('SilverStripe\\Security\\Member', 'user3');
		$this->session()->inst_set('loggedInAs', $member->ID);

		$response = $this->post('admin/myprofile/EditForm', array(
			'action_save' => 1,
			'ID' => $member->ID,
			'FirstName' => 'JoeEdited',
			'Surname' => 'BloggsEdited',
			'Email' => $member->Email,
			'Locale' => $member->Locale,
			'Password[_Password]' => 'password',
			'Password[_ConfirmPassword]' => 'password',
		));

		$member = $this->objFromFixture('SilverStripe\\Security\\Member', 'user3');

		$this->assertEquals('JoeEdited', $member->FirstName, 'FirstName field was changed');
	}

	public function testExtendedPermissionsStopEditingOwnProfile() {
		$existingExtensions = Config::inst()->get('SilverStripe\\Security\\Member', 'extensions');
		Config::inst()->update('SilverStripe\\Security\\Member', 'extensions', array('CMSProfileControllerTestExtension'));

		$member = $this->objFromFixture('SilverStripe\\Security\\Member', 'user1');
		$this->session()->inst_set('loggedInAs', $member->ID);

		$response = $this->post('admin/myprofile/EditForm', array(
			'action_save' => 1,
			'ID' => $member->ID,
			'FirstName' => 'JoeEdited',
			'Surname' => 'BloggsEdited',
			'Email' => $member->Email,
			'Locale' => $member->Locale,
			'Password[_Password]' => 'password',
			'Password[_ConfirmPassword]' => 'password',
		));

		$member = $this->objFromFixture('SilverStripe\\Security\\Member', 'user1');

		$this->assertNotEquals($member->FirstName, 'JoeEdited',
			'FirstName field was NOT changed because we modified canEdit');

		Config::inst()->remove('SilverStripe\\Security\\Member', 'extensions');
		Config::inst()->update('SilverStripe\\Security\\Member', 'extensions', $existingExtensions);
	}

}

/**
 * @package framework
 * @subpackage tests
 */
class CMSProfileControllerTestExtension extends DataExtension {

	public function canEdit($member = null) {
		return false;
	}

}
