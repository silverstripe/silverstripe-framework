<?php
/**
 * @package framework
 * @subpackage tests
 */

class FulltextSearchableTest extends SapphireTest {

	public function setUp() {
		parent::setUp();
		
		$this->orig['File_searchable'] = File::has_extension('FulltextSearchable');
		
		// TODO This shouldn't need all arguments included
		File::remove_extension('FulltextSearchable(\'"Filename","Title","Content"\')');
	}
	
	public function tearDown() {
		// TODO This shouldn't need all arguments included
		if($this->orig['File_searchable']) {
			File::add_extension('FulltextSearchable(\'"Filename","Title","Content"\')');
		}
		
		parent::tearDown();
	}
	
	public function testEnable() {
		FulltextSearchable::enable();
		$this->assertTrue(File::has_extension('FulltextSearchable'));
	}
	
	public function testEnableWithCustomClasses() {
		FulltextSearchable::enable(array('File'));
		$this->assertTrue(File::has_extension('FulltextSearchable'));

		// TODO This shouldn't need all arguments included
		File::remove_extension('FulltextSearchable(\'"Filename","Title","Content"\')');
		
		$this->assertFalse(File::has_extension('FulltextSearchable'));
	}
	
}
