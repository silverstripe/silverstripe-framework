<?php
/**
 * @author Ingo Schommer (ingo at silverstripe dot com)
 * @todo There's currently no way to save outside of assets/ folder
 * 
 * @package sapphire
 * @subpackage tests
 */
class FolderTest extends SapphireTest {
	
	function tearDown() {
		$testPath = ASSETS_PATH . '/FolderTest';
		if(file_exists($testPath)) Filesystem::removeFolder($testPath);
		
		parent::tearDown();
	}
	
	function testFindOrMake() {
		$path = '/FolderTest/testFindOrMake/';
		$folder = Folder::findOrMake($path);
		$this->assertEquals(ASSETS_DIR . $path,$folder->getRelativePath(),
			'Nested path information is correctly saved to database (with trailing slash)'
		);

		$this->assertTrue(file_exists(ASSETS_PATH . $path), 'File');
		$parentFolder = DataObject::get_one('Folder', '"Name" = \'FolderTest\'');
		$this->assertNotNull($parentFolder);
		$this->assertEquals($parentFolder->ID, $folder->ParentID);
		
		$path = '/FolderTest/testFindOrMake';
		$folder = Folder::findOrMake($path);
		$this->assertEquals(ASSETS_DIR . $path . '/',$folder->getRelativePath(),
			'Path information is correctly saved to database (without trailing slash)'
		);
	}
}