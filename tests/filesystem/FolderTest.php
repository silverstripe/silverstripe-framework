<?php
/**
 * @author Ingo Schommer (ingo at silverstripe dot com)
 * @todo There's currently no way to save outside of assets/ folder
 *
 * @package framework
 * @subpackage tests
 */
class FolderTest extends SapphireTest {

	protected static $fixture_file = 'FileTest.yml';

	public function testCreateFromNameAndParentIDSetsFilename() {
		$folder1 = $this->objFromFixture('Folder', 'folder1');
		$newFolder = new Folder();
		$newFolder->Name = 'CreateFromNameAndParentID';
		$newFolder->ParentID = $folder1->ID;
		$newFolder->write();

		$this->assertEquals($folder1->Filename . 'CreateFromNameAndParentID/', $newFolder->Filename);
	}

	public function testAllChildrenIncludesFolders() {
		$folder1 = $this->objFromFixture('Folder', 'folder1');
		$subfolder1 = $this->objFromFixture('Folder', 'folder1-subfolder1');
		$file1 = $this->objFromFixture('File', 'file1-folder1');

		$children = $folder1->allChildren();
		$this->assertEquals(2, $children->Count());
		$this->assertContains($subfolder1->ID, $children->column('ID'));
		$this->assertContains($file1->ID, $children->column('ID'));
	}

	public function testFindOrMake() {
		$path = '/FolderTest/testFindOrMake/';
		$folder = Folder::find_or_make($path);
		$this->assertEquals(ASSETS_DIR . $path,$folder->getRelativePath(),
			'Nested path information is correctly saved to database (with trailing slash)'
		);

		$this->assertTrue(file_exists(ASSETS_PATH . $path), 'File');
		$parentFolder = DataObject::get_one('Folder', array(
			'"File"."Name"' => 'FolderTest'
		));
		$this->assertNotNull($parentFolder);
		$this->assertEquals($parentFolder->ID, $folder->ParentID);

		$path = '/FolderTest/testFindOrMake'; // no trailing slash
		$folder = Folder::find_or_make($path);
		$this->assertEquals(ASSETS_DIR . $path . '/',$folder->getRelativePath(),
			'Path information is correctly saved to database (without trailing slash)'
		);

		$path = '/assets/'; // relative to "assets/" folder, should produce "assets/assets/"
		$folder = Folder::find_or_make($path);
		$this->assertEquals(ASSETS_DIR . $path,$folder->getRelativePath(),
			'A folder named "assets/" within "assets/" is allowed'
		);
	}

	/**
	 * @see FileTest->testSetNameChangesFilesystemOnWrite()
	 */
	public function testSetNameChangesFilesystemOnWrite() {
		$folder1 = $this->objFromFixture('Folder', 'folder1');
		$subfolder1 = $this->objFromFixture('Folder', 'folder1-subfolder1');
		$file1 = $this->objFromFixture('File', 'file1-folder1');
		$oldPathFolder1 = $folder1->getFullPath();
		$oldPathSubfolder1 = $subfolder1->getFullPath();
		$oldPathFile1 = $file1->getFullPath();

		// Before write()
		$folder1->Name = 'FileTest-folder1-renamed';
		$this->assertFileExists($oldPathFolder1, 'Old path is still present');
		$this->assertFileNotExists($folder1->getFullPath(),
			'New path is updated in memory, not written before write() is called');
		$this->assertFileExists($oldPathFile1, 'Old file is still present');
		// TODO setters currently can't update in-memory
		// $this->assertFileNotExists($file1->getFullPath(),
		// 'New path on contained files is updated in memory, not written before write() is called');
		// $this->assertFileNotExists($subfolder1->getFullPath(),
		// 'New path on subfolders is updated in memory, not written before write() is called');

		$folder1->write();

		// After write()

		// Reload state
		clearstatcache();
		DataObject::flush_and_destroy_cache();
		$folder1 = DataObject::get_by_id('Folder', $folder1->ID);
		$file1 = DataObject::get_by_id('File', $file1->ID);
		$subfolder1 = DataObject::get_by_id('Folder', $subfolder1->ID);

		$this->assertFileNotExists($oldPathFolder1, 'Old path is removed after write()');
		$this->assertFileExists($folder1->getFullPath(), 'New path is created after write()');
		$this->assertFileNotExists($oldPathFile1, 'Old file is removed after write()');
		$this->assertFileExists($file1->getFullPath(), 'New file path is created after write()');
		$this->assertFileNotExists($oldPathSubfolder1, 'Subfolder is removed after write()');
		$this->assertFileExists($subfolder1->getFullPath(), 'New subfolder path is created after write()');

		// Clean up after ourselves - tearDown() doesn't like renamed fixtures
		$folder1->delete(); // implicitly deletes subfolder as well
	}

