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
	
}

class ClassInfoTest_BaseClass {
	
}

class ClassInfoTest_ChildClass extends ClassInfoTest_BaseClass {
	
}

class ClassInfoTest_GrandChildClass extends ClassInfoTest_ChildClass {
	
}
?>