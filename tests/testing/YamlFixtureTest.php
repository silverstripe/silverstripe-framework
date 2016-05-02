<?php

class YamlFixtureTest extends SapphireTest {

	protected $extraDataObjects = array(
		'YamlFixtureTest_DataObject',
		'YamlFixtureTest_DataObjectRelation',
	);

	public function testAbsoluteFixturePath() {
		$absPath = FRAMEWORK_PATH . '/tests/testing/YamlFixtureTest.yml';
		$obj = Injector::inst()->create('YamlFixture', $absPath);
		$this->assertEquals($absPath, $obj->getFixtureFile());
		$this->assertNull($obj->getFixtureString());
	}

	public function testRelativeFixturePath() {
		$relPath = FRAMEWORK_DIR . '/tests/testing/YamlFixtureTest.yml';
		$obj = Injector::inst()->create('YamlFixture', $relPath);
		$this->assertEquals(Director::baseFolder() . '/' . $relPath, $obj->getFixtureFile());
		$this->assertNull($obj->getFixtureString());
	}

	public function testStringFixture() {
		$absPath = FRAMEWORK_PATH . '/tests/testing/YamlFixtureTest.yml';
		$string = file_get_contents($absPath);
		$obj = Injector::inst()->create('YamlFixture', $string);
		$this->assertEquals($string, $obj->getFixtureString());
		$this->assertNull($obj->getFixtureFile());
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testFailsWithInvalidFixturePath() {
		$invalidPath = FRAMEWORK_DIR . '/tests/testing/invalid.yml';
		$obj = Injector::inst()->create('YamlFixture', $invalidPath);
	}

	public function testSQLInsert() {
		$factory = new FixtureFactory();
		$relPath = FRAMEWORK_DIR . '/tests/testing/YamlFixtureTest.yml';
		$fixture = Injector::inst()->create('YamlFixture', $relPath);
		$fixture->writeInto($factory);

		$this->assertGreaterThan(0, $factory->getId("YamlFixtureTest_DataObject", "testobject1"));
		$object1 = DataObject::get_by_id(
			"YamlFixtureTest_DataObject",
			$factory->getId("YamlFixtureTest_DataObject", "testobject1")
		);
		$this->assertTrue(
			$object1->ManyManyRelation()->Count() == 2,
			"Should be two items in this relationship"
		);
		$this->assertGreaterThan(0, $factory->getId("YamlFixtureTest_DataObject", "testobject2"));
		$object2 = DataObject::get_by_id(
			"YamlFixtureTest_DataObject",
			$factory->getId("YamlFixtureTest_DataObject", "testobject2")
		);
		$this->assertTrue(
			$object2->ManyManyRelation()->Count() == 1,
			"Should be one item in this relationship"
		);
	}

	public function testWriteInto() {
		$factory = Injector::inst()->create('FixtureFactory');

		$relPath = FRAMEWORK_DIR . '/tests/testing/YamlFixtureTest.yml';
		$fixture = Injector::inst()->create('YamlFixture', $relPath);
		$fixture->writeInto($factory);

		$this->assertGreaterThan(0, $factory->getId("YamlFixtureTest_DataObject", "testobject1"));
	}
}

class YamlFixtureTest_DataObject extends DataObject implements TestOnly {
	private static $db = array(
		"Name" => "Varchar"
	);
	private static $many_many = array(
		"ManyManyRelation" => "YamlFixtureTest_DataObjectRelation"
	);
}

class YamlFixtureTest_DataObjectRelation extends DataObject implements TestOnly {
	private static $db = array(
		"Name" => "Varchar"
	);
	private static $belongs_many_many = array(
		"TestParent" => "YamlFixtureTest_DataObject"
	);
}