	/**
	 * @see FileTest->testSetParentIDChangesFilesystemOnWrite()
	 */
	public function testSetParentIDChangesFilesystemOnWrite() {
		$folder1 = $this->objFromFixture('Folder', 'folder1');
		$folder2 = $this->objFromFixture('Folder', 'folder2');
		$oldPathFolder1 = $folder1->getFullPath();

		// set ParentID
		$folder1->ParentID = $folder2->ID;

		// Before write()
		$this->assertFileExists($oldPathFolder1, 'Old path is still present');
		$this->assertFileNotExists($folder1->getFullPath(),
			'New path is updated in memory, not written before write() is called');

		$folder1->write();

		// After write()
		clearstatcache();
		$this->assertFileNotExists($oldPathFolder1, 'Old path is removed after write()');
		$this->assertFileExists($folder1->getFullPath(), 'New path is created after write()');
	}

	/**
	 * Tests for the bug #5994 - Moving folder after executing Folder::findOrMake will not set the Filenames properly
	 */
	public function testFindOrMakeFolderThenMove() {
		$folder1 = $this->objFromFixture('Folder', 'folder1');
		Folder::find_or_make($folder1->Filename);
		$folder2 = $this->objFromFixture('Folder', 'folder2');

		// set ParentID
		$folder1->ParentID = $folder2->ID;
		$folder1->write();

		// Check if the file in the folder moved along
		$file1 = DataObject::get_by_id('File', $this->idFromFixture('File', 'file1-folder1'), false);
		$this->assertFileExists($file1->getFullPath());
		$this->assertEquals($file1->Filename, 'assets/FileTest-folder2/FileTest-folder1/File1.txt',
			'The file DataObject has updated path');
	}

	/**
	 * Tests for the bug #5994 - if you don't execute get_by_id prior to the rename or move, it will fail.
	 */
	public function testRenameFolderAndCheckTheFile() {
		// ID is prefixed in case Folder is subclassed by project/other module.
		$folder1 = DataObject::get_one('Folder', array(
			'"File"."ID"' => $this->idFromFixture('Folder', 'folder1')
		));

		$folder1->Name = 'FileTest-folder1-changed';
		$folder1->write();

		// Check if the file in the folder moved along
		$file1 = DataObject::get_by_id('File', $this->idFromFixture('File', 'file1-folder1'), false);
		$this->assertFileExists($file1->getFullPath());
		$this->assertEquals($file1->Filename, 'assets/FileTest-folder1-changed/File1.txt',
			'The file DataObject path uses renamed folder');
	}

	/**
	 * @see FileTest->testLinkAndRelativeLink()
	 */
	public function testLinkAndRelativeLink() {
		$folder = $this->objFromFixture('Folder', 'folder1');
		$this->assertEquals(ASSETS_DIR . '/FileTest-folder1/', $folder->RelativeLink());
		$this->assertEquals(Director::baseURL() . ASSETS_DIR . '/FileTest-folder1/', $folder->Link());
	}

	/**
	 * @see FileTest->testGetRelativePath()
	 */
	public function testGetRelativePath() {
		$rootfolder = $this->objFromFixture('Folder', 'folder1');
		$this->assertEquals('assets/FileTest-folder1/', $rootfolder->getRelativePath(), 'Folder in assets/');
	}

	/**
	 * @see FileTest->testGetFullPath()
	 */
	public function testGetFullPath() {
		$rootfolder = $this->objFromFixture('Folder', 'folder1');
		$this->assertEquals(ASSETS_PATH . '/FileTest-folder1/', $rootfolder->getFullPath(), 'File in assets/ folder');
	}

	public function testDeleteAlsoRemovesFilesystem() {
		$path = '/FolderTest/DeleteAlsoRemovesFilesystemAndChildren';
		$folder = Folder::find_or_make($path);
		$this->assertFileExists(ASSETS_PATH . $path);

		$folder->delete();

		$this->assertFileNotExists(ASSETS_PATH . $path);
	}

	public function testDeleteAlsoRemovesSubfoldersInDatabaseAndFilesystem() {
		$path = '/FolderTest/DeleteAlsoRemovesSubfoldersInDatabaseAndFilesystem';
		$subfolderPath = $path . '/subfolder';
		$folder = Folder::find_or_make($path);
		$subfolder = Folder::find_or_make($subfolderPath);
		$subfolderID = $subfolder->ID;

		$folder->delete();

		$this->assertFileNotExists(ASSETS_PATH . $path);
		$this->assertFileNotExists(ASSETS_PATH . $subfolderPath, 'Subfolder removed from filesystem');
		$this->assertFalse(DataObject::get_by_id('Folder', $subfolderID), 'Subfolder removed from database');
	}

