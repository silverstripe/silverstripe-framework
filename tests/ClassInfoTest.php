<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ClassInfoTest extends SapphireTest {
	
	function testSubclassesFor() {
		$this->assertEquals(
			ClassInfo::subclassesFor('ClassInfoTest_BaseClass'),
			array(
				0 => 'ClassInfoTest_BaseClass',
				'ClassInfoTest_ChildClass' => 'ClassInfoTest_ChildClass',
				'ClassInfoTest_GrandChildClass' => 'ClassInfoTest_GrandChildClass'
			),
			'ClassInfo::subclassesFor() returns only direct subclasses and doesnt include base class'
		);
	}
	
	function testClassesForFolder() {
		//$baseFolder = Director::baseFolder() . '/' . SAPPHIRE_DIR . '/tests/_ClassInfoTest';
		//$manifestInfo = ManifestBuilder::get_manifest_info($baseFolder);
		
		$classes = ClassInfo::classes_for_folder('sapphire/tests');
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

		$this->setExpectedException('Exception');
		ClassInfo::baseDataClass('DataObject');
	}

}

class ClassInfoTest_BaseClass extends DataObject {
	
}

class ClassInfoTest_ChildClass extends ClassInfoTest_BaseClass {
	
}

class ClassInfoTest_GrandChildClass extends ClassInfoTest_ChildClass {
	
}