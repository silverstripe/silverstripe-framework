<?php

/**
 * Tests for the File class
 */
class FileTest extends SapphireTest {

	protected static $fixture_file = 'FileTest.yml';

	protected $extraDataObjects = array('FileTest_MyCustomFile');

	public function testLinkShortcodeHandler() {
		$testFile = $this->objFromFixture('File', 'asdf');

		$parser = new ShortcodeParser();
		$parser->register('file_link', array('File', 'link_shortcode_handler'));

		$fileShortcode = sprintf('[file_link,id=%d]', $testFile->ID);
		$fileEnclosed  = sprintf('[file_link,id=%d]Example Content[/file_link]', $testFile->ID);

		$fileShortcodeExpected = $testFile->Link();
		$fileEnclosedExpected  = sprintf(
			'<a href="%s" class="file" data-type="txt" data-size="977 KB">Example Content</a>', $testFile->Link());

		$this->assertEquals($fileShortcodeExpected, $parser->parse($fileShortcode), 'Test that simple linking works.');
		$this->assertEquals($fileEnclosedExpected, $parser->parse($fileEnclosed), 'Test enclosed content is linked.');

		$testFile->delete();

		$fileShortcode = '[file_link,id="-1"]';
		$fileEnclosed  = '[file_link,id="-1"]Example Content[/file_link]';

		$this->assertEquals('', $parser->parse('[file_link]'), 'Test that invalid ID attributes are not parsed.');
		$this->assertEquals('', $parser->parse('[file_link,id="text"]'));
		$this->assertEquals('', $parser->parse('[file_link]Example Content[/file_link]'));

		if(class_exists('ErrorPage')) {
			$errorPage = ErrorPage::get()->filter('ErrorCode', 404)->First();
			$this->assertEquals(
				$errorPage->Link(),
				$parser->parse($fileShortcode),
				'Test link to 404 page if no suitable matches.'
			);
			$this->assertEquals(
				sprintf('<a href="%s">Example Content</a>', $errorPage->Link()),
				$parser->parse($fileEnclosed)
			);
		} else {
			$this->assertEquals('', $parser->parse($fileShortcode),
				'Short code is removed if file record is not present.');
			$this->assertEquals('', $parser->parse($fileEnclosed));
		}
	}

	public function testCreateWithFilenameWithSubfolder() {
		// Note: We can't use fixtures/setUp() for this, as we want to create the db record manually.
		// Creating the folder is necessary to avoid having "Filename" overwritten by setName()/setRelativePath(),
		// because the parent folders don't exist in the database
		$folder = Folder::find_or_make('/FileTest/');
		$testfilePath = 'assets/FileTest/CreateWithFilenameHasCorrectPath.txt'; // Important: No leading slash
		$fh = fopen(BASE_PATH . '/' . $testfilePath, "w");
		fwrite($fh, str_repeat('x',1000000));
		fclose($fh);

		$file = new File();
		$file->Filename = $testfilePath;
		// TODO This should be auto-detected
		$file->ParentID = $folder->ID;
		$file->write();

		$this->assertEquals('CreateWithFilenameHasCorrectPath.txt', $file->Name,
			'"Name" property is automatically set from "Filename"');
		$this->assertEquals($testfilePath, $file->Filename,
			'"Filename" property remains unchanged');

		// TODO This should be auto-detected, see File->updateFilesystem()
		// $this->assertInstanceOf('Folder', $file->Parent(), 'Parent folder is created in database');
		// $this->assertFileExists($file->Parent()->getFullPath(), 'Parent folder is created on filesystem');
		// $this->assertEquals('FileTest', $file->Parent()->Name);
		// $this->assertInstanceOf('Folder', $file->Parent()->Parent(), 'Grandparent folder is created in database');
		// $this->assertFileExists($file->Parent()->Parent()->getFullPath(),
		// 'Grandparent folder is created on filesystem');
		// $this->assertEquals('assets', $file->Parent()->Parent()->Name);
	}

	public function testGetExtension() {
		$this->assertEquals('', File::get_file_extension('myfile'),
			'No extension');
		$this->assertEquals('txt', File::get_file_extension('myfile.txt'),
			'Simple extension');
		$this->assertEquals('gz', File::get_file_extension('myfile.tar.gz'),
			'Double-barrelled extension only returns last bit');
	}

