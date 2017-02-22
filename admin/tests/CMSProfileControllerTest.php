<?php

namespace SilverStripe\Admin\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;

class CMSProfileControllerTest extends FunctionalTest
{

    protected static $fixture_file = 'CMSProfileControllerTest.yml';

    public $autoFollowRedirection = false;

    public function testMemberCantEditAnother()
    {
        $member = $this->objFromFixture(Member::class, 'user1');
        $anotherMember = $this->objFromFixture(Member::class, 'user2');
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

        $anotherMember = $this->objFromFixture(Member::class, 'user2');

        $this->assertNotEquals($anotherMember->FirstName, 'JoeEdited', 'FirstName field stays the same');
    }

    public function testMemberEditsOwnProfile()
    {
        $member = $this->objFromFixture(Member::class, 'user3');
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

        $member = $this->objFromFixture(Member::class, 'user3');

        $this->assertEquals('JoeEdited', $member->FirstName, 'FirstName field was changed');
    }

    public function testExtendedPermissionsStopEditingOwnProfile()
    {
        $existingExtensions = Member::config()->get('extensions', Config::EXCLUDE_EXTRA_SOURCES);
        Member::config()->update('extensions', [
            CMSProfileControllerTest\TestExtension::class
        ]);

        $member = $this->objFromFixture(Member::class, 'user1');
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

        $member = $this->objFromFixture(Member::class, 'user1');

        $this->assertNotEquals(
            $member->FirstName,
            'JoeEdited',
            'FirstName field was NOT changed because we modified canEdit'
        );

        Member::config()
            ->remove('extensions')
            ->update('extensions', $existingExtensions);
    }
}
