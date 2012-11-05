<?php
/**
 * @package framework
 * @subpackage tests
 */

class FulltextSearchableTest extends SapphireTest {

	protected static $build_db_each_test = false;

	public function setUp() {
		parent::setUp();
		
		$this->orig['File_searchable'] = Object::has_extension('File', 'FulltextSearchable');
		
		// TODO This shouldn't need all arguments included
		Object::remove_extension('File', 'FulltextSearchable(\'"Filename","Title","Content"\')');
	}
	
	public function tearDown() {
		// TODO This shouldn't need all arguments included
		if($this->orig['File_searchable']) {
			Object::add_extension('File', 'FulltextSearchable(\'"Filename","Title","Content"\')');
		}
		
		parent::tearDown();
	}
	
	public function testEnable() {
		FulltextSearchable::enable();
		$this->assertTrue(Object::has_extension('File', 'FulltextSearchable'));
	}
	
	public function testEnableWithCustomClasses() {
		FulltextSearchable::enable(array('File'));
		$this->assertTrue(Object::has_extension('File', 'FulltextSearchable'));

		// TODO This shouldn't need all arguments included
		Object::remove_extension('File', 'FulltextSearchable(\'"Filename","Title","Content"\')');
		
		$this->assertFalse(Object::has_extension('File', 'FulltextSearchable'));
	}
	
}
