<?php
class DataObjectDecoratorTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/DataObjectDecoratorTest.yml';
	
	function testOneToManyAssociationWithDecorator() {
		// Fails in RestfulServerTest
		// Error: Object::__call() Method 'RelatedObjects' not found in class 'RestfulServerTest_Comment' 
		$contact = new DataObjectDecoratorTest_Member();
		$contact->Website = "http://www.example.com";
		
		$object = new DataObjectDecoratorTest_RelatedObject();
		$object->FieldOne = "Lorem ipsum dolor";
		$object->FieldTwo = "Random notes";
		
		// The following code doesn't currently work:
		// $contact->RelatedObjects()->add($object);
		// $contact->write();
		
		// Instead we have to do the following
		$contact->write();
		$object->ContactID = $contact->ID;
		$object->write();
		
		unset($contact);
		
		$contact = DataObject::get_one("DataObjectDecoratorTest_Member", "Website='http://www.example.com'");
		
		$this->assertType('DataObjectDecoratorTest_RelatedObject', $contact->RelatedObjects()->First());
		$this->assertEquals("Lorem ipsum dolor", $contact->RelatedObjects()->First()->FieldOne);
		$this->assertEquals("Random notes", $contact->RelatedObjects()->First()->FieldTwo);
		$contact->delete();
	}
	
	function testPermissionDecoration() {
		// testing behaviour in isolation, too many sideeffects and other checks
		// in SiteTree->can*() methods to test one single feature reliably with them

		$obj = $this->objFromFixture('DataObjectDecoratorTest_MyObject', 'object1');
		$websiteuser = $this->objFromFixture('Member', 'websiteuser');
		$admin = $this->objFromFixture('Member', 'admin');
		
		$this->assertFalse(
			$obj->canOne($websiteuser),
			'Both decorators return true, but original method returns false'
		);

		$this->assertFalse(
			$obj->canTwo($websiteuser),
			'One decorator returns false, original returns true, but decorator takes precedence'
		);
		
		$this->assertTrue(
			$obj->canThree($admin),
			'Undefined decorator methods returning NULL dont influence the original method'
		);

	}
	
	/**
	 * Test that DataObject::dbObject() works for fields applied by a decorator
	 */
	function testDbObjectOnDecoratedFields() {
		$member = $this->objFromFixture('DataObjectDecoratorTest_Member', 'member1');
		$this->assertNotNull($member->dbObject('Website'));
		$this->assertType('Text', $member->dbObject('Website'));
	}	
}

class DataObjectDecoratorTest_Member extends DataObject implements TestOnly {
	
	static $db = array(
		"Name" => "Text",
		"Email" => "Text"
	);
	
}

class DataObjectDecoratorTest_ContactRole extends DataObjectDecorator implements TestOnly {
	
	function extraStatics() {
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
		"FieldTwo" => "Text"
	);
	
	static $has_one = array(
		"Contact" => "DataObjectDecoratorTest_Member"
	);
	
}

DataObject::add_extension('DataObjectDecoratorTest_Member', 'DataObjectDecoratorTest_ContactRole');

class DataObjectDecoratorTest_MyObject extends DataObject implements TestOnly {
	
	static $db = array(
		'Title' => 'Varchar', 
	);
	
	function canOne($member = null) {
		// decorated access checks
		$results = $this->extend('canOne', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		return false;
	}
	
	function canTwo($member = null) {
		// decorated access checks
		$results = $this->extend('canTwo', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		return true;
	}
	
	function canThree($member = null) {
		// decorated access checks
		$results = $this->extend('canThree', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		return true;
	}
}

class DataObjectDecoratorTest_Ext1 extends DataObjectDecorator implements TestOnly {
	
	function canOne($member = null) {
		return true;
	}
	
	function canTwo($member = null) {
		return false;
	}
	
	function canThree($member = null) {
	}
	
}

class DataObjectDecoratorTest_Ext2 extends DataObjectDecorator implements TestOnly {
	
	function canOne($member = null) {
		return true;
	}
	
	function canTwo($member = null) {
		return true;
	}
	
	function canThree($member = null) {
	}
	
}

DataObject::add_extension('DataObjectDecoratorTest_MyObject', 'DataObjectDecoratorTest_Ext1');
DataObject::add_extension('DataObjectDecoratorTest_MyObject', 'DataObjectDecoratorTest_Ext2');
?>
