<?php

class GridFieldFilterHeaderTest extends SapphireTest {

	protected $extraDataObjects = array(
		'GridFieldFilterHeaderTest_DataObject',
	);

	public function testColumnToFilterField() {
		$class = 'GridFieldFilterHeaderTest_DataObject';
		$header = new GridFieldFilterHeader();
		$method = new ReflectionMethod($header, 'columnToFilterField');
		$method->setAccessible(true);
		$this->assertEquals('Title', $method->invoke($header, $class,'Title.ATT'));
		$this->assertEquals('isTest', $method->invoke($header, $class, 'isTest.Nice'));
		$this->assertEquals('Self.isTest.Nice', $method->invoke($header, $class, 'Self.isTest.Nice'));
	}

}

class GridFieldFilterHeaderTest_DataObject extends DataObject implements TestOnly {

	private static $db = array(
		'Title' => 'Varchar',
	    'isTest' => 'Boolean',
	);

	private static $has_one = array(
		'Self' => 'GridFieldFilterHeaderTest_DataObject',
	);

}
