<?php
/**
 * @package sapphire
 * @subpackage tests
 */

class FulltextSearchableTest extends SapphireTest {

	function setUp() {
		parent::setUp();
		
		$this->orig['File_searchable'] = Object::has_extension('File', 'FulltextSearchable');
		$this->orig['SiteTree_searchable'] = Object::has_extension('SiteTree', 'FulltextSearchable');
		
		// TODO This shouldn't need all arguments included
		Object::remove_extension('File', 'FulltextSearchable(\'Filename,Title,Content\')');
		Object::remove_extension('SiteTree', 'FulltextSearchable(\'Title,MenuTitle,Content,MetaTitle,MetaDescription,MetaKeywords\')');
	}
	
	function tearDown() {
		// TODO This shouldn't need all arguments included
		if($this->orig['File_searchable']) Object::add_extension('File', 'FulltextSearchable(\'Filename,Title,Content\')');
		if($this->orig['SiteTree_searchable']) Object::add_extension('SiteTree', 'FulltextSearchable(\'Title,MenuTitle,Content,MetaTitle,MetaDescription,MetaKeywords\')');
		
		parent::tearDown();
	}
	
	function testEnable() {		
		FulltextSearchable::enable();
		$this->assertTrue(Object::has_extension('SiteTree', 'FulltextSearchable'));
		$this->assertTrue(Object::has_extension('File', 'FulltextSearchable'));
	}
	
	function testEnableWithCustomClasses() {
		FulltextSearchable::enable(array('SiteTree'));
		$this->assertTrue(Object::has_extension('SiteTree', 'FulltextSearchable'));

		// TODO This shouldn't need all arguments included
		Object::remove_extension('File', 'FulltextSearchable(\'Filename,Title,Content\')');
		
		$this->assertFalse(Object::has_extension('File', 'FulltextSearchable'));
	}
	
}