	public function testDeleteAlsoRemovesContainedFilesInDatabaseAndFilesystem() {
		$path = '/FolderTest/DeleteAlsoRemovesContainedFilesInDatabaseAndFilesystem';
		$folder = Folder::find_or_make($path);

		$file = $this->objFromFixture('File', 'gif');
		$file->ParentID = $folder->ID;
		$file->write();
		$fileID = $file->ID;
		$fileAbsPath = $file->getFullPath();
		$this->assertFileExists($fileAbsPath);

		$folder->delete();

		$this->assertFileNotExists($fileAbsPath, 'Contained files removed from filesystem');
		$this->assertFalse(DataObject::get_by_id('File', $fileID), 'Contained files removed from database');

	}

	/**
	 * @see FileTest->testDeleteDatabaseOnly()
	 */
	public function testDeleteDatabaseOnly() {
		$subfolder = $this->objFromFixture('Folder', 'subfolder');
		$subfolderID = $subfolder->ID;
		$subfolderFile = $this->objFromFixture('File', 'subfolderfile');
		$subfolderFileID = $subfolderFile->ID;

		$subfolder->deleteDatabaseOnly();

		DataObject::flush_and_destroy_cache();

		$this->assertFileExists($subfolder->getFullPath());
		$this->assertFalse(DataObject::get_by_id('Folder', $subfolderID));

		$this->assertFileExists($subfolderFile->getFullPath());
		$this->assertFalse(DataObject::get_by_id('File', $subfolderFileID));
	}

	public function setUp() {
		parent::setUp();

		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);

		// Create a test folders for each of the fixture references
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if(!file_exists(BASE_PATH."/$folder->Filename")) mkdir(BASE_PATH."/$folder->Filename");
		}

		// Create a test files for each of the fixture references
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			$fh = fopen(BASE_PATH."/$file->Filename", "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	}

	public function tearDown() {
		$testPath = ASSETS_PATH . '/FolderTest';
		if(file_exists($testPath)) Filesystem::removeFolder($testPath);

		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			if($file && file_exists(BASE_PATH."/$file->Filename")) unlink(BASE_PATH."/$file->Filename");
		}

		// Remove the test folders that we've crated
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			// Might have been removed during test
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) {
				Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
			}
		}

		parent::tearDown();
	}

	public function testSyncedChildren() {
		mkdir(ASSETS_PATH ."/FolderTest");
		mkdir(ASSETS_PATH ."/FolderTest/sync");

		$files = array(
			'.htaccess',
			'.git',
			'web.config',
			'.DS_Store',
			'_my_synced_file.txt',
			'invalid_extension.xyz123'
		);

		$folders = array(
			'_combinedfiles',
			'_resampled',
			'_testsync'
		);

		foreach($files as $file) {
			$fh = fopen(ASSETS_PATH."/FolderTest/sync/$file", "w");
			fwrite($fh, 'test');
			fclose($fh);
		}

		foreach($folders as $folder) {
			mkdir(ASSETS_PATH ."/FolderTest/sync/". $folder);
		}

		$folder = Folder::find_or_make('/FolderTest/sync');
		$result = $folder->syncChildren();

		$this->assertEquals(11, $result['skipped']);
		$this->assertEquals(2, $result['added']);

		// folder with a path of _test should exist
		$this->assertEquals(1, Folder::get()->filter(array(
			'Name' => '_testsync'
		))->count());

		$this->assertEquals(1, File::get()->filter(array(
			'Name' => '_my_synced_file.txt'
		))->count());

		$this->assertEquals(0, File::get()->filter(array(
			'Name' => 'invalid_extension.xyz123'
		))->count());
	}

	public function testIllegalFilenames() {

		// Test that generating a filename with invalid characters generates a correctly named folder.
		$folder = Folder::find_or_make('/FolderTest/EN_US Lang');
		$this->assertEquals(ASSETS_DIR.'/FolderTest/EN-US-Lang/', $folder->getRelativePath());

		// Test repeatitions of folder
		$folder2 = Folder::find_or_make('/FolderTest/EN_US Lang');
		$this->assertEquals($folder->ID, $folder2->ID);

		$folder3 = Folder::find_or_make('/FolderTest/EN--US_L!ang');
		$this->assertEquals($folder->ID, $folder3->ID);

		$folder4 = Folder::find_or_make('/FolderTest/EN-US-Lang');
		$this->assertEquals($folder->ID, $folder4->ID);
	}

	public function testTitleTiedToName() {
		$newFolder = new Folder();

		$newFolder->Name = 'TestNameCopiedToTitle';
		$this->assertEquals($newFolder->Name, $newFolder->Title);

		$newFolder->Title = 'TestTitleCopiedToName';
		$this->assertEquals($newFolder->Name, $newFolder->Title);

		$newFolder->Name = 'TestNameWithIllegalCharactersCopiedToTitle <!BANG!>';
		$this->assertEquals($newFolder->Name, $newFolder->Title);

		$newFolder->Title = 'TestTitleWithIllegalCharactersCopiedToName <!BANG!>';
		$this->assertEquals($newFolder->Name, $newFolder->Title);
	}
}
