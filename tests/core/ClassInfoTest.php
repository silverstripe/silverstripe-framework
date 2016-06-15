<?php

use SilverStripe\ORM\DataObject;

/**
 * @package framework
 * @subpackage tests
 */
class ClassInfoTest extends SapphireTest {

	protected $extraDataObjects = array(
		'ClassInfoTest_BaseClass',
		'ClassInfoTest_BaseDataClass',
		'ClassInfoTest_ChildClass',
		'ClassInfoTest_GrandChildClass',
		'ClassInfoTest_HasFields',
		'ClassInfoTest_NoFields',
		'ClassInfoTest_WithCustomTable',
		'ClassInfoTest_WithRelation',
	);

	public function setUp() {
		parent::setUp();
		ClassInfo::reset_db_cache();
	}

	public function testExists() {
		$this->assertTrue(ClassInfo::exists('Object'));
		$this->assertTrue(ClassInfo::exists('object'));
		$this->assertTrue(ClassInfo::exists('ClassInfoTest'));
		$this->assertTrue(ClassInfo::exists('CLASSINFOTEST'));
		$this->assertTrue(ClassInfo::exists('stdClass'));
		$this->assertTrue(ClassInfo::exists('stdCLASS'));
		$this->assertFalse(ClassInfo::exists('SomeNonExistantClass'));
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
		ClassInfo::reset_db_cache();
		$this->assertEquals(
			ClassInfo::subclassesFor('classinfotest_baseclass'),
			array(
				'ClassInfoTest_BaseClass' => 'ClassInfoTest_BaseClass',
				'ClassInfoTest_ChildClass' => 'ClassInfoTest_ChildClass',
				'ClassInfoTest_GrandChildClass' => 'ClassInfoTest_GrandChildClass'
			),
			'ClassInfo::subclassesFor() is acting in a case sensitive way when it should not'
		);
	}

	public function testClassName()
	{
		$this->assertEquals('ClassInfoTest', ClassInfo::class_name($this));
		$this->assertEquals('ClassInfoTest', ClassInfo::class_name('ClassInfoTest'));
		$this->assertEquals('ClassInfoTest', ClassInfo::class_name('CLaSsInfOTEsT'));
	}

	public function testNonClassName() {
		$this->setExpectedException('ReflectionException', 'Class IAmAClassThatDoesNotExist does not exist');
		$this->assertEquals('IAmAClassThatDoesNotExist', ClassInfo::class_name('IAmAClassThatDoesNotExist'));
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
		$this->assertContains(
			'classinfotest_baseclass',
			$classes,
			'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
		);
	}

	/**
	 * @covers ClassInfo::ancestry()
	 */
	public function testAncestry() {
		$ancestry = ClassInfo::ancestry('ClassInfoTest_ChildClass');
		$expect = ArrayLib::valuekey(array(
			'Object',
			'ViewableData',
			'SilverStripe\\ORM\\DataObject',
			'ClassInfoTest_BaseClass',
			'ClassInfoTest_ChildClass',
		));
		$this->assertEquals($expect, $ancestry);

		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::ancestry('classINFOTest_Childclass'));

		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::ancestry('classINFOTest_Childclass'));

		ClassInfo::reset_db_cache();
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
			'ClassInfoTest_WithRelation' => 'ClassInfoTest_WithRelation',
			'ClassInfoTest_WithCustomTable' => 'ClassInfoTest_WithCustomTable',
		);

		$classes = array(
			'ClassInfoTest_BaseDataClass',
			'ClassInfoTest_NoFields',
			'ClassInfoTest_HasFields',
		);

		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[0]));
		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor(strtoupper($classes[0])));
		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[1]));

		$expect = array(
			'ClassInfoTest_BaseDataClass' => 'ClassInfoTest_BaseDataClass',
			'ClassInfoTest_HasFields'     => 'ClassInfoTest_HasFields',
		);

		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[2]));
		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor(strtolower($classes[2])));
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

class ClassInfoTest_WithCustomTable extends ClassInfoTest_NoFields {
	private static $table_name = 'CITWithCustomTable';
	private static $db = array(
		'Description' => 'Text'
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