	public function testValidateExtension() {
		Session::set('loggedInAs', null);

		$orig = Config::inst()->get('File', 'allowed_extensions');
		Config::inst()->remove('File', 'allowed_extensions');
		Config::inst()->update('File', 'allowed_extensions', array('txt'));

		$file = $this->objFromFixture('File', 'asdf');

		// Invalid ext
		$file->Name = 'asdf.php';
		$v = $file->doValidate();
		$this->assertFalse($v->valid());
		$this->assertContains('Extension is not allowed', $v->message());

		// Valid ext
		$file->Name = 'asdf.txt';
		$v = $file->doValidate();
		$this->assertTrue($v->valid());

		// Capital extension is valid as well
		$file->Name = 'asdf.TXT';
		$v = $file->doValidate();
		$this->assertTrue($v->valid());

		Config::inst()->remove('File', 'allowed_extensions');
		Config::inst()->update('File', 'allowed_extensions', $orig);
	}

	public function testSetNameChangesFilesystemOnWrite() {
		$file = $this->objFromFixture('File', 'asdf');
		$oldPath = $file->getFullPath();

		// Before write()
		$file->Name = 'renamed.txt';
		$this->assertFileExists($oldPath,
			'Old path is still present');
		$this->assertFileNotExists($file->getFullPath(),
			'New path is updated in memory, not written before write() is called');

		$file->write();

		// After write()
		clearstatcache();
		$this->assertFileNotExists($oldPath, 'Old path is removed after write()');
		$this->assertFileExists($file->getFullPath(), 'New path is created after write()');
	}

	public function testSetParentIDChangesFilesystemOnWrite() {
		$file = $this->objFromFixture('File', 'asdf');
		$subfolder = $this->objFromFixture('Folder', 'subfolder');
		$oldPath = $file->getFullPath();

		// set ParentID
		$file->ParentID = $subfolder->ID;

		// Before write()
		$this->assertFileExists($oldPath,
			'Old path is still present');
		$this->assertFileNotExists($file->getFullPath(),
			'New path is updated in memory, not written before write() is called');

		$file->write();

		// After write()
		clearstatcache();
		$this->assertFileNotExists($oldPath,
			'Old path is removed after write()');
		$this->assertFileExists($file->getFullPath(),
			'New path is created after write()');
	}

	/**
	 * @see http://open.silverstripe.org/ticket/5693
	 *
	 * @expectedException ValidationException
	 */
	public function testSetNameWithInvalidExtensionDoesntChangeFilesystem() {
		$orig = Config::inst()->get('File', 'allowed_extensions');
		Config::inst()->remove('File', 'allowed_extensions');
		Config::inst()->update('File', 'allowed_extensions', array('txt'));

		$file = $this->objFromFixture('File', 'asdf');
		$oldPath = $file->getFullPath();

		$file->Name = 'renamed.php'; // evil extension
		try {
			$file->write();
		} catch(ValidationException $e) {
			Config::inst()->remove('File', 'allowed_extensions');
			Config::inst()->update('File', 'allowed_extensions', $orig);
			throw $e;
		}
	}

	/**
	 * Uses fixtures Folder.folder1 and File.setfromname
	 * @dataProvider setNameFileProvider
	 */
	public function testSetNameAddsUniqueSuffixWhenFilenameAlreadyExists($name, $expected)
	{
		$duplicate = new Folder;
		$duplicate->setName($name);
		$duplicate->write();

		$this->assertSame($expected, $duplicate->Name);
	}

	/**
	 * @return array[]
	 */
	public function setNameFileProvider()
	{
		return array(
			array('FileTest-folder1', 'FileTest-folder1-2'),
			array('FileTest.png', 'FileTest-2.png'),
		);
	}

	public function testLinkAndRelativeLink() {
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(ASSETS_DIR . '/FileTest.txt', $file->RelativeLink());
		$this->assertEquals(Director::baseURL() . ASSETS_DIR . '/FileTest.txt', $file->Link());
	}

	public function testGetRelativePath() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals('assets/FileTest.txt', $rootfile->getRelativePath(), 'File in assets/ folder');

		$subfolderfile = $this->objFromFixture('File', 'subfolderfile');
		$this->assertEquals('assets/FileTest-subfolder/FileTestSubfolder.txt', $subfolderfile->getRelativePath(),
			'File in subfolder within assets/ folder, with existing Filename');

