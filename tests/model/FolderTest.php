<?php
class FolderTest extends SapphireTest {
	
	protected $orig = array();
	
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
?>