<?php

class DBClassNameTest extends SapphireTest {
	
	protected $extraDataObjects = array(
		'DBClassNameTest_Object',
		'DBClassNameTest_ObjectSubClass',
		'DBClassNameTest_ObjectSubSubClass',
		'DBClassNameTest_OtherClass'
	);

	/**
	 * Test that custom subclasses generate the right hierarchy
	 */
	public function testEnumList() {
		// Object 1 fields
		$object = new DBClassNameTest_Object();
		$defaultClass = $object->dbObject('DefaultClass');
		$anyClass = $object->dbObject('AnyClass');
		$childClass = $object->dbObject('ChildClass');
		$leafClass = $object->dbObject('LeafClass');

		// Object 2 fields
		$object2 = new DBClassNameTest_ObjectSubClass();
		$midDefault = $object2->dbObject('MidClassDefault');
		$midClass = $object2->dbObject('MidClass');

		// Default fields always default to children of base class (even if put in a subclass)
		$mainSubclasses = array (
			'DBClassNameTest_Object' => 'DBClassNameTest_Object',
			'DBClassNameTest_ObjectSubClass' => 'DBClassNameTest_ObjectSubClass',
			'DBClassNameTest_ObjectSubSubClass' => 'DBClassNameTest_ObjectSubSubClass',
		);
		$this->assertEquals($mainSubclasses, $defaultClass->getEnumObsolete());
		$this->assertEquals($mainSubclasses, $midDefault->getEnumObsolete());

		// Unbound classes detect any
		$anyClasses = $anyClass->getEnumObsolete();
		$this->assertContains('DBClassNameTest_OtherClass', $anyClasses);
		$this->assertContains('DBClassNameTest_Object', $anyClasses);
		$this->assertContains('DBClassNameTest_ObjectSubClass', $anyClasses);
		$this->assertContains('DBClassNameTest_ObjectSubSubClass', $anyClasses);

		// Classes bound to the middle of a tree
		$midSubClasses = $mainSubclasses = array (
			'DBClassNameTest_ObjectSubClass' => 'DBClassNameTest_ObjectSubClass',
			'DBClassNameTest_ObjectSubSubClass' => 'DBClassNameTest_ObjectSubSubClass',
		);
		$this->assertEquals($midSubClasses, $childClass->getEnumObsolete());
		$this->assertEquals($midSubClasses, $midClass->getEnumObsolete());

		// Leaf clasess contain only exactly one node
		$this->assertEquals(
			array('DBClassNameTest_ObjectSubSubClass' => 'DBClassNameTest_ObjectSubSubClass',),
			$leafClass->getEnumObsolete()
		);
	}

	/**
	 * Test that the base class can be detected under various circumstances
	 */
	public function testBaseClassDetection() {
		// Explicit DataObject
		$field1 = new DBClassName('MyClass', 'DataObject');
		$this->assertEquals('DataObject', $field1->getBaseClass());

		// Explicit base class
		$field2 = new DBClassName('MyClass', 'DBClassNameTest_Object');
		$this->assertEquals('DBClassNameTest_Object', $field2->getBaseClass());

		// Explicit subclass
		$field3 = new DBClassName('MyClass');
		$field3->setValue(null, new DBClassNameTest_ObjectSubClass());
		$this->assertEquals('DBClassNameTest_Object', $field3->getBaseClass());

		// Implicit table
		$field4 = new DBClassName('MyClass');
		$field4->setTable('DBClassNameTest_ObjectSubClass_versions');
		$this->assertEquals('DBClassNameTest_Object', $field4->getBaseClass());

		// Missing
		$field5 = new DBClassName('MyClass');
		$this->assertEquals('DataObject', $field5->getBaseClass());

		// Invalid class
		$field6 = new DBClassName('MyClass');
		$field6->setTable('InvalidTable');
		$this->assertEquals('DataObject', $field6->getBaseClass());
	}
}

class DBClassNameTest_Object extends DataObject implements TestOnly {

	private static $extensions = array(
		'Versioned'
	);

	private static $db = array(
		'DefaultClass' => 'DBClassName',
		'AnyClass' => 'DBClassName("DataObject")',
		'ChildClass' => 'DBClassName("DBClassNameTest_ObjectSubClass")',
		'LeafClass' => 'DBClassName("DBClassNameTest_ObjectSubSubClass")'
	);
}

class DBClassNameTest_ObjectSubClass extends DBClassNameTest_Object {
	private static $db = array(
		'MidClassDefault' => 'DBClassName',
		'MidClass' => 'DBClassName("DBClassNameTest_ObjectSubclass")'
	);

}

class DBClassNameTest_ObjectSubSubClass extends DBClassNameTest_ObjectSubclass {
}

class DBClassNameTest_OtherClass extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar'
	);
}