<?php
/**
 * @package framework
 * @subpackage tests
 */
class MemberCsvBulkLoaderTest extends SapphireTest {
	protected static $fixture_file = 'MemberCsvBulkLoaderTest.yml';

	public function testNewImport() {
		$loader = new MemberCsvBulkLoader();
		$results = $loader->load($this->getCurrentRelativePath() . '/MemberCsvBulkLoaderTest.csv');
		$created = $results->Created()->toArray();
		$this->assertEquals(count($created), 2);
		$this->assertEquals($created[0]->Email, 'author1@test.com');
		$this->assertEquals($created[1]->Email, 'author2@test.com');
	}

	public function testOverwriteExistingImport() {
		$author1 = new Member();
		$author1->FirstName = 'author1_first_old';
		$author1->Email = 'author1@test.com';
		$author1->write();

		$loader = new MemberCsvBulkLoader();
		$results = $loader->load($this->getCurrentRelativePath() . '/MemberCsvBulkLoaderTest.csv');
		$created = $results->Created()->toArray();
		$this->assertEquals(count($created), 1);
		$updated = $results->Updated()->toArray();
		$this->assertEquals(count($updated), 1);
		$this->assertEquals($created[0]->Email, 'author2@test.com');
		$this->assertEquals($updated[0]->Email, 'author1@test.com');
		$this->assertEquals($updated[0]->FirstName, 'author1_first');
	}

	public function testAddToPredefinedGroups() {
		$existinggroup = $this->objFromFixture('Group', 'existinggroup');

		$loader = new MemberCsvBulkLoader();
		$loader->setGroups(array($existinggroup));

		$results = $loader->load($this->getCurrentRelativePath() . '/MemberCsvBulkLoaderTest.csv');

		$created = $results->Created()->toArray();
		$this->assertEquals(1, count($created[0]->Groups()->column('ID')));
		$this->assertContains($existinggroup->ID, $created[0]->Groups()->column('ID'));

		$this->assertEquals(1, count($created[1]->Groups()->column('ID')));
		$this->assertContains($existinggroup->ID, $created[1]->Groups()->column('ID'));
	}

	public function testAddToCsvColumnGroupsByCode() {
		$existinggroup = $this->objFromFixture('Group', 'existinggroup');

		$loader = new MemberCsvBulkLoader();
		$results = $loader->load($this->getCurrentRelativePath() . '/MemberCsvBulkLoaderTest_withGroups.csv');

		$newgroup = DataObject::get_one('Group', array(
			'"Group"."Code"' => 'newgroup'
		));
		$this->assertEquals($newgroup->Title, 'newgroup');

		$created = $results->Created()->toArray();
		$this->assertEquals(1, count($created[0]->Groups()->column('ID')));
		$this->assertContains($existinggroup->ID, $created[0]->Groups()->column('ID'));

		$this->assertEquals(2, count($created[1]->Groups()->column('ID')));
		$this->assertContains($existinggroup->ID, $created[1]->Groups()->column('ID'));
		$this->assertContains($newgroup->ID, $created[1]->Groups()->column('ID'));
	}

	public function testCleartextPasswordsAreHashedWithDefaultAlgo() {
		$loader = new MemberCsvBulkLoader();

		$results = $loader->load($this->getCurrentRelativePath() . '/MemberCsvBulkLoaderTest_cleartextpws.csv');

		$member = $results->Created()->First();
		$memberID = $member->ID;
		DataObject::flush_and_destroy_cache();
		$member = DataObject::get_by_id('Member', $memberID);

		// TODO Direct getter doesn't work, wtf!
		$this->assertEquals(Security::config()->password_encryption_algorithm, $member->getField('PasswordEncryption'));
		$result = $member->checkPassword('mypassword');
		$this->assertTrue($result->valid());
	}
}
