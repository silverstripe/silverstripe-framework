<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Control\Cookie;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Group;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;
use SilverStripe\Security\Member_Validator;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\MemberAuthenticator\SessionAuthenticationHandler;
use SilverStripe\Security\MemberPassword;
use SilverStripe\Security\PasswordEncryptor_Blowfish;
use SilverStripe\Security\Permission;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Security;
use SilverStripe\Security\Tests\MemberTest\FieldsExtension;

class MemberTest extends FunctionalTest
{
    protected static $fixture_file = 'MemberTest.yml';

    protected $orig = array();

    protected static $illegal_extensions = [
        Member::class => '*',
    ];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        //Setting the locale has to happen in the constructor (using the setUp and tearDown methods doesn't work)
        //This is because the test relies on the yaml file being interpreted according to a particular date format
        //and this setup occurs before the setUp method is run
        i18n::config()->set('default_locale', 'en_US');
    }

    /**
     * @skipUpgrade
     */
    protected function setUp()
    {
        parent::setUp();

        Member::config()->set('unique_identifier_field', 'Email');
        Member::set_password_validator(null);
    }

    public function testPasswordEncryptionUpdatedOnChangedPassword()
    {
        Config::modify()->set(Security::class, 'password_encryption_algorithm', 'none');
        $member = Member::create();
        $member->Password = 'password';
        $member->write();
        $this->assertEquals('password', $member->Password);
        $this->assertEquals('none', $member->PasswordEncryption);
        Config::modify()->set(Security::class, 'password_encryption_algorithm', 'blowfish');
        $member->Password = 'newpassword';
        $member->write();
        $this->assertNotEquals('password', $member->Password);
        $this->assertNotEquals('newpassword', $member->Password);
        $this->assertEquals('blowfish', $member->PasswordEncryption);
    }

    public function testWriteDoesntMergeNewRecordWithExistingMember()
    {
        $this->expectException(ValidationException::class);
        $m1 = new Member();
        $m1->Email = 'member@test.com';
        $m1->write();

        $m2 = new Member();
        $m2->Email = 'member@test.com';
        $m2->write();
    }

    /**
     * @expectedException \SilverStripe\ORM\ValidationException
     */
    public function testWriteDoesntMergeExistingMemberOnIdentifierChange()
    {
        $m1 = new Member();
        $m1->Email = 'member@test.com';
        $m1->write();

        $m2 = new Member();
        $m2->Email = 'member_new@test.com';
        $m2->write();

        $m2->Email = 'member@test.com';
        $m2->write();
    }

    public function testDefaultPasswordEncryptionOnMember()
    {
        $memberWithPassword = new Member();
        $memberWithPassword->Password = 'mypassword';
        $memberWithPassword->write();
        $this->assertEquals(
            Security::config()->get('password_encryption_algorithm'),
            $memberWithPassword->PasswordEncryption,
            'Password encryption is set for new member records on first write (with setting "Password")'
        );

        $memberNoPassword = new Member();
        $memberNoPassword->write();
        $this->assertNull(
            $memberNoPassword->PasswordEncryption,
            'Password encryption is not set for new member records on first write, when not setting a "Password")'
        );
    }

    public function testKeepsEncryptionOnEmptyPasswords()
    {
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
        $auth = new MemberAuthenticator();
        $result = $auth->checkPassword($member, '');
        $this->assertTrue($result->isValid());
    }

    public function testSetPassword()
    {
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $member->Password = "test1";
        $member->write();
        $auth = new MemberAuthenticator();
        $result = $auth->checkPassword($member, 'test1');
        $this->assertTrue($result->isValid());
    }

    /**
     * Test that password changes are logged properly
     */
    public function testPasswordChangeLogging()
    {
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $this->assertNotNull($member);
        $member->Password = "test1";
        $member->write();

        $member->Password = "test2";
        $member->write();

        $member->Password = "test3";
        $member->write();

        $passwords = DataObject::get(MemberPassword::class, "\"MemberID\" = $member->ID", "\"Created\" DESC, \"ID\" DESC")
            ->getIterator();
        $this->assertNotNull($passwords);
        $passwords->rewind();
        $this->assertTrue($passwords->current()->checkPassword('test3'), "Password test3 not found in MemberRecord");

        $passwords->next();
        $this->assertTrue($passwords->current()->checkPassword('test2'), "Password test2 not found in MemberRecord");

        $passwords->next();
        $this->assertTrue($passwords->current()->checkPassword('test1'), "Password test1 not found in MemberRecord");

        $passwords->next();
        $this->assertInstanceOf('SilverStripe\\ORM\\DataObject', $passwords->current());
        $this->assertTrue(
            $passwords->current()->checkPassword('1nitialPassword'),
            "Password 1nitialPassword not found in MemberRecord"
        );

        //check we don't retain orphaned records when a member is deleted
        $member->delete();

        $passwords = MemberPassword::get()->filter('MemberID', $member->OldID);

        $this->assertCount(0, $passwords);
    }

    /**
     * Test that changed passwords will send an email
     */
    public function testChangedPasswordEmaling()
    {
        Member::config()->update('notify_password_change', true);

        $this->clearEmails();

        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $this->assertNotNull($member);
        $valid = $member->changePassword('32asDF##$$%%');
        $this->assertTrue($valid->isValid());

        $this->assertEmailSent(
            'testuser@example.com',
            null,
            'Your password has been changed',
            '/testuser@example\.com/'
        );
    }

    /**
     * Test that triggering "forgotPassword" sends an Email with a reset link
        */
    public function testForgotPasswordEmaling()
    {
        $this->clearEmails();
        $this->autoFollowRedirection = false;

        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $this->assertNotNull($member);

        // Initiate a password-reset
        $response = $this->post('Security/lostpassword/LostPasswordForm', array('Email' => $member->Email));

        $this->assertEquals($response->getStatusCode(), 302);

        // We should get redirected to Security/passwordsent
        $this->assertContains(
            'Security/lostpassword/passwordsent/testuser@example.com',
            urldecode($response->getHeader('Location'))
        );

        // Check existance of reset link
        $this->assertEmailSent(
            "testuser@example.com",
            null,
            'Your password reset link',
            '/Security\/changepassword\?m=' . $member->ID . '&amp;t=[^"]+/'
        );
    }

    /**
     * Test that passwords validate against NZ e-government guidelines
     *  - don't allow the use of the last 6 passwords
     *  - require at least 3 of lowercase, uppercase, digits and punctuation
     *  - at least 7 characters long
     */
    public function testValidatePassword()
    {
        /**
 * @var Member $member
*/
        $member = $this->objFromFixture(Member::class, 'test');
        $this->assertNotNull($member);

        Member::set_password_validator(new MemberTest\TestPasswordValidator());

        // BAD PASSWORDS

        $result = $member->changePassword('shorty');
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey("TOO_SHORT", $result->getMessages());

        $result = $member->changePassword('longone');
        $this->assertArrayNotHasKey("TOO_SHORT", $result->getMessages());
        $this->assertArrayHasKey("LOW_CHARACTER_STRENGTH", $result->getMessages());
        $this->assertFalse($result->isValid());

        $result = $member->changePassword('w1thNumb3rs');
        $this->assertArrayNotHasKey("LOW_CHARACTER_STRENGTH", $result->getMessages());
        $this->assertTrue($result->isValid());

        // Clear out the MemberPassword table to ensure that the system functions properly in that situation
        DB::query("DELETE FROM \"MemberPassword\"");

        // GOOD PASSWORDS

        $result = $member->changePassword('withSym###Ls');
        $this->assertArrayNotHasKey("LOW_CHARACTER_STRENGTH", $result->getMessages());
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls2');
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls3');
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls4');
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls5');
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls6');
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls7');
        $this->assertTrue($result->isValid());

        // CAN'T USE PASSWORDS 2-7, but I can use pasword 1

        $result = $member->changePassword('withSym###Ls2');
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey("PREVIOUS_PASSWORD", $result->getMessages());

        $result = $member->changePassword('withSym###Ls5');
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey("PREVIOUS_PASSWORD", $result->getMessages());

        $result = $member->changePassword('withSym###Ls7');
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey("PREVIOUS_PASSWORD", $result->getMessages());

        $result = $member->changePassword('withSym###Ls');
        $this->assertTrue($result->isValid());

        // HAVING DONE THAT, PASSWORD 2 is now available from the list

        $result = $member->changePassword('withSym###Ls2');
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls3');
        $this->assertTrue($result->isValid());

        $result = $member->changePassword('withSym###Ls4');
        $this->assertTrue($result->isValid());

        Member::set_password_validator(null);
    }

    /**
     * Test that the PasswordExpiry date is set when passwords are changed
     */
    public function testPasswordExpirySetting()
    {
        Member::config()->set('password_expiry_days', 90);

        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $this->assertNotNull($member);
        $valid = $member->changePassword("Xx?1234234");
        $this->assertTrue($valid->isValid());

        $expiryDate = date('Y-m-d', time() + 90*86400);
        $this->assertEquals($expiryDate, $member->PasswordExpiry);

        Member::config()->set('password_expiry_days', null);
        $valid = $member->changePassword("Xx?1234235");
        $this->assertTrue($valid->isValid());

        $this->assertNull($member->PasswordExpiry);
    }

    public function testIsPasswordExpired()
    {
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $this->assertNotNull($member);
        $this->assertFalse($member->isPasswordExpired());

        $member = $this->objFromFixture(Member::class, 'noexpiry');
        $member->PasswordExpiry = null;
        $this->assertFalse($member->isPasswordExpired());

        $member = $this->objFromFixture(Member::class, 'expiredpassword');
        $this->assertTrue($member->isPasswordExpired());

        // Check the boundary conditions
        // If PasswordExpiry == today, then it's expired
        $member->PasswordExpiry = date('Y-m-d');
        $this->assertTrue($member->isPasswordExpired());

        // If PasswordExpiry == tomorrow, then it's not
        $member->PasswordExpiry = date('Y-m-d', time() + 86400);
        $this->assertFalse($member->isPasswordExpired());
    }
    public function testInGroups()
    {
        /** @var Member $staffmember */
        $staffmember = $this->objFromFixture(Member::class, 'staffmember');
        /** @var Member $ceomember */
        $ceomember = $this->objFromFixture(Member::class, 'ceomember');

        $staffgroup = $this->objFromFixture(Group::class, 'staffgroup');
        $managementgroup = $this->objFromFixture(Group::class, 'managementgroup');
        $ceogroup = $this->objFromFixture(Group::class, 'ceogroup');

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

    public function testAddToGroupByCode()
    {
        /** @var Member $grouplessMember */
        $grouplessMember = $this->objFromFixture(Member::class, 'grouplessmember');
        /** @var Group $memberlessGroup */
        $memberlessGroup = $this->objFromFixture(Group::class, 'memberlessgroup');

        $this->assertFalse($grouplessMember->Groups()->exists());
        $this->assertFalse($memberlessGroup->Members()->exists());

        $grouplessMember->addToGroupByCode('memberless');

        $this->assertEquals($memberlessGroup->Members()->count(), 1);
        $this->assertEquals($grouplessMember->Groups()->count(), 1);

        $grouplessMember->addToGroupByCode('somegroupthatwouldneverexist', 'New Group');
        $this->assertEquals($grouplessMember->Groups()->count(), 2);

        /** @var Group $group */
        $group = DataObject::get_one(
            Group::class,
            array(
            '"Group"."Code"' => 'somegroupthatwouldneverexist'
            )
        );
        $this->assertNotNull($group);
        $this->assertEquals($group->Code, 'somegroupthatwouldneverexist');
        $this->assertEquals($group->Title, 'New Group');
    }

    public function testRemoveFromGroupByCode()
    {
        /** @var Member $grouplessMember */
        $grouplessMember = $this->objFromFixture(Member::class, 'grouplessmember');
        /** @var Group $memberlessGroup */
        $memberlessGroup = $this->objFromFixture(Group::class, 'memberlessgroup');

        $this->assertFalse($grouplessMember->Groups()->exists());
        $this->assertFalse($memberlessGroup->Members()->exists());

        $grouplessMember->addToGroupByCode('memberless');

        $this->assertEquals($memberlessGroup->Members()->count(), 1);
        $this->assertEquals($grouplessMember->Groups()->count(), 1);

        $grouplessMember->addToGroupByCode('somegroupthatwouldneverexist', 'New Group');
        $this->assertEquals($grouplessMember->Groups()->count(), 2);

        /** @var Group $group */
        $group = DataObject::get_one(Group::class, "\"Code\" = 'somegroupthatwouldneverexist'");
        $this->assertNotNull($group);
        $this->assertEquals($group->Code, 'somegroupthatwouldneverexist');
        $this->assertEquals($group->Title, 'New Group');

        $grouplessMember->removeFromGroupByCode('memberless');
        $this->assertEquals($memberlessGroup->Members()->count(), 0);
        $this->assertEquals($grouplessMember->Groups()->count(), 1);

        $grouplessMember->removeFromGroupByCode('somegroupthatwouldneverexist');
        $this->assertEquals($grouplessMember->Groups()->count(), 0);
    }

    public function testInGroup()
    {
        /** @var Member $staffmember */
        $staffmember = $this->objFromFixture(Member::class, 'staffmember');
        /** @var Member $managementmember */
        $managementmember = $this->objFromFixture(Member::class, 'managementmember');
        /** @var Member $accountingmember */
        $accountingmember = $this->objFromFixture(Member::class, 'accountingmember');
        /** @var Member $ceomember */
        $ceomember = $this->objFromFixture(Member::class, 'ceomember');

        /** @var Group $staffgroup */
        $staffgroup = $this->objFromFixture(Group::class, 'staffgroup');
        /** @var Group $managementgroup */
        $managementgroup = $this->objFromFixture(Group::class, 'managementgroup');
        /** @var Group $ceogroup */
        $ceogroup = $this->objFromFixture(Group::class, 'ceogroup');

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
    public function testCanManipulateOwnRecord()
    {
        $member = $this->objFromFixture(Member::class, 'test');
        $member2 = $this->objFromFixture(Member::class, 'staffmember');

        /* Not logged in, you can't view, delete or edit the record */
        $this->assertFalse($member->canView());
        $this->assertFalse($member->canDelete());
        $this->assertFalse($member->canEdit());

        /* Logged in users can edit their own record */
        $this->logInAs($member);
        $this->assertTrue($member->canView());
        $this->assertFalse($member->canDelete());
        $this->assertTrue($member->canEdit());

        /* Other uses cannot view, delete or edit others records */
        $this->logInAs($member2);
        $this->assertFalse($member->canView());
        $this->assertFalse($member->canDelete());
        $this->assertFalse($member->canEdit());

        $this->logOut();
    }

    public function testAuthorisedMembersCanManipulateOthersRecords()
    {
        $member = $this->objFromFixture(Member::class, 'test');
        $member2 = $this->objFromFixture(Member::class, 'staffmember');

        /* Group members with SecurityAdmin permissions can manipulate other records */
        $this->logInAs($member);
        $this->assertTrue($member2->canView());
        $this->assertTrue($member2->canDelete());
        $this->assertTrue($member2->canEdit());

        $this->logOut();
    }

    public function testExtendedCan()
    {
        $member = $this->objFromFixture(Member::class, 'test');

        /* Normal behaviour is that you can't view a member unless canView() on an extension returns true */
        $this->assertFalse($member->canView());
        $this->assertFalse($member->canDelete());
        $this->assertFalse($member->canEdit());

        /* Apply a extension that allows viewing in any case (most likely the case for member profiles) */
        Member::add_extension(MemberTest\ViewingAllowedExtension::class);
        $member2 = $this->objFromFixture(Member::class, 'staffmember');

        $this->assertTrue($member2->canView());
        $this->assertFalse($member2->canDelete());
        $this->assertFalse($member2->canEdit());

        /* Apply a extension that denies viewing of the Member */
        Member::remove_extension(MemberTest\ViewingAllowedExtension::class);
        Member::add_extension(MemberTest\ViewingDeniedExtension::class);
        $member3 = $this->objFromFixture(Member::class, 'managementmember');

        $this->assertFalse($member3->canView());
        $this->assertFalse($member3->canDelete());
        $this->assertFalse($member3->canEdit());

        /* Apply a extension that allows viewing and editing but denies deletion */
        Member::remove_extension(MemberTest\ViewingDeniedExtension::class);
        Member::add_extension(MemberTest\EditingAllowedDeletingDeniedExtension::class);
        $member4 = $this->objFromFixture(Member::class, 'accountingmember');

        $this->assertTrue($member4->canView());
        $this->assertFalse($member4->canDelete());
        $this->assertTrue($member4->canEdit());

        Member::remove_extension(MemberTest\EditingAllowedDeletingDeniedExtension::class);
    }

    /**
     * Tests for {@link Member::getName()} and {@link Member::setName()}
     */
    public function testName()
    {
        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $member->setName('Test Some User');
        $this->assertEquals('Test Some User', $member->getName());
        $member->setName('Test');
        $this->assertEquals('Test', $member->getName());
        $member->FirstName = 'Test';
        $member->Surname = '';
        $this->assertEquals('Test', $member->getName());
    }

    public function testMembersWithSecurityAdminAccessCantEditAdminsUnlessTheyreAdminsThemselves()
    {
        $adminMember = $this->objFromFixture(Member::class, 'admin');
        $otherAdminMember = $this->objFromFixture(Member::class, 'other-admin');
        $securityAdminMember = $this->objFromFixture(Member::class, 'test');
        $ceoMember = $this->objFromFixture(Member::class, 'ceomember');

        // Careful: Don't read as english language.
        // More precisely this should read canBeEditedBy()

        $this->assertTrue($adminMember->canEdit($adminMember), 'Admins can edit themselves');
        $this->assertTrue($otherAdminMember->canEdit($adminMember), 'Admins can edit other admins');
        $this->assertTrue($securityAdminMember->canEdit($adminMember), 'Admins can edit other members');

        $this->assertTrue($securityAdminMember->canEdit($securityAdminMember), 'Security-Admins can edit themselves');
        $this->assertFalse($adminMember->canEdit($securityAdminMember), 'Security-Admins can not edit other admins');
        $this->assertTrue($ceoMember->canEdit($securityAdminMember), 'Security-Admins can edit other members');
    }

    public function testOnChangeGroups()
    {
        /** @var Group $staffGroup */
        $staffGroup = $this->objFromFixture(Group::class, 'staffgroup');
        /** @var Member $staffMember */
        $staffMember = $this->objFromFixture(Member::class, 'staffmember');
        /** @var Member $adminMember */
        $adminMember = $this->objFromFixture(Member::class, 'admin');
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

        $this->logInAs($adminMember);
        $this->assertTrue(
            $staffMember->onChangeGroups(array($newAdminGroup->ID)),
            'Adding new admin group relation is allowed for normal users, when granter is logged in as admin'
        );
        $this->logOut();

        $this->assertTrue(
            $adminMember->onChangeGroups(array($newAdminGroup->ID)),
            'Adding new admin group relation is allowed for admin members'
        );
    }

    /**
     * Test Member_GroupSet::add
     */
    public function testOnChangeGroupsByAdd()
    {
        /** @var Member $staffMember */
        $staffMember = $this->objFromFixture(Member::class, 'staffmember');
        /** @var Member $adminMember */
        $adminMember = $this->objFromFixture(Member::class, 'admin');

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
        $this->logOut();
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
    public function testOnChangeGroupsBySetIDList()
    {
        /** @var Member $staffMember */
        $staffMember = $this->objFromFixture(Member::class, 'staffmember');

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
    public function testUpdateCMSFields()
    {
        Member::add_extension(FieldsExtension::class);

        $member = Member::singleton();
        $fields = $member->getCMSFields();

        /**
 * @skipUpgrade
*/
        $this->assertNotNull($fields->dataFieldByName('Email'), 'Scaffolded fields are retained');
        $this->assertNull($fields->dataFieldByName('Salt'), 'Field modifications run correctly');
        $this->assertNotNull($fields->dataFieldByName('TestMemberField'), 'Extension is applied correctly');

        Member::remove_extension(FieldsExtension::class);
    }

    /**
     * Test that all members are returned
     */
    public function testMap_in_groupsReturnsAll()
    {
        $members = Member::map_in_groups();
        $this->assertEquals(13, $members->count(), 'There are 12 members in the mock plus a fake admin');
    }

    /**
     * Test that only admin members are returned
     */
    public function testMap_in_groupsReturnsAdmins()
    {
        $adminID = $this->objFromFixture(Group::class, 'admingroup')->ID;
        $members = Member::map_in_groups($adminID)->toArray();

        $admin = $this->objFromFixture(Member::class, 'admin');
        $otherAdmin = $this->objFromFixture(Member::class, 'other-admin');

        $this->assertTrue(
            in_array($admin->getTitle(), $members),
            $admin->getTitle() . ' should be in the returned list.'
        );
        $this->assertTrue(
            in_array($otherAdmin->getTitle(), $members),
            $otherAdmin->getTitle() . ' should be in the returned list.'
        );
        $this->assertEquals(2, count($members), 'There should be 2 members from the admin group');
    }

    /**
     * Add the given array of member extensions as class names.
     * This is useful for re-adding extensions after being removed
     * in a test case to produce an unbiased test.
     *
     * @param  array $extensions
     * @return array The added extensions
     */
    protected function addExtensions($extensions)
    {
        if ($extensions) {
            foreach ($extensions as $extension) {
                Member::add_extension($extension);
            }
        }
        return $extensions;
    }

    /**
     * Remove given extensions from Member. This is useful for
     * removing extensions that could produce a biased
     * test result, as some extensions applied by project
     * code or modules can do this.
     *
     * @param  array $extensions
     * @return array The removed extensions
     */
    protected function removeExtensions($extensions)
    {
        if ($extensions) {
            foreach ($extensions as $extension) {
                Member::remove_extension($extension);
            }
        }
        return $extensions;
    }

    public function testGenerateAutologinTokenAndStoreHash()
    {
        $enc = new PasswordEncryptor_Blowfish();

        $m = new Member();
        $m->PasswordEncryption = 'blowfish';
        $m->Salt = $enc->salt('123');

        $token = $m->generateAutologinTokenAndStoreHash();

        $this->assertEquals($m->encryptWithUserSettings($token), $m->AutoLoginHash, 'Stores the token as ahash.');
    }

    public function testValidateAutoLoginToken()
    {
        $enc = new PasswordEncryptor_Blowfish();

        $m1 = new Member();
        $m1->PasswordEncryption = 'blowfish';
        $m1->Salt = $enc->salt('123');
        $m1Token = $m1->generateAutologinTokenAndStoreHash();

        $m2 = new Member();
        $m2->PasswordEncryption = 'blowfish';
        $m2->Salt = $enc->salt('456');
        $m2->generateAutologinTokenAndStoreHash();

        $this->assertTrue($m1->validateAutoLoginToken($m1Token), 'Passes token validity test against matching member.');
        $this->assertFalse($m2->validateAutoLoginToken($m1Token), 'Fails token validity test against other member.');
    }

    public function testRememberMeHashGeneration()
    {
        /** @var Member $m1 */
        $m1 = $this->objFromFixture(Member::class, 'grouplessmember');

        Injector::inst()->get(IdentityStore::class)->logIn($m1, true);

        $hashes = RememberLoginHash::get()->filter('MemberID', $m1->ID);
        $this->assertEquals($hashes->count(), 1);
        /** @var RememberLoginHash $firstHash */
        $firstHash = $hashes->first();
        $this->assertNotNull($firstHash->DeviceID);
        $this->assertNotNull($firstHash->Hash);
    }

    public function testRememberMeHashAutologin()
    {
        /**
 * @var Member $m1
*/
        $m1 = $this->objFromFixture(Member::class, 'noexpiry');

        Injector::inst()->get(IdentityStore::class)->logIn($m1, true);

        /** @var RememberLoginHash $firstHash */
        $firstHash = RememberLoginHash::get()->filter('MemberID', $m1->ID)->first();
        $this->assertNotNull($firstHash);

        // re-generates the hash so we can get the token
        $firstHash->Hash = $firstHash->getNewHash($m1);
        $token = $firstHash->getToken();
        $firstHash->write();

        $response = $this->get(
            'Security/login',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':' . $token,
                'alc_device' => $firstHash->DeviceID
            )
        );
        $message = Convert::raw2xml(
            _t(
                'SilverStripe\\Security\\Member.LOGGEDINAS',
                "You're logged in as {name}.",
                array('name' => $m1->FirstName)
            )
        );
        $this->assertContains($message, $response->getBody());

        $this->logOut();

        // A wrong token or a wrong device ID should not let us autologin
        $response = $this->get(
            'Security/login',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':asdfasd' . str_rot13($token),
                'alc_device' => $firstHash->DeviceID
            )
        );
        $this->assertNotContains($message, $response->getBody());

        $response = $this->get(
            'Security/login',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':' . $token,
                'alc_device' => str_rot13($firstHash->DeviceID)
            )
        );
        $this->assertNotContains($message, $response->getBody());

        // Re-logging (ie 'alc_enc' has expired), and not checking the "Remember Me" option
        // should remove all previous hashes for this device
        $response = $this->post(
            'Security/login/default/LoginForm',
            array(
                'Email' => $m1->Email,
                'Password' => '1nitialPassword',
                'action_doLogin' => 'action_doLogin'
            ),
            null,
            $this->session(),
            null,
            array(
                'alc_device' => $firstHash->DeviceID
            )
        );
        $this->assertContains($message, $response->getBody());
        $this->assertEquals(RememberLoginHash::get()->filter('MemberID', $m1->ID)->count(), 0);
    }

    public function testExpiredRememberMeHashAutologin()
    {
        /** @var Member $m1 */
        $m1 = $this->objFromFixture(Member::class, 'noexpiry');
        Injector::inst()->get(IdentityStore::class)->logIn($m1, true);
        /** @var RememberLoginHash $firstHash */
        $firstHash = RememberLoginHash::get()->filter('MemberID', $m1->ID)->first();
        $this->assertNotNull($firstHash);

        // re-generates the hash so we can get the token
        $firstHash->Hash = $firstHash->getNewHash($m1);
        $token = $firstHash->getToken();
        $firstHash->ExpiryDate = '2000-01-01 00:00:00';
        $firstHash->write();

        DBDatetime::set_mock_now('1999-12-31 23:59:59');

        $response = $this->get(
            'Security/login',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':' . $token,
                'alc_device' => $firstHash->DeviceID
            )
        );
        $message = Convert::raw2xml(
            _t(
                'SilverStripe\\Security\\Member.LOGGEDINAS',
                "You're logged in as {name}.",
                array('name' => $m1->FirstName)
            )
        );
        $this->assertContains($message, $response->getBody());

        $this->logOut();

        // re-generates the hash so we can get the token
        $firstHash->Hash = $firstHash->getNewHash($m1);
        $token = $firstHash->getToken();
        $firstHash->ExpiryDate = '2000-01-01 00:00:00';
        $firstHash->write();

        DBDatetime::set_mock_now('2000-01-01 00:00:01');

        $response = $this->get(
            'Security/login',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':' . $token,
                'alc_device' => $firstHash->DeviceID
            )
        );
        $this->assertNotContains($message, $response->getBody());
        $this->logOut();
        DBDatetime::clear_mock_now();
    }

    public function testRememberMeMultipleDevices()
    {
        /** @var Member $m1 */
        $m1 = $this->objFromFixture(Member::class, 'noexpiry');

        // First device
        Injector::inst()->get(IdentityStore::class)->logIn($m1, true);
        Cookie::set('alc_device', null);
        // Second device
        Injector::inst()->get(IdentityStore::class)->logIn($m1, true);

        // Hash of first device
        /** @var RememberLoginHash $firstHash */
        $firstHash = RememberLoginHash::get()->filter('MemberID', $m1->ID)->first();
        $this->assertNotNull($firstHash);

        // Hash of second device
        /** @var RememberLoginHash $secondHash */
        $secondHash = RememberLoginHash::get()->filter('MemberID', $m1->ID)->last();
        $this->assertNotNull($secondHash);

        // DeviceIDs are different
        $this->assertNotEquals($firstHash->DeviceID, $secondHash->DeviceID);

        // re-generates the hashes so we can get the tokens
        $firstHash->Hash = $firstHash->getNewHash($m1);
        $firstToken = $firstHash->getToken();
        $firstHash->write();

        $secondHash->Hash = $secondHash->getNewHash($m1);
        $secondToken = $secondHash->getToken();
        $secondHash->write();

        // Accessing the login page should show the user's name straight away
        $response = $this->get(
            'Security/login',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':' . $firstToken,
                'alc_device' => $firstHash->DeviceID
            )
        );
        $message = Convert::raw2xml(
            _t(
                'SilverStripe\\Security\\Member.LOGGEDINAS',
                "You're logged in as {name}.",
                array('name' => $m1->FirstName)
            )
        );
        $this->assertContains($message, $response->getBody());

        // Test that removing session but not cookie keeps user
        /** @var SessionAuthenticationHandler $sessionHandler */
        $sessionHandler = Injector::inst()->get(SessionAuthenticationHandler::class);
        $sessionHandler->logOut();
        Security::setCurrentUser(null);

        // Accessing the login page from the second device
        $response = $this->get(
            'Security/login',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':' . $secondToken,
                'alc_device' => $secondHash->DeviceID
            )
        );
        $this->assertContains($message, $response->getBody());

        // Logging out from the second device - only one device being logged out
        RememberLoginHash::config()->update('logout_across_devices', false);
        $this->get(
            'Security/logout',
            $this->session(),
            null,
            array(
                'alc_enc' => $m1->ID . ':' . $secondToken,
                'alc_device' => $secondHash->DeviceID
            )
        );
        $this->assertEquals(
            RememberLoginHash::get()->filter(array('MemberID'=>$m1->ID, 'DeviceID'=>$firstHash->DeviceID))->count(),
            1
        );

        // Logging out from any device when all login hashes should be removed
        RememberLoginHash::config()->update('logout_across_devices', true);
        Injector::inst()->get(IdentityStore::class)->logIn($m1, true);
        $this->get('Security/logout', $this->session());
        $this->assertEquals(
            RememberLoginHash::get()->filter('MemberID', $m1->ID)->count(),
            0
        );
    }

    public function testCanDelete()
    {
        $admin1 = $this->objFromFixture(Member::class, 'admin');
        $admin2 = $this->objFromFixture(Member::class, 'other-admin');
        $member1 = $this->objFromFixture(Member::class, 'grouplessmember');
        $member2 = $this->objFromFixture(Member::class, 'noformatmember');

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

    public function testFailedLoginCount()
    {
        $maxFailedLoginsAllowed = 3;
        //set up the config variables to enable login lockouts
        Member::config()->update('lock_out_after_incorrect_logins', $maxFailedLoginsAllowed);

        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');
        $failedLoginCount = $member->FailedLoginCount;

        for ($i = 1; $i < $maxFailedLoginsAllowed; ++$i) {
            $member->registerFailedLogin();

            $this->assertEquals(
                ++$failedLoginCount,
                $member->FailedLoginCount,
                'Failed to increment $member->FailedLoginCount'
            );

            $this->assertTrue(
                $member->canLogin(),
                "Member has been locked out too early"
            );
        }
    }

    public function testMemberValidator()
    {
        // clear custom requirements for this test
        Member_Validator::config()->update('customRequired', null);
        /** @var Member $memberA */
        $memberA = $this->objFromFixture(Member::class, 'admin');
        /** @var Member $memberB */
        $memberB = $this->objFromFixture(Member::class, 'test');

        // create a blank form
        $form = new MemberTest\ValidatorForm();

        $validator = new Member_Validator();
        $validator->setForm($form);

        // Simulate creation of a new member via form, but use an existing member identifier
        $fail = $validator->php(
            array(
            'FirstName' => 'Test',
            'Email' => $memberA->Email
            )
        );

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
        $fail = $validator->php(
            array(
            'FirstName' => 'Test',
            'Email' => $memberA->Email
            )
        );

        // Simulate update to a new Email address
        $pass1 = $validator->php(
            array(
            'FirstName' => 'Test',
            'Email' => 'membervalidatortest@testing.com'
            )
        );

        // Pass in the same Email address that the member already has. Ensure that case is valid
        $pass2 = $validator->php(
            array(
            'FirstName' => 'Test',
            'Surname' => 'User',
            'Email' => $memberB->Email
            )
        );

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
        Member_Validator::config()->update('customRequired', null);

        // create a blank form
        $form = new MemberTest\ValidatorForm();

        // Test extensions
        Member_Validator::add_extension(MemberTest\SurnameMustMatchFirstNameExtension::class);
        $validator = new Member_Validator();
        $validator->setForm($form);

        // This test should fail, since the extension enforces FirstName == Surname
        $fail = $validator->php(
            array(
            'FirstName' => 'Test',
            'Surname' => 'User',
            'Email' => 'test-member-validator-extension@testing.com'
            )
        );

        $pass = $validator->php(
            array(
            'FirstName' => 'Test',
            'Surname' => 'Test',
            'Email' => 'test-member-validator-extension@testing.com'
            )
        );

        $this->assertFalse(
            $fail,
            'Member_Validator must fail because of added extension.'
        );

        $this->assertTrue(
            $pass,
            'Member_Validator must succeed, since it meets all requirements.'
        );

        // Add another extension that always fails. This ensures that all extensions are considered in the validation
        Member_Validator::add_extension(MemberTest\AlwaysFailExtension::class);
        $validator = new Member_Validator();
        $validator->setForm($form);

        // Even though the data is valid, This test should still fail, since one extension always returns false
        $fail = $validator->php(
            array(
            'FirstName' => 'Test',
            'Surname' => 'Test',
            'Email' => 'test-member-validator-extension@testing.com'
            )
        );

        $this->assertFalse(
            $fail,
            'Member_Validator must fail because of added extensions.'
        );

        // Remove added extensions
        Member_Validator::remove_extension(MemberTest\AlwaysFailExtension::class);
        Member_Validator::remove_extension(MemberTest\SurnameMustMatchFirstNameExtension::class);
    }

    public function testCustomMemberValidator()
    {
        // clear custom requirements for this test
        Member_Validator::config()->update('customRequired', null);

        $member = $this->objFromFixture(Member::class, 'admin');

        $form = new MemberTest\ValidatorForm();
        $form->loadDataFrom($member);

        $validator = new Member_Validator();
        $validator->setForm($form);

        $pass = $validator->php(
            array(
            'FirstName' => 'Borris',
            'Email' => 'borris@silverstripe.com'
            )
        );

        $fail = $validator->php(
            array(
            'Email' => 'borris@silverstripe.com',
            'Surname' => ''
            )
        );

        $this->assertTrue($pass, 'Validator requires a FirstName and Email');
        $this->assertFalse($fail, 'Missing FirstName');

        $ext = new MemberTest\ValidatorExtension();
        $ext->updateValidator($validator);

        $pass = $validator->php(
            array(
            'FirstName' => 'Borris',
            'Email' => 'borris@silverstripe.com'
            )
        );

        $fail = $validator->php(
            array(
            'Email' => 'borris@silverstripe.com'
            )
        );

        $this->assertFalse($pass, 'Missing surname');
        $this->assertFalse($fail, 'Missing surname value');

        $fail = $validator->php(
            array(
            'Email' => 'borris@silverstripe.com',
            'Surname' => 'Silverman'
            )
        );

        $this->assertTrue($fail, 'Passes with email and surname now (no firstname)');
    }

    public function testCurrentUser()
    {
        $this->assertNull(Security::getCurrentUser());

        $adminMember = $this->objFromFixture(Member::class, 'admin');
        $this->logInAs($adminMember);

        $userFromSession = Security::getCurrentUser();
        $this->assertEquals($adminMember->ID, $userFromSession->ID);
    }

    /**
     * @covers \SilverStripe\Security\Member::actAs()
     */
    public function testActAsUserPermissions()
    {
        $this->assertNull(Security::getCurrentUser());

        /** @var Member $adminMember */
        $adminMember = $this->objFromFixture(Member::class, 'admin');

        // Check acting as admin when not logged in
        $checkAdmin = Member::actAs($adminMember, function () {
            return Permission::check('ADMIN');
        });
        $this->assertTrue($checkAdmin);

        // Check nesting
        $checkAdmin = Member::actAs($adminMember, function () {
            return Member::actAs(null, function () {
                return Permission::check('ADMIN');
            });
        });
        $this->assertFalse($checkAdmin);

        // Check logging in as non-admin user
        $this->logInWithPermission('TEST_PERMISSION');

        $hasPerm = Member::actAs(null, function () {
            return Permission::check('TEST_PERMISSION');
        });
        $this->assertFalse($hasPerm);

        // Check permissions can be promoted
        $checkAdmin = Member::actAs($adminMember, function () {
            return Permission::check('ADMIN');
        });
        $this->assertTrue($checkAdmin);
    }

    /**
     * @covers \SilverStripe\Security\Member::actAs()
     */
    public function testActAsUser()
    {
        $this->assertNull(Security::getCurrentUser());

        /** @var Member $adminMember */
        $adminMember = $this->objFromFixture(Member::class, 'admin');
        $member = Member::actAs($adminMember, function () {
            return Security::getCurrentUser();
        });
        $this->assertEquals($adminMember->ID, $member->ID);

        // Check nesting
        $member = Member::actAs($adminMember, function () {
            return Member::actAs(null, function () {
                return Security::getCurrentUser();
            });
        });
        $this->assertEmpty($member);
    }

    public function testChangePasswordWithExtensionsThatModifyValidationResult()
    {
        // Default behaviour
        $member = $this->objFromFixture(Member::class, 'admin');
        $result = $member->changePassword('my-secret-new-password');
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->isValid());

        // With an extension added
        Member::add_extension(MemberTest\ExtendedChangePasswordExtension::class);
        $member = $this->objFromFixture(Member::class, 'admin');
        $result = $member->changePassword('my-second-secret-password');
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertFalse($result->isValid());
    }
}