		$subfolderfilesetfromname = $this->objFromFixture('File', 'subfolderfile-setfromname');
		$this->assertEquals('assets/FileTest-subfolder/FileTestSubfolder2.txt',
			$subfolderfilesetfromname->getRelativePath(),
			'File in subfolder within assets/ folder, with Filename generated through setName()');
	}

	public function testGetFullPath() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(ASSETS_PATH . '/FileTest.txt', $rootfile->getFullPath(), 'File in assets/ folder');
	}

	public function testGetURL() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(Director::baseURL() . $rootfile->getFilename(), $rootfile->getURL());
	}

	public function testGetAbsoluteURL() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(Director::absoluteBaseURL() . $rootfile->getFilename(), $rootfile->getAbsoluteURL());
	}

	public function testNameAndTitleGeneration() {
		/* If objects are loaded into the system with just a Filename, then Name is generated but Title isn't */
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals('FileTest.txt', $file->Name);
		$this->assertNull($file->Title);

		/* However, if Name is set instead of Filename, then Title is set */
		$file = $this->objFromFixture('File', 'setfromname');
		$this->assertEquals(ASSETS_DIR . '/FileTest.png', $file->Filename);
		$this->assertEquals('FileTest', $file->Title);
	}

	public function testSizeAndAbsoluteSizeParameters() {
		$file = $this->objFromFixture('File', 'asdf');

		/* AbsoluteSize will give the integer number */
		$this->assertEquals(1000000, $file->AbsoluteSize);
		/* Size will give a humanised number */
		$this->assertEquals('977 KB', $file->Size);
	}

	public function testFileType() {
		$file = $this->objFromFixture('File', 'gif');
		$this->assertEquals("GIF image - good for diagrams", $file->FileType);

		$file = $this->objFromFixture('File', 'pdf');
		$this->assertEquals("Adobe Acrobat PDF file", $file->FileType);

		$file = $this->objFromFixture('File', 'gifupper');
		$this->assertEquals("GIF image - good for diagrams", $file->FileType);

		/* Only a few file types are given special descriptions; the rest are unknown */
		$file = $this->objFromFixture('File', 'asdf');
		$this->assertEquals("unknown", $file->FileType);
	}

	/**
	 * Test the File::format_size() method
	 */
	public function testFormatSize() {
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

	public function testDeleteDatabaseOnly() {
		$file = $this->objFromFixture('File', 'asdf');
		$fileID = $file->ID;
		$filePath = $file->getFullPath();

		$file->deleteDatabaseOnly();

		DataObject::flush_and_destroy_cache();

		$this->assertFileExists($filePath);
		$this->assertFalse(DataObject::get_by_id('File', $fileID));
	}

	public function testRenameFolder() {
		$newTitle = "FileTest-folder-renamed";

		//rename a folder's title
		$folderID = $this->objFromFixture("Folder","folder2")->ID;
		$folder = DataObject::get_by_id('Folder',$folderID);
		$folder->Title = $newTitle;
		$folder->write();

		//get folder again and see if the filename has changed
		$folder = DataObject::get_by_id('Folder',$folderID);
		$this->assertEquals($folder->Filename, ASSETS_DIR ."/". $newTitle ."/",
			"Folder Filename updated after rename of Title");


		//rename a folder's name
		$newTitle2 = "FileTest-folder-renamed2";
		$folder->Name = $newTitle2;
		$folder->write();

		//get folder again and see if the Title has changed
		$folder = DataObject::get_by_id('Folder',$folderID);
		$this->assertEquals($folder->Title, $newTitle2,
			"Folder Title updated after rename of Name");


		//rename a folder's Filename
		$newTitle3 = "FileTest-folder-renamed3";
		$folder->Filename = $newTitle3;
		$folder->write();

		//get folder again and see if the Title has changed
		$folder = DataObject::get_by_id('Folder',$folderID);
		$this->assertEquals($folder->Title, $newTitle3,
			"Folder Title updated after rename of Filename");
	}


	public function testGetClassForFileExtension() {
		$orig = File::config()->class_for_file_extension;
		File::config()->class_for_file_extension = array('*' => 'MyGenericFileClass');
		File::config()->class_for_file_extension = array('foo' => 'MyFooFileClass');

		$this->assertEquals(
			'MyFooFileClass',
			File::get_class_for_file_extension('foo'),
			'Finds directly mapped file classes'
		);
		$this->assertEquals(
			'MyFooFileClass',
			File::get_class_for_file_extension('FOO'),
			'Works without case sensitivity'
		);
		$this->assertEquals(
			'MyGenericFileClass',
			File::get_class_for_file_extension('unknown'),
			'Falls back to generic class for unknown extensions'
		);

		File::config()->class_for_file_extension = $orig;
	}

	public function testFolderConstructChild() {
		$orig = File::config()->class_for_file_extension;
		File::config()->class_for_file_extension = array('gif' => 'FileTest_MyCustomFile');

		$folder1 = $this->objFromFixture('Folder', 'folder1');
		$fileID = $folder1->constructChild('myfile.gif');
		$file = DataObject::get_by_id('File', $fileID);
		$this->assertEquals('FileTest_MyCustomFile', get_class($file));

		File::config()->class_for_file_extension = $orig;
	}

	public function testSetsOwnerOnFirstWrite() {
		Session::set('loggedInAs', null);
		$member1 = new Member();
		$member1->write();
		$member2 = new Member();
		$member2->write();

		$file1 = new File();
		$file1->write();
		$this->assertEquals(0, $file1->OwnerID, 'Owner not written when no user is logged in');

		$member1->logIn();
		$file2 = new File();
		$file2->write();
		$this->assertEquals($member1->ID, $file2->OwnerID, 'Owner written when user is logged in');

		$member2->logIn();
		$file2->forceChange();
		$file2->write();
		$this->assertEquals($member1->ID, $file2->OwnerID, 'Owner not overwritten on existing files');
	}

	public function testCanEdit() {
		$file = $this->objFromFixture('File', 'gif');

		// Test anonymous permissions
		Session::set('loggedInAs', null);
		$this->assertFalse($file->canEdit(), "Anonymous users can't edit files");

		// Test permissionless user
		$this->objFromFixture('Member', 'frontend')->logIn();
		$this->assertFalse($file->canEdit(), "Permissionless users can't edit files");

		// Test global CMS section users
		$this->objFromFixture('Member', 'cms')->logIn();
		$this->assertTrue($file->canEdit(), "Users with all CMS section access can edit files");

		// Test cms access users without file access
		$this->objFromFixture('Member', 'security')->logIn();
		$this->assertFalse($file->canEdit(), "Security CMS users can't edit files");

		// Test asset-admin user
		$this->objFromFixture('Member', 'assetadmin')->logIn();
		$this->assertTrue($file->canEdit(), "Asset admin users can edit files");

		// Test admin
		$this->objFromFixture('Member', 'admin')->logIn();
		$this->assertTrue($file->canEdit(), "Admins can edit files");
	}

	/**
	 * Test that ini2bytes returns the number of bytes for a PHP ini style size declaration
	 *
	 * @param string $iniValue
	 * @param int    $expected
	 * @dataProvider ini2BytesProvider
	 */
	public function testIni2Bytes($iniValue, $expected) {
		$this->assertSame($expected, File::ini2bytes($iniValue));
	}

	/**
	 * @return array
	 */
	public function ini2BytesProvider() {
		return array(
			array('2048', 2 * 1024),
			array('2k', 2 * 1024),
			array('512M', 512 * 1024 * 1024),
			array('512MiB', 512 * 1024 * 1024),
			array('512 mbytes', 512 * 1024 * 1024),
			array('512 megabytes', 512 * 1024 * 1024),
			array('1024g', 1024 * 1024 * 1024 * 1024),
			array('1024G', 1024 * 1024 * 1024 * 1024)
		);
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function setUp() {
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

		// Conditional fixture creation in case the 'cms' module is installed
		if(class_exists('ErrorPage')) {
			$page = new ErrorPage(array(
				'Title' => 'Page not Found',
				'ErrorCode' => 404
			));
			$page->write();
			$page->publish('Stage', 'Live');
		}
	}

	public function tearDown() {
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
			if($folder && file_exists(BASE_PATH."/$folder->Filename")) {
				Filesystem::removeFolder(BASE_PATH."/$folder->Filename");
			}
		}

		// Remove left over folders and any files that may exist
		if(file_exists('../assets/FileTest')) Filesystem::removeFolder('../assets/FileTest');
		if(file_exists('../assets/FileTest-subfolder')) Filesystem::removeFolder('../assets/FileTest-subfolder');
		if(file_exists('../assets/FileTest.txt')) unlink('../assets/FileTest.txt');

		if (file_exists("../assets/FileTest-folder-renamed1")) {
			Filesystem::removeFolder("../assets/FileTest-folder-renamed1");
		}
		if (file_exists("../assets/FileTest-folder-renamed2")) {
			Filesystem::removeFolder("../assets/FileTest-folder-renamed2");
		}
		if (file_exists("../assets/FileTest-folder-renamed3")) {
			Filesystem::removeFolder("../assets/FileTest-folder-renamed3");
		}
	}

}

class FileTest_MyCustomFile extends File implements TestOnly {

}
