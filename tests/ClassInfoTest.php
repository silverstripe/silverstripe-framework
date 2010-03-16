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
	
}

class ClassInfoTest_BaseClass {
	
}

class ClassInfoTest_ChildClass extends ClassInfoTest_BaseClass {
	
}

class ClassInfoTest_GrandChildClass extends ClassInfoTest_ChildClass {
	
}
?>