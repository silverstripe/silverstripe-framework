<?php

/**
 * Tests for the File class
 */
class FileTest extends SapphireTest {
	
	static $fixture_file = 'sapphire/tests/filesystem/FileTest.yml';
	
	function testCreateWithFilenameWithSubfolder() {
		// Note: We can't use fixtures/setUp() for this, as we want to create the db record manually.
		// Creating the folder is necessary to avoid having "Filename" overwritten by setName()/setRelativePath(),
		// because the parent folders don't exist in the database
		$folder = Folder::findOrMake('/FileTest/');
		$testfilePath = 'assets/FileTest/CreateWithFilenameHasCorrectPath.txt'; // Important: No leading slash
		$fh = fopen(BASE_PATH . '/' . $testfilePath, "w");
		fwrite($fh, str_repeat('x',1000000));
		fclose($fh);
				
		$file = new File();
		$file->Filename = $testfilePath;
		// TODO This should be auto-detected
		$file->ParentID = $folder->ID;
		$file->write();
		
		$this->assertEquals('CreateWithFilenameHasCorrectPath.txt', $file->Name, '"Name" property is automatically set from "Filename"');
		$this->assertEquals($testfilePath, $file->Filename, '"Filename" property remains unchanged');

		// TODO This should be auto-detected, see File->updateFilesystem()
		// $this->assertType('Folder', $file->Parent(), 'Parent folder is created in database');
		// $this->assertFileExists($file->Parent()->getFullPath(), 'Parent folder is created on filesystem');
		// $this->assertEquals('FileTest', $file->Parent()->Name);
		// $this->assertType('Folder', $file->Parent()->Parent(), 'Grandparent folder is created in database');
		// $this->assertFileExists($file->Parent()->Parent()->getFullPath(), 'Grandparent folder is created on filesystem');
		// $this->assertEquals('assets', $file->Parent()->Parent()->Name);
	}
		
	function testGetExtension() {
		$this->assertEquals('', File::get_file_extension('myfile'), 'No extension');
		$this->assertEquals('txt', File::get_file_extension('myfile.txt'), 'Simple extension');
		$this->assertEquals('gz', File::get_file_extension('myfile.tar.gz'), 'Double-barrelled extension only returns last bit');
	}
	
	function testValidateExtension() {
		Session::set('loggedInAs', null);
		
		$origExts = File::$allowed_extensions;
		File::$allowed_extensions = array('txt');
		
		$file = $this->objFromFixture('File', 'asdf'); 
	
		// Invalid ext
		$file->Name = 'asdf.php';
		$v = $file->validate();
		$this->assertFalse($v->valid());
		$this->assertContains('Extension is not allowed', $v->message());
		
		// Valid ext
		$file->Name = 'asdf.txt';
		$v = $file->validate();
		$this->assertTrue($v->valid());
		
		// Capital extension is valid as well
		$file->Name = 'asdf.TXT';
		$v = $file->validate();
		$this->assertTrue($v->valid());
		
		File::$allowed_extensions = $origExts;
	}
	
	function testSetNameChangesFilesystemOnWrite() {
		$file = $this->objFromFixture('File', 'asdf');
		$oldPath = $file->getFullPath();
	
		// Before write()
		$file->Name = 'renamed.txt';
		$this->assertFileExists($oldPath, 'Old path is still present');
		$this->assertFileNotExists($file->getFullPath(), 'New path is updated in memory, not written before write() is called');
	
		$file->write();
		
		// After write()
		clearstatcache();
		$this->assertFileNotExists($oldPath, 'Old path is removed after write()');
		$this->assertFileExists($file->getFullPath(), 'New path is created after write()');
	}
	
	function testSetParentIDChangesFilesystemOnWrite() {
		$file = $this->objFromFixture('File', 'asdf');
		$subfolder = $this->objFromFixture('Folder', 'subfolder');
		$oldPath = $file->getFullPath();
		
		// set ParentID
		$file->ParentID = $subfolder->ID;

		// Before write()
		$this->assertFileExists($oldPath, 'Old path is still present');
		$this->assertFileNotExists($file->getFullPath(), 'New path is updated in memory, not written before write() is called');

		$file->write();
		
		// After write()
		clearstatcache();
		$this->assertFileNotExists($oldPath, 'Old path is removed after write()');
		$this->assertFileExists($file->getFullPath(), 'New path is created after write()');
	}
	
	/**
	 * @see http://open.silverstripe.org/ticket/5693
	 */
	function testSetNameWithInvalidExtensionDoesntChangeFilesystem() {
		$origExts = File::$allowed_extensions;
		File::$allowed_extensions = array('txt');
		
		$file = $this->objFromFixture('File', 'asdf');
		$oldPath = $file->getFullPath();
	
		$file->Name = 'renamed.php'; // evil extension	
		try {
			$file->write();
		} catch(ValidationException $e) {
			File::$allowed_extensions = $origExts;
			return;
		}
		
		$this->fail('Expected ValidationException not raised');
		File::$allowed_extensions = $origExts;
	}
	
