<?php

/**
 * @package framework
 * @subpackage tests
 */
class ClassInfoTest extends SapphireTest {

	protected $extraDataObjects = array(
		'ClassInfoTest_BaseClass',
		'ClassInfoTest_ChildClass',
		'ClassInfoTest_GrandChildClass',
		'ClassInfoTest_BaseDataClass',
		'ClassInfoTest_NoFields',
	);

	public function testExists() {
		$this->assertTrue(ClassInfo::exists('Object'));
		$this->assertTrue(ClassInfo::exists('ClassInfoTest'));
		$this->assertTrue(ClassInfo::exists('stdClass'));
	}

	public function testSubclassesFor() {
		$this->assertEquals(
			ClassInfo::subclassesFor('ClassInfoTest_BaseClass'),
			array(
				'ClassInfoTest_BaseClass' => 'ClassInfoTest_BaseClass',
				'ClassInfoTest_ChildClass' => 'ClassInfoTest_ChildClass',
				'ClassInfoTest_GrandChildClass' => 'ClassInfoTest_GrandChildClass'
			),
			'ClassInfo::subclassesFor() returns only direct subclasses and doesnt include base class'
		);
	}

	public function testClassesForFolder() {
		//$baseFolder = Director::baseFolder() . '/' . FRAMEWORK_DIR . '/tests/_ClassInfoTest';
		//$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);

		$classes = ClassInfo::classes_for_folder(FRAMEWORK_DIR . '/tests');
		$this->assertContains(
			'classinfotest',
			$classes,
			'ClassInfo::classes_for_folder() returns classes matching the filename'
		);
		// $this->assertContains(
		// 			'ClassInfoTest_BaseClass',
		// 			$classes,
		// 			'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
		// 		);
	}

	/**
	 * @covers ClassInfo::baseDataClass()
	 */
	public function testBaseDataClass() {
		$this->assertEquals('ClassInfoTest_BaseClass', ClassInfo::baseDataClass('ClassInfoTest_BaseClass'));
		$this->assertEquals('ClassInfoTest_BaseClass', ClassInfo::baseDataClass('ClassInfoTest_ChildClass'));
		$this->assertEquals('ClassInfoTest_BaseClass', ClassInfo::baseDataClass('ClassInfoTest_GrandChildClass'));

		$this->setExpectedException('InvalidArgumentException');
		ClassInfo::baseDataClass('DataObject');
	}

	/**
	 * @covers ClassInfo::ancestry()
	 */
	public function testAncestry() {
		$ancestry = ClassInfo::ancestry('ClassInfoTest_ChildClass');
		$expect = ArrayLib::valuekey(array(
			'Object',
			'ViewableData',
			'DataObject',
			'ClassInfoTest_BaseClass',
			'ClassInfoTest_ChildClass',
		));
		$this->assertEquals($expect, $ancestry);

		$ancestry = ClassInfo::ancestry('ClassInfoTest_ChildClass', true);
		$this->assertEquals(array('ClassInfoTest_BaseClass' => 'ClassInfoTest_BaseClass'), $ancestry,
			'$tablesOnly option excludes memory-only inheritance classes'
		);
	}

	/**
	 * @covers ClassInfo::dataClassesFor()
	 */
	public function testDataClassesFor() {
		$expect = array(
			'ClassInfoTest_BaseDataClass' => 'ClassInfoTest_BaseDataClass',
			'ClassInfoTest_HasFields'     => 'ClassInfoTest_HasFields',
			'ClassInfoTest_WithRelation' => 'ClassInfoTest_WithRelation'
		);

		$classes = array(
			'ClassInfoTest_BaseDataClass',
			'ClassInfoTest_NoFields',
			'ClassInfoTest_HasFields',
		);


		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[0]));
		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[1]));
	
		$expect = array(
			'ClassInfoTest_BaseDataClass' => 'ClassInfoTest_BaseDataClass',
			'ClassInfoTest_HasFields'     => 'ClassInfoTest_HasFields',
		);

		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[2]));
	}

	public function testTableForObjectField() {
		$this->assertEquals('ClassInfoTest_WithRelation',
			ClassInfo::table_for_object_field('ClassInfoTest_WithRelation', 'RelationID')
		);

		$this->assertEquals('ClassInfoTest_BaseDataClass', 
			ClassInfo::table_for_object_field('ClassInfoTest_BaseDataClass', 'Title')
		);

		$this->assertEquals('ClassInfoTest_BaseDataClass', 
			ClassInfo::table_for_object_field('ClassInfoTest_HasFields', 'Title')
		);

		$this->assertEquals('ClassInfoTest_BaseDataClass', 
			ClassInfo::table_for_object_field('ClassInfoTest_NoFields', 'Title')
		);

		$this->assertEquals('ClassInfoTest_HasFields', 
			ClassInfo::table_for_object_field('ClassInfoTest_HasFields', 'Description')
		);

		// existing behaviour fallback to DataObject? Should be null.
		$this->assertEquals('DataObject',
			ClassInfo::table_for_object_field('ClassInfoTest_BaseClass', 'Nonexist')
		);

		$this->assertNull(
			ClassInfo::table_for_object_field('SomeFakeClassHere', 'Title')
		);

		$this->assertNull(
			ClassInfo::table_for_object_field('Object', 'Title')
		);

		$this->assertNull(
			ClassInfo::table_for_object_field(null, null)
		);
	}
}

/**
 * @package framework
 * @subpackage tests
 */

class ClassInfoTest_BaseClass extends DataObject implements TestOnly {

}

/**
 * @package framework
 * @subpackage tests
 */

class ClassInfoTest_ChildClass extends ClassInfoTest_BaseClass {

}

/**
 * @package framework
 * @subpackage tests
 */

class ClassInfoTest_GrandChildClass extends ClassInfoTest_ChildClass {

}

/**
 * @package framework
 * @subpackage tests
 */

class ClassInfoTest_BaseDataClass extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar'
	);
}

/**
 * @package framework
 * @subpackage tests
 */

class ClassInfoTest_NoFields extends ClassInfoTest_BaseDataClass {

}

/**
 * @package framework
 * @subpackage tests
 */

class ClassInfoTest_HasFields extends ClassInfoTest_NoFields {

	private static $db = array(
		'Description' => 'Varchar'
	);
}

/**
 * @package framework
 * @subpackage tests
 */

class ClassInfoTest_WithRelation extends ClassInfoTest_NoFields {

	private static $has_one = array(
		'Relation' => 'ClassInfoTest_HasFields'
	);
}
