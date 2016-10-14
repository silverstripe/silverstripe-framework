<?php

namespace SilverStripe\Core\Tests;

use SilverStripe\Core\Object;
use SilverStripe\Core\Tests\ClassInfoTest\BaseClass;
use SilverStripe\Core\Tests\ClassInfoTest\BaseDataClass;
use SilverStripe\Core\Tests\ClassInfoTest\ChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\GrandChildClass;
use SilverStripe\Core\Tests\ClassInfoTest\HasFields;
use SilverStripe\Core\Tests\ClassInfoTest\NoFields;
use SilverStripe\Core\Tests\ClassInfoTest\WithCustomTable;
use SilverStripe\Core\Tests\ClassInfoTest\WithRelation;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

class ClassInfoTest extends SapphireTest {

	protected $extraDataObjects = array(
		BaseClass::class,
		BaseDataClass::class,
		ChildClass::class,
		GrandChildClass::class,
		HasFields::class,
		NoFields::class,
		WithCustomTable::class,
		WithRelation::class,
	);

	public function setUp() {
		parent::setUp();
		ClassInfo::reset_db_cache();
	}

	public function testExists() {
		$this->assertTrue(ClassInfo::exists('SilverStripe\\Core\\Object'));
		$this->assertTrue(ClassInfo::exists('SilverStripe\\Core\\object'));
		$this->assertTrue(ClassInfo::exists('SilverStripe\\Core\\Tests\\ClassInfoTest'));
		$this->assertTrue(ClassInfo::exists('SilverStripe\\Core\\Tests\\CLASSINFOTEST'));
		$this->assertTrue(ClassInfo::exists('stdClass'));
		$this->assertTrue(ClassInfo::exists('stdCLASS'));
		$this->assertFalse(ClassInfo::exists('SomeNonExistantClass'));
	}

	public function testSubclassesFor() {
		$this->assertEquals(
			array(
				BaseClass::class => BaseClass::class,
				ChildClass::class => ChildClass::class,
				GrandChildClass::class => GrandChildClass::class
			),
			ClassInfo::subclassesFor(BaseClass::class),
			'ClassInfo::subclassesFor() returns only direct subclasses and doesnt include base class'
		);
		ClassInfo::reset_db_cache();
		$this->assertEquals(
			array(
				BaseClass::class => BaseClass::class,
				ChildClass::class => ChildClass::class,
				GrandChildClass::class => GrandChildClass::class
			),
			ClassInfo::subclassesFor('silverstripe\\core\\tests\\classinfotest\\baseclass'),
			'ClassInfo::subclassesFor() is acting in a case sensitive way when it should not'
		);
	}

	public function testClassName()
	{
		$this->assertEquals(
			ClassInfoTest::class,
			ClassInfo::class_name($this)
		);
		$this->assertEquals(
			ClassInfoTest::class,
			ClassInfo::class_name('SilverStripe\\Core\\Tests\\ClassInfoTest')
		);
		$this->assertEquals(
			ClassInfoTest::class,
			ClassInfo::class_name('SilverStripe\\Core\\TESTS\\CLaSsInfOTEsT')
		);
	}

	public function testNonClassName() {
		$this->setExpectedException('ReflectionException', 'Class IAmAClassThatDoesNotExist does not exist');
		$this->assertEquals('IAmAClassThatDoesNotExist', ClassInfo::class_name('IAmAClassThatDoesNotExist'));
	}

	public function testClassesForFolder() {
		//$baseFolder = Director::baseFolder() . '/' . FRAMEWORK_DIR . '/tests/_ClassInfoTest';
		//$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);

		$classes = ClassInfo::classes_for_folder(ltrim(FRAMEWORK_DIR . '/tests', '/'));
		$this->assertContains(
			'silverstripe\\core\\tests\\classinfotest',
			$classes,
			'ClassInfo::classes_for_folder() returns classes matching the filename'
		);
		$this->assertContains(
			'silverstripe\\core\\tests\\classinfotest\\baseclass',
			$classes,
			'ClassInfo::classes_for_folder() returns additional classes not matching the filename'
		);
	}

	/**
	 * @covers \SilverStripe\Core\ClassInfo::ancestry()
	 */
	public function testAncestry() {
		$ancestry = ClassInfo::ancestry(ChildClass::class);
		$expect = ArrayLib::valuekey(array(
			Object::class,
			ViewableData::class,
			DataObject::class,
			BaseClass::class,
			ChildClass::class,
		));
		$this->assertEquals($expect, $ancestry);

		ClassInfo::reset_db_cache();
		$this->assertEquals(
			$expect,
			ClassInfo::ancestry('silverstripe\\core\\tests\\classINFOtest\\Childclass')
		);

		ClassInfo::reset_db_cache();
		$ancestry = ClassInfo::ancestry(ChildClass::class, true);
		$this->assertEquals(array(BaseClass::class => BaseClass::class), $ancestry,
			'$tablesOnly option excludes memory-only inheritance classes'
		);
	}

	/**
	 * @covers \SilverStripe\Core\ClassInfo::dataClassesFor()
	 */
	public function testDataClassesFor() {
		$expect = array(
			BaseDataClass::class => BaseDataClass::class,
			HasFields::class     => HasFields::class,
			WithRelation::class => WithRelation::class,
			WithCustomTable::class => WithCustomTable::class,
		);

		$classes = array(
			BaseDataClass::class,
			NoFields::class,
			HasFields::class,
		);

		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[0]));
		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor(strtoupper($classes[0])));
		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[1]));

		$expect = array(
			BaseDataClass::class => BaseDataClass::class,
			HasFields::class     => HasFields::class,
		);

		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor($classes[2]));
		ClassInfo::reset_db_cache();
		$this->assertEquals($expect, ClassInfo::dataClassesFor(strtolower($classes[2])));
	}

}