	function testLinkAndRelativeLink() {
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(ASSETS_DIR . '/FileTest.txt', $file->RelativeLink());
		$this->assertEquals(Director::baseURL() . ASSETS_DIR . '/FileTest.txt', $file->Link());
	}
	
	function testGetRelativePath() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals('assets/FileTest.txt', $rootfile->getRelativePath(), 'File in assets/ folder');
		
		$subfolderfile = $this->objFromFixture('File', 'subfolderfile');
		$this->assertEquals('assets/FileTest-subfolder/FileTestSubfolder.txt', $subfolderfile->getRelativePath(), 'File in subfolder within assets/ folder, with existing Filename');
		
		$subfolderfilesetfromname = $this->objFromFixture('File', 'subfolderfile-setfromname');
		$this->assertEquals('assets/FileTest-subfolder/FileTestSubfolder2.txt', $subfolderfilesetfromname->getRelativePath(), 'File in subfolder within assets/ folder, with Filename generated through setName()');
	}
	
	function testGetFullPath() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(ASSETS_PATH . '/FileTest.txt', $rootfile->getFullPath(), 'File in assets/ folder');
	}
	
	function testGetURL() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(Director::baseURL() . $rootfile->getFilename(), $rootfile->getURL());
	}
	
	function testGetAbsoluteURL() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(Director::absoluteBaseURL() . $rootfile->getFilename(), $rootfile->getAbsoluteURL());
	}
	
	function testNameAndTitleGeneration() {
		/* If objects are loaded into the system with just a Filename, then Name is generated but Title isn't */
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals('FileTest.txt', $file->Name);
		$this->assertNull($file->Title);
		
		/* However, if Name is set instead of Filename, then Title is set */
		$file = $this->objFromFixture('File', 'setfromname');
		$this->assertEquals(ASSETS_DIR . '/FileTest.png', $file->Filename);
		$this->assertEquals('FileTest', $file->Title);
	}

	function testSizeAndAbsoluteSizeParameters() {
		$file = $this->objFromFixture('File', 'asdf');
		
		/* AbsoluteSize will give the integer number */
		$this->assertEquals(1000000, $file->AbsoluteSize);
		/* Size will give a humanised number */
		$this->assertEquals('977 KB', $file->Size);
	}
	
	function testFileType() {
		$file = $this->objFromFixture('File', 'gif');
		$this->assertEquals("GIF image - good for diagrams", $file->FileType);
	
		$file = $this->objFromFixture('File', 'pdf');
		$this->assertEquals("Adobe Acrobat PDF file", $file->FileType);
	
		/* Only a few file types are given special descriptions; the rest are unknown */
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals("unknown", $file->FileType);
	}
	
	/**
	 * Test the File::format_size() method
	 */
	function testFormatSize() {
		$this->assertEquals("1000 bytes", File::format_size(1000));
		$this->assertEquals("1023 bytes", File::format_size(1023));
		$this->assertEquals("1 KB", File::format_size(1025));
		$this->assertEquals("9.8 KB", File::format_size(10000));
		$this->assertEquals("49 KB", File::format_size(50000));
		$this->assertEquals("977 KB", File::format_size(1000000));
		$this->assertEquals("1 MB", File::format_size(1024*1024));
		$this->assertEquals("954 MB", File::format_size(1000000000));
		$this->assertEquals("1 GB", File::format_size(1024*1024*1024));
		$this->assertEquals("9.3 GB", File::format_size(10000000000));
		// It use any denomination higher than GB.  It also doesn't overflow with >32 bit integers
		$this->assertEquals("93132.3 GB", File::format_size(100000000000000));
	}
	
	function testDeleteDatabaseOnly() {
		$file = $this->objFromFixture('File', 'asdf');
		$fileID = $file->ID;
		$filePath = $file->getFullPath();
		
		$file->deleteDatabaseOnly();
		
		DataObject::flush_and_destroy_cache();
		
		$this->assertFileExists($filePath);
		$this->assertFalse(DataObject::get_by_id('File', $fileID));
	}
		
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function setUp() {
		parent::setUp();
		
		if(!file_exists(ASSETS_PATH)) mkdir(ASSETS_PATH);

		/* Create a test folders for each of the fixture references */
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if(!file_exists(BASE_PATH."/$folder->Filename")) mkdir(BASE_PATH."/$folder->Filename");
		}
		
		/* Create a test files for each of the fixture references */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			$fh = fopen(BASE_PATH."/$file->Filename", "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	} 
	
	function tearDown() {
		parent::tearDown();

		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			if($file && file_exists(BASE_PATH."/$file->Filename")) unlink(BASE_PATH."/$file->Filename");
		}

		/* Remove the test folders that we've crated */
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
		}

		// Remove left over folders and any files that may exist
		if(file_exists('../assets/FileTest')) Filesystem::removeFolder('../assets/FileTest');
		if(file_exists('../assets/FileTest-subfolder')) Filesystem::removeFolder('../assets/FileTest-subfolder');
		if(file_exists('../assets/FileTest.txt')) unlink('../assets/FileTest.txt');
	}

}