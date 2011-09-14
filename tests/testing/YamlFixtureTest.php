<?php

class YamlFixtureTest extends SapphireTest {

	static $fixture_file = 'YamlFixtureTest.yml';

	protected $extraDataObjects = array(
		'YamlFixtureTest_DataObject',
		'YamlFixtureTest_DataObjectRelation',
	);
	
	function testAbsoluteFixturePath() {
		$absPath = Director::baseFolder() . '/sapphire/tests/testing/YamlFixtureTest.yml';
		$obj = new YamlFixture($absPath);
		$this->assertEquals($absPath, $obj->getFixtureFile());
	}
	
	function testRelativeFixturePath() {
		$relPath = 'sapphire/tests/testing/YamlFixtureTest.yml';
		$obj = new YamlFixture($relPath);
		$this->assertEquals(Director::baseFolder() . '/' . $relPath, $obj->getFixtureFile());
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	function testFailsWithInvalidFixturePath() {
		$invalidPath = 'sapphire/tests/testing/invalid.yml';
		$obj = new YamlFixture($invalidPath);
	}
	
	function testSQLInsert() {
		$object1 = DataObject::get_by_id("YamlFixtureTest_DataObject", $this->idFromFixture("YamlFixtureTest_DataObject", "testobject1"));
		$this->assertTrue($object1->ManyMany()->Count() == 2, "Should be 2 items in this manymany relationship");
		$object2 = DataObject::get_by_id("YamlFixtureTest_DataObject", $this->idFromFixture("YamlFixtureTest_DataObject", "testobject2"));
		$this->assertTrue($object2->ManyMany()->Count() == 2, "Should be 2 items in this manymany relationship");
	}
}

class YamlFixtureTest_DataObject extends DataObject implements TestOnly {
	static $db = array(
		"Name" => "Varchar"
	);
	static $many_many = array(
		"ManyMany" => "YamlFixtureTest_DataObjectRelation"
	);
}

class YamlFixtureTest_DataObjectRelation extends DataObject implements TestOnly {
	static $db = array(
		"Name" => "Varchar"
	);
	static $belongs_many_many = array(
		"TestParent" => "YamlFixtureTest_DataObject"
	); 
}
