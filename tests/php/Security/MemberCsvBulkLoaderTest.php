<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\MemberCsvBulkLoader;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

class MemberCsvBulkLoaderTest extends SapphireTest
{
    protected static $fixture_file = 'MemberCsvBulkLoaderTest.yml';

    protected function setUp(): void
    {
        parent::setUp();
        Member::set_password_validator(null);
    }

    public function testNewImport()
    {
        $loader = new MemberCsvBulkLoader();
        $results = $loader->load(__DIR__ . '/MemberCsvBulkLoaderTest/MemberCsvBulkLoaderTest.csv');
        $created = $results->Created()->toArray();
        $this->assertEquals(count($created ?? []), 2);
        $this->assertEquals($created[0]->Email, 'author1@test.com');
        $this->assertEquals($created[1]->Email, 'author2@test.com');
    }

    public function testOverwriteExistingImport()
    {
        $author1 = new Member();
        $author1->FirstName = 'author1_first_old';
        $author1->Email = 'author1@test.com';
        $author1->write();

        $loader = new MemberCsvBulkLoader();
        $results = $loader->load(__DIR__ . '/MemberCsvBulkLoaderTest/MemberCsvBulkLoaderTest.csv');
        $created = $results->Created()->toArray();
        $this->assertEquals(count($created ?? []), 1);
        $updated = $results->Updated()->toArray();
        $this->assertEquals(count($updated ?? []), 1);
        $this->assertEquals($created[0]->Email, 'author2@test.com');
        $this->assertEquals($updated[0]->Email, 'author1@test.com');
        $this->assertEquals($updated[0]->FirstName, 'author1_first');
    }

    public function testAddToPredefinedGroups()
    {
        $existinggroup = $this->objFromFixture(Group::class, 'existinggroup');

        $loader = new MemberCsvBulkLoader();
        $loader->setGroups([$existinggroup]);

        $results = $loader->load(__DIR__ . '/MemberCsvBulkLoaderTest/MemberCsvBulkLoaderTest.csv');

        $created = $results->Created()->toArray();
        $this->assertEquals(1, count($created[0]->Groups()->column('ID') ?? []));
        $this->assertContains($existinggroup->ID, $created[0]->Groups()->column('ID'));

        $this->assertEquals(1, count($created[1]->Groups()->column('ID') ?? []));
        $this->assertContains($existinggroup->ID, $created[1]->Groups()->column('ID'));
    }

    public function testAddToCsvColumnGroupsByCode()
    {
        $existinggroup = $this->objFromFixture(Group::class, 'existinggroup');

        $loader = new MemberCsvBulkLoader();
        $results = $loader->load(__DIR__ . '/MemberCsvBulkLoaderTest/MemberCsvBulkLoaderTest_withGroups.csv');

        $newgroup = DataObject::get_one(
            Group::class,
            [
            '"Group"."Code"' => 'newgroup'
            ]
        );
        $this->assertEquals($newgroup->Title, 'newgroup');

        $created = $results->Created()->toArray();
        $this->assertEquals(1, count($created[0]->Groups()->column('ID') ?? []));
        $this->assertContains($existinggroup->ID, $created[0]->Groups()->column('ID'));

        $this->assertEquals(2, count($created[1]->Groups()->column('ID') ?? []));
        $this->assertContains($existinggroup->ID, $created[1]->Groups()->column('ID'));
        $this->assertContains($newgroup->ID, $created[1]->Groups()->column('ID'));
    }

    public function testCleartextPasswordsAreHashedWithDefaultAlgo()
    {
        $loader = new MemberCsvBulkLoader();

        $results = $loader->load(__DIR__ . '/MemberCsvBulkLoaderTest/MemberCsvBulkLoaderTest_cleartextpws.csv');

        $member = $results->Created()->First();
        $memberID = $member->ID;
        DataObject::flush_and_destroy_cache();
        $member = DataObject::get_by_id(Member::class, $memberID);

        $this->assertEquals(Security::config()->password_encryption_algorithm, $member->getField('PasswordEncryption'));
        $auth = new MemberAuthenticator();
        $result = $auth->checkPassword($member, 'mypassword');
        $this->assertTrue($result->isValid());
    }
}
