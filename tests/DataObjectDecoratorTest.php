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

		$contactID = $contact->ID;
		unset($contact);
		unset($object);
		
		$contact = DataObject::get_one("DataObjectDecoratorTest_Member", "Website = 'http://www.example.com'");
		$object = DataObject::get_one('DataObjectDecoratorTest_RelatedObject', "ContactID = {$contactID}");

		$this->assertNotNull($object, 'Related object not null');
		$this->assertType('DataObjectDecoratorTest_Member', $object->Contact(), 'Related contact is a member dataobject');
		$this->assertType('DataObjectDecoratorTest_Member', $object->getComponent('Contact'), 'getComponent does the same thing as Contact()');
		
		$this->assertType('DataObjectDecoratorTest_RelatedObject', $contact->RelatedObjects()->First());
		$this->assertEquals("Lorem ipsum dolor", $contact->RelatedObjects()->First()->FieldOne);
		$this->assertEquals("Random notes", $contact->RelatedObjects()->First()->FieldTwo);
		$contact->delete();
	}
	
	function testManyManyAssociationWithDecorator() {
		$parent = new DataObjectDecoratorTest_MyObject();
		$parent->Title = 'My Title';
		$parent->write();
		
		$this->assertEquals(0, $parent->Faves()->Count());
		
		$homepage = $this->objFromFixture('Page', 'home');
		$firstpage = $this->objFromFixture('Page', 'page1');

		$parent->Faves()->add($homepage->ID);
		$this->assertEquals(1, $parent->Faves()->Count());
		
		$parent->Faves()->add($firstpage->ID);
		$this->assertEquals(2, $parent->Faves()->Count());
		
		$parent->Faves()->remove($firstpage->ID);
		$this->assertEquals(1, $parent->Faves()->Count());
	}
	
	/**
	 * Test {@link Object::add_extension()} has loaded DataObjectDecorator statics correctly.
	 */
	function testAddExtensionLoadsStatics() {
		// Object::add_extension() will load DOD statics directly, so let's try adding a decorator on the fly
		Object::add_extension('DataObjectDecoratorTest_Player', 'DataObjectDecoratorTest_PlayerDecorator');
		
		// Now that we've just added the decorator, we need to rebuild the database
		$da = new DatabaseAdmin();
		$da->doBuild(true, false, true);
		
		// Create a test record with decorated fields, writing to the DB
		$player = new DataObjectDecoratorTest_Player();
		$player->setField('Name', 'Joe');
		$player->setField('DateBirth', '1990-5-10');
		$player->Address = '123 somewhere street';
		$player->write();
		
		unset($player);
		
		// Pull the record out of the DB and examine the decorated fields
		$player = DataObject::get_one('DataObjectDecoratorTest_Player', "Name = 'Joe'");
		$this->assertEquals($player->DateBirth, '1990-05-10');
		$this->assertEquals($player->Address, '123 somewhere street');
		$this->assertEquals($player->Status, 'Goalie');
	}
	
	/**
	 * Test that DataObject::$api_access can be set to true via a decorator
	 */
	function testApiAccessCanBeDecorated() {
		$this->assertTrue(Object::get_static('DataObjectDecoratorTest_Member', 'api_access'));
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

class DataObjectDecoratorTest_Player extends DataObject implements TestOnly {

	static $db = array(
		'Name' => 'Varchar'
	);
	
}

class DataObjectDecoratorTest_PlayerDecorator extends DataObjectDecorator implements TestOnly {
	
	function extraStatics() {
		return array(
			'db' => array(
				'Address' => 'Text',
				'DateBirth' => 'Date',
				'Status' => "Enum('Shooter,Goalie')"
			),
			'defaults' => array(
				'Status' => 'Goalie'
			)
		);
	}
	
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
			),
			'api_access' => true,
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

class DataObjectDecoratorTest_Faves extends DataObjectDecorator implements TestOnly {
	public function extraStatics() {
		return array(
			'many_many' => array(
				'Faves' => 'Page'
			)
		);
	}
}

DataObject::add_extension('DataObjectDecoratorTest_MyObject', 'DataObjectDecoratorTest_Ext1');
DataObject::add_extension('DataObjectDecoratorTest_MyObject', 'DataObjectDecoratorTest_Ext2');
DataObject::add_extension('DataObjectDecoratorTest_MyObject', 'DataObjectDecoratorTest_Faves');
?>
