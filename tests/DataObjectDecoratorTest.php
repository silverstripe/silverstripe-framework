<?php

class DataObjectDecoratorTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/DataObjectTest.yml';
	
	function testOneToManyAssociationWithDecorator() {
		$contact = new DataObjectDecoratorTest_Member();
		$contact->Website = "http://www.example.com";
		$object = new DataObjectDecoratorTest_RelatedObject();
		$object->FieldOne = "Lorem ipsum dolor";
		$object->FieldTwo = "Random notes";
		$contact->RelatedObjects()->add($object);
		$contact->write();
		unset($contact);
		
		$contact = DataObject::get_one("DataObjectDecoratorTest_Member", "Website='http://www.example.com'");
		$this->assertType('DataObjectDecoratorTest_RelatedObject', $contact->RelatedObjects()->First());
		$this->assertEquals("Lorem ipsum dolor", $contact->RelatedObjects()->First()->FieldOne);
		$this->assertEquals("Random notes", $contact->RelatedObjects()->First()->FieldTwo);
		$contact->delete();
	}
	
}

class DataObjectDecoratorTest_Member extends DataObject implements TestOnly {
	
	static $db = array(
		"Name" => "Text",
		"Email" => "Text"
	);
	
}

class DataObjectDecoratorTest_ContactRole extends DataObjectDecorator implements TestOnly {
	
	function extraDBFields() {
		return array(
			'db' => array(
				'Website' => 'Text',
				'Phone' => 'Varchar(255)',
			),
			'has_many' => array(
				'RelatedObjects' => 'DataObjectDecoratorTest_RelatedObject'
			)
		);
	}
}

class DataObjectDecoratorTest_RelatedObject extends DataObject implements TestOnly {
	
	static $db = array(
		"FieldOne" => "Text",
		"FieldOne" => "Text"
	);
	
	static $has_one = array(
		"Contact" => "Member"
	);
	
}

DataObject::add_extension('DataObjectDecoratorTest_Member', 'DataObjectDecoratorTest_ContactRole');

?>