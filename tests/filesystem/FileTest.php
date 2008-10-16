<?php

/**
 * Tests for the File class
 */
class FileTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/filesystem/FileTest.yml';
	
	function testLinkAndRelativeLink() {
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(ASSETS_DIR . '/asdfjkl.txt', $file->RelativeLink());
		$this->assertEquals(Director::baseURL() . ASSETS_DIR . '/asdfjkl.txt', $file->Link());
	}
	
	function testNameAndTitleGeneration() {
		/* If objects are loaded into the system with just a Filename, then Name is generated but Title isn't */
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals('asdfjkl.txt', $file->Name);
		$this->assertNull($file->Title);
		
		/* However, if Name is set instead of Filename, then Title is set */
		$file = $this->objFromFixture('File', 'setfromname');
		$this->assertEquals(ASSETS_DIR . '/asdfjkl.png', $file->Filename);
		$this->assertEquals('asdfjkl', $file->Title);
	}
	
	function testChangingNameAndFilenameAndParentID() {
		$file = $this->objFromFixture('File', 'asdf');

		/* If you alter the Name attribute of a file, then the filesystem is also affected */
		$file->Name = 'asdfjkl2.txt';
		clearstatcache();
		$this->assertFileNotExists(ASSETS_PATH . "/asdfjkl.txt");
		$this->assertFileExists(ASSETS_PATH . "/asdfjkl2.txt");
		/* The Filename field is also updated */
		$this->assertEquals(ASSETS_DIR . '/asdfjkl2.txt', $file->Filename);

		/* However, if you alter the Filename attribute, the the filesystem isn't affected.  Altering Filename directly isn't
		recommended */
		$file->Filename = ASSETS_DIR . '/asdfjkl3.txt';
		clearstatcache();
		$this->assertFileExists(ASSETS_PATH . "/asdfjkl2.txt");
		$this->assertFileNotExists(ASSETS_PATH . "/asdfjkl3.txt");
		
		$file->Filename = ASSETS_DIR . '/asdfjkl2.txt';
		$file->write();
		
		/* Instead, altering Name and ParentID is the recommended way of changing the name and location of a file */
		$file->ParentID = $this->idFromFixture('Folder', 'subfolder');
		clearstatcache();
		$this->assertFileExists(ASSETS_PATH . "/subfolder/asdfjkl2.txt");
		$this->assertFileNotExists(ASSETS_PATH . "/asdfjkl2.txt");
		$this->assertEquals(ASSETS_DIR . '/subfolder/asdfjkl2.txt', $file->Filename);
		$file->write();
		
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
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	function setUp() {
		parent::setUp();

		/* Create a test folders for each of the fixture references */
		$fileIDs = $this->allFixtureIDs('Folder');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Folder', $fileID);
			if(!file_exists("../$file->Filename")) mkdir("../$file->Filename");
		}
		
		/* Create a test files for each of the fixture references */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			$fh = fopen("../$file->Filename", "w");
			fwrite($fh, str_repeat('x',1000000));
			fclose($fh);
		}
	} 
	
	function tearDown() {
		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			if(file_exists("../$file->Filename")) unlink("../$file->Filename");
		}

		/* Remove the test folders that we've crated */
		$fileIDs = $this->allFixtureIDs('Folder');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Folder', $fileID);
			if(file_exists("../$file->Filename")) rmdir("../$file->Filename");
		}
		
		parent::tearDown();
	}
	
	
}