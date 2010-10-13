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
	
	function testFilesystemFolderIsCreatedOnFirstWrite() {
		$parentFolder = new Folder();
		$parentFolder->Name = '__FolderTest';
		$parentFolder->write();
		$this->assertEquals(
			$parentFolder->getFullPath(),
			ASSETS_PATH . '/' . $parentFolder->Name . '/',
			'Folder record creates matching path on filesystem on first write'
		);
		$this->assertFileExists(
			$parentFolder->getFullPath(),
			'Folder record without ParentID creates a folder in the $base_dir on filesystem on first write'
		);
		
		$childFolder = new Folder();
		$childFolder->ParentID = $parentFolder->ID;
		$childFolder->Name = 'child';
		$childFolder->write();
		$this->assertEquals(
			$childFolder->getFullPath(),
			ASSETS_PATH . '/' . $parentFolder->Name . '/' . $childFolder->Name . '/',
			'Folder record creates matching path on filesystem on first write'
		);
		$this->assertFileExists(
			$childFolder->getFullPath(),
			'Folder record without ParentID creates a folder on filesystem on first write'
		);
	}
	
	function testFolderNameCantDuplicate() {
		$folder = new Folder();
		$folder->Name = 'myfolder';
		$folder->write();
		
		$folder2 = new Folder();
		$folder2->Name = 'myfolder';
		$folder2->write();
		$this->assertNotEquals(
			$folder->Name,
			$folder2->Name,
			'Folder write renames to avoid duplicates on filesystem'
		);
	}
}