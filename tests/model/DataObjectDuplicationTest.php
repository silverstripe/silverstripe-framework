<?php

class DataObjectDuplicationTest extends SapphireTest {
	
	protected $extraDataObjects = array(
		'DataObjectDuplicateTestClass1',
		'DataObjectDuplicateTestClass2',
		'DataObjectDuplicateTestClass3'
	);

	function testDuplicateManyManyClasses() {
		//create new test classes below
		$one = new DataObjectDuplicateTestClass1();
		$two = new DataObjectDuplicateTestClass2();
		$three = new DataObjectDuplicateTestClass3();

		//set some simple fields
		$text1 = "Test Text 1";
		$text2 = "Test Text 2";
		$text3 = "Test Text 3";
		$one->text = $text1;
		$two->text = $text2;
		$three->text = $text3;

		//write the to DB
		$one->write();
		$two->write();
		$three->write();

		//create relations
		$one->twos()->add($two);
		$one->threes()->add($three);

		$one = DataObject::get_by_id("DataObjectDuplicateTestClass1", $one->ID);
		$two = DataObject::get_by_id("DataObjectDuplicateTestClass2", $two->ID);
		$three = DataObject::get_by_id("DataObjectDuplicateTestClass3", $three->ID);

		//test duplication
		$oneCopy = $one->duplicate();
		$twoCopy = $two->duplicate();
		$threeCopy = $three->duplicate();

		$oneCopy = DataObject::get_by_id("DataObjectDuplicateTestClass1", $oneCopy->ID);
		$twoCopy = DataObject::get_by_id("DataObjectDuplicateTestClass2", $twoCopy->ID);
		$threeCopy = DataObject::get_by_id("DataObjectDuplicateTestClass3", $threeCopy->ID);

		$this->assertNotNull($oneCopy, "Copy of 1 exists");
		$this->assertNotNull($twoCopy, "Copy of 2 exists");
		$this->assertNotNull($threeCopy, "Copy of 3 exists");

		$this->assertEquals($text1, $oneCopy->text);
		$this->assertEquals($text2, $twoCopy->text);
		$this->assertEquals($text3, $threeCopy->text);

		$this->assertNotEquals($one->twos()->Count(), $oneCopy->twos()->Count(), "Many-to-one relation not copied (has_many)");
		$this->assertEquals($one->threes()->Count(), $oneCopy->threes()->Count(), "Object has the correct number of relations");
		$this->assertEquals($three->ones()->Count(), $threeCopy->ones()->Count(), "Object has the correct number of relations");

		$this->assertEquals($one->ID, $twoCopy->one()->ID, "Match between relation of copy and the original");
		$this->assertEquals(0, $oneCopy->twos()->Count(), "Many-to-one relation not copied (has_many)");
		$this->assertEquals($three->ID, $oneCopy->threes()->First()->ID, "Match between relation of copy and the original");
		$this->assertEquals($one->ID, $threeCopy->ones()->First()->ID, "Match between relation of copy and the original");
	}

}


class DataObjectDuplicateTestClass1 extends DataObject implements TestOnly {

	static $db = array(
		'text' => 'Varchar'
	);

	static $has_many = array(
		'twos' => 'DataObjectDuplicateTestClass2'
	);

	static $many_many = array(
		'threes' => 'DataObjectDuplicateTestClass3'
	);
}

class DataObjectDuplicateTestClass2 extends DataObject implements TestOnly {

	static $db = array(
		'text' => 'Varchar'
	);

	static $has_one = array(
		'one' => 'DataObjectDuplicateTestClass1'
	);

}

class DataObjectDuplicateTestClass3 extends DataObject implements TestOnly {

	static $db = array(
		'text' => 'Varchar'
	);

	static $belongs_many_many = array(
		'ones' => 'DataObjectDuplicateTestClass1'
	);
}


