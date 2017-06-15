<?php

class HasManyListTest extends SapphireTest {

	protected static $fixture_file = array(
		'DataObjectTest.yml', // Borrow the model from DataObjectTest
		'HasManyListTest.yml'
	);

	protected $extraDataObjects = array(
		'DataObjectTest_Team',
		'DataObjectTest_SubTeam',
		'DataObjectTest_Player',
		'DataObjectTest_TeamComment',
		'DataObjectTest_Sortable',
		'DataObjectTest_Company',
		'DataObjectTest_EquipmentCompany',
		'DataObjectTest_SubEquipmentCompany',
		'DataObjectTest_Fan',
		'ManyManyListTest_Product',
		'ManyManyListTest_Category',
		'HasManyListTest_Company',
		'HasManyListTest_Employee',
		'HasManyListTest_CompanyCar',
	);

	public function testRelationshipEmptyOnNewRecords() {
		// Relies on the fact that (unrelated) comments exist in the fixture file already
		$newTeam = new DataObjectTest_Team(); // has_many Comments
		$this->assertEquals(array(), $newTeam->Comments()->column('ID'));
	}

	/**
	 * Test that related objects can be removed from a relation
	 */
	public function testRemoveRelation() {

		// Check that expected teams exist
		$list = DataObjectTest_Team::get();
		$this->assertEquals(
			array('Subteam 1', 'Subteam 2', 'Subteam 3', 'Team 1', 'Team 2', 'Team 3'),
			$list->sort('Title')->column('Title')
		);

		// Test that each team has the correct fans
		$team1 = $this->objFromFixture('DataObjectTest_Team', 'team1');
		$team2 = $this->objFromFixture('DataObjectTest_Team', 'team2');
		$this->assertEquals(array('Bob', 'Joe'), $team1->Comments()->sort('Name')->column('Name'));
		$this->assertEquals(array('Phil'), $team2->Comments()->sort('Name')->column('Name'));

		// Test that removing comments from unrelated team has no effect
		$team1comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment1');
		$team2comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment3');
		$team1->Comments()->remove($team2comment);
		$team2->Comments()->remove($team1comment);
		$this->assertEquals(array('Bob', 'Joe'), $team1->Comments()->sort('Name')->column('Name'));
		$this->assertEquals(array('Phil'), $team2->Comments()->sort('Name')->column('Name'));
		$this->assertEquals($team1->ID, $team1comment->TeamID);
		$this->assertEquals($team2->ID, $team2comment->TeamID);

		// Test that removing items from the related team resets the has_one relations on the fan
		$team1comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment1');
		$team2comment = $this->objFromFixture('DataObjectTest_TeamComment', 'comment3');
		$team1->Comments()->remove($team1comment);
		$team2->Comments()->remove($team2comment);
		$this->assertEquals(array('Bob'), $team1->Comments()->sort('Name')->column('Name'));
		$this->assertEquals(array(), $team2->Comments()->sort('Name')->column('Name'));
		$this->assertEmpty($team1comment->TeamID);
		$this->assertEmpty($team2comment->TeamID);
	}

	/**
	 * Test that multiple models with the same "has_one" relation name (and therefore the same "<hasone>ID"
	 * column name) do not trigger a "Column '<hasone>ID' in where clause is ambiguous" error
	 */
	public function testAmbiguousRelationshipNames() {
		$company = $this->objFromFixture('HasManyListTest_Company', 'silverstripe');

		$johnsCars = $company->CompanyCars()->filter(array('User.Name' => 'John Smith'));
		$this->assertCount(1, $johnsCars, 'John Smith has one company car');

		$jennysCars = $company->CompanyCars()->filter(array('User.Name' => 'Jenny Smith'));
		$this->assertCount(2, $jennysCars, 'Jenny Smith has two company cars');
	}

}

class HasManyListTest_Company extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar(100)'
	);

	private static $has_many = array(
		'Employees' => 'HasManyListTest_Employee',
		'CompanyCars' => 'HasManyListTest_CompanyCar'
	);

}

class HasManyListTest_Employee extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar(100)'
	);

	private static $has_one = array(
		'Company' => 'HasManyListTest_Company'
	);

	private static $has_many = array(
		'CompanyCars' => 'HasManyListTest_CompanyCar'
	);

}

class HasManyListTest_CompanyCar extends DataObject implements TestOnly {

	private static $db = array(
		'Make' => 'Varchar(100)',
		'Model' => 'Varchar(100)'
	);

	private static $has_one = array(
		'User' => 'HasManyListTest_Employee',
		'Company' => 'HasManyListTest_Company'
	);

}
