<?php

use Filesystem as SS_Filesystem;
use SilverStripe\Filesystem\Storage\AssetStore;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;


/**
 * Tests for the File class
 */
class FileTest extends SapphireTest {

	protected static $fixture_file = 'FileTest.yml';

	protected $extraDataObjects = array('FileTest_MyCustomFile');

	public function setUp() {
		parent::setUp();
		$this->logInWithPermission('ADMIN');
		Versioned::set_stage(Versioned::DRAFT);

		// Set backend root to /ImageTest
		AssetStoreTest_SpyStore::activate('FileTest');

		// Create a test folders for each of the fixture references
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			$filePath = ASSETS_PATH . '/FileTest/' . $folder->getFilename();
			SS_Filesystem::makeFolder($filePath);
		}

		// Create a test files for each of the fixture references
		$fileIDs = $this->allFixtureIDs('File');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('File', $fileID);
			$root = ASSETS_PATH . '/FileTest/';
			if($folder = $file->Parent()) {
				$root .= $folder->getFilename();
			}
			$path = $root . substr($file->getHash(), 0, 10) . '/' . basename($file->getFilename());
			SS_Filesystem::makeFolder(dirname($path));
			$fh = fopen($path, "w+");
			fwrite($fh, str_repeat('x', 1000000));
			fclose($fh);
		}

		// Conditional fixture creation in case the 'cms' module is installed
		if(class_exists('ErrorPage')) {
			$page = new ErrorPage(array(
				'Title' => 'Page not Found',
				'ErrorCode' => 404
			));
			$page->write();
			$page->copyVersionToStage('Stage', 'Live');
		}
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		parent::tearDown();
	}

	public function testLinkShortcodeHandler() {
		$testFile = $this->objFromFixture('File', 'asdf');

		$parser = new ShortcodeParser();
		$parser->register('file_link', array('File', 'handle_shortcode'));

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
		$testfilePath = BASE_PATH . '/assets/FileTest/CreateWithFilenameHasCorrectPath.txt'; // Important: No leading slash
		$fh = fopen($testfilePath, "w");
		fwrite($fh, str_repeat('x',1000000));
		fclose($fh);

		$file = new File();
		$file->setFromLocalFile($testfilePath);
		$file->ParentID = $folder->ID;
		$file->write();

		$this->assertEquals(
			'CreateWithFilenameHasCorrectPath.txt',
			$file->Name,
			'"Name" property is automatically set from "Filename"'
		);
		$this->assertEquals(
			'FileTest/CreateWithFilenameHasCorrectPath.txt',
			$file->Filename,
			'"Filename" property remains unchanged'
		);

		// TODO This should be auto-detected, see File->updateFilesystem()
		// $this->assertInstanceOf('Folder', $file->Parent(), 'Parent folder is created in database');
		// $this->assertFileExists($file->Parent()->getURL(), 'Parent folder is created on filesystem');
		// $this->assertEquals('FileTest', $file->Parent()->Name);
		// $this->assertInstanceOf('Folder', $file->Parent()->Parent(), 'Grandparent folder is created in database');
		// $this->assertFileExists($file->Parent()->Parent()->getURL(),
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

		Config::inst()->remove('File', 'allowed_extensions');
		Config::inst()->update('File', 'allowed_extensions', $orig);
	}

	public function testAppCategory() {
		// Test various categories
		$this->assertEquals('image', File::get_app_category('jpg'));
		$this->assertEquals('image', File::get_app_category('JPG'));
		$this->assertEquals('image', File::get_app_category('JPEG'));
		$this->assertEquals('image', File::get_app_category('png'));
		$this->assertEquals('image', File::get_app_category('tif'));
		$this->assertEquals('document', File::get_app_category('pdf'));
		$this->assertEquals('video', File::get_app_category('mov'));
		$this->assertEquals('audio', File::get_app_category('OGG'));
	}

	public function testGetCategoryExtensions() {
		// Test specific categories
		$images = array(
			'alpha', 'als', 'bmp', 'cel', 'gif', 'ico', 'icon', 'jpeg', 'jpg', 'pcx', 'png', 'ps', 'tif', 'tiff'
		);
		$this->assertEquals($images, File::get_category_extensions('image'));
		$this->assertEquals(array('gif', 'jpeg', 'jpg', 'png'), File::get_category_extensions('image/supported'));
		$this->assertEquals($images, File::get_category_extensions(array('image', 'image/supported')));
		$this->assertEquals(
			array('fla', 'gif', 'jpeg', 'jpg', 'png', 'swf'),
			File::get_category_extensions(array('flash', 'image/supported'))
		);

		// Test other categories have at least one item
		$this->assertNotEmpty(File::get_category_extensions('archive'));
		$this->assertNotEmpty(File::get_category_extensions('audio'));
		$this->assertNotEmpty(File::get_category_extensions('document'));
		$this->assertNotEmpty(File::get_category_extensions('flash'));
		$this->assertNotEmpty(File::get_category_extensions('video'));
	}

	/**
	 * @dataProvider allowedExtensions
	 * @param string $extension
	 */
	public function testAllFilesHaveCategory($extension) {
		$this->assertNotEmpty(
			File::get_app_category($extension),
			"Assert that extension {$extension} has a valid category"
		);
	}

	/**
	 * Gets the list of all extensions for testing
	 *
	 * @return array
	 */
	public function allowedExtensions() {
		$args = array();
		foreach(array_filter(File::config()->allowed_extensions) as $ext) {
			$args[] = array($ext);
		}
		return $args;
	}

	public function testSetNameChangesFilesystemOnWrite() {
		/** @var File $file */
		$file = $this->objFromFixture('File', 'asdf');
		$this->logInWithPermission('ADMIN');
		$file->publishRecursive();
		$oldTuple = $file->File->getValue();

		// Rename
		$file->Name = 'renamed.txt';
		$newTuple = $oldTuple;
		$newTuple['Filename'] = $file->generateFilename();

		// Before write()
		$this->assertTrue(
			$this->getAssetStore()->exists($oldTuple['Filename'], $oldTuple['Hash']),
			'Old path is still present'
		);
		$this->assertFalse(
			$this->getAssetStore()->exists($newTuple['Filename'], $newTuple['Hash']),
			'New path is updated in memory, not written before write() is called'
		);

		// After write()
		$file->write();
		$this->assertTrue(
			$this->getAssetStore()->exists($oldTuple['Filename'], $oldTuple['Hash']),
			'Old path exists after draft change'
		);
		$this->assertTrue(
			$this->getAssetStore()->exists($newTuple['Filename'], $newTuple['Hash']),
			'New path is created after write()'
		);

		// After publish
		$file->publishRecursive();
		$this->assertFalse(
			$this->getAssetStore()->exists($oldTuple['Filename'], $oldTuple['Hash']),
			'Old file is finally removed after publishing new file'
		);
		$this->assertTrue(
			$this->getAssetStore()->exists($newTuple['Filename'], $newTuple['Hash']),
			'New path is created after write()'
		);
	}

	public function testSetParentIDChangesFilesystemOnWrite() {
		$file = $this->objFromFixture('File', 'asdf');
		$this->logInWithPermission('ADMIN');
		$file->publishRecursive();
		$subfolder = $this->objFromFixture('Folder', 'subfolder');
		$oldTuple = $file->File->getValue();

		// set ParentID
		$file->ParentID = $subfolder->ID;
		$newTuple = $oldTuple;
		$newTuple['Filename'] = $file->generateFilename();

		// Before write()
		$this->assertTrue(
			$this->getAssetStore()->exists($oldTuple['Filename'], $oldTuple['Hash']),
			'Old path is still present'
		);
		$this->assertFalse(
			$this->getAssetStore()->exists($newTuple['Filename'], $newTuple['Hash']),
			'New path is updated in memory, not written before write() is called'
		);
		$file->write();

		// After write()
		$file->write();
		$this->assertTrue(
			$this->getAssetStore()->exists($oldTuple['Filename'], $oldTuple['Hash']),
			'Old path exists after draft change'
		);
		$this->assertTrue(
			$this->getAssetStore()->exists($newTuple['Filename'], $newTuple['Hash']),
			'New path is created after write()'
		);

		// After publish
		$file->publishSingle();
		$this->assertFalse(
			$this->getAssetStore()->exists($oldTuple['Filename'], $oldTuple['Hash']),
			'Old file is finally removed after publishing new file'
		);
		$this->assertTrue(
			$this->getAssetStore()->exists($newTuple['Filename'], $newTuple['Hash']),
			'New path is created after write()'
		);
	}

	/**
	 * @see http://open.silverstripe.org/ticket/5693
	 *
	 * @expectedException SilverStripe\ORM\ValidationException
	 */
	public function testSetNameWithInvalidExtensionDoesntChangeFilesystem() {
		$orig = Config::inst()->get('File', 'allowed_extensions');
		Config::inst()->remove('File', 'allowed_extensions');
		Config::inst()->update('File', 'allowed_extensions', array('txt'));

		$file = $this->objFromFixture('File', 'asdf');
		$oldPath = $file->getURL();

		$file->Name = 'renamed.php'; // evil extension
		try {
			$file->write();
		} catch(ValidationException $e) {
			Config::inst()->remove('File', 'allowed_extensions');
			Config::inst()->update('File', 'allowed_extensions', $orig);
			throw $e;
		}
	}

	public function testGetURL() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals('/assets/FileTest/55b443b601/FileTest.txt', $rootfile->getURL());
	}

	public function testGetAbsoluteURL() {
		$rootfile = $this->objFromFixture('File', 'asdf');
		$this->assertEquals(
			Director::absoluteBaseURL() . 'assets/FileTest/55b443b601/FileTest.txt',
			$rootfile->getAbsoluteURL()
		);
	}

	public function testNameAndTitleGeneration() {
		// When name is assigned, title is automatically assigned
		$file = $this->objFromFixture('Image', 'setfromname');
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
		$file = $this->objFromFixture('Image', 'gif');
		$this->assertEquals("GIF image - good for diagrams", $file->FileType);

		$file = $this->objFromFixture('File', 'pdf');
		$this->assertEquals("Adobe Acrobat PDF file", $file->FileType);

		$file = $this->objFromFixture('Image', 'gifupper');
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

	public function testDeleteFile() {
		/** @var File $file */
		$file = $this->objFromFixture('File', 'asdf');
		$this->logInWithPermission('ADMIN');
		$file->publishSingle();
		$tuple = $file->File->getValue();

		// Before delete
		$this->assertTrue(
			$this->getAssetStore()->exists($tuple['Filename'], $tuple['Hash']),
			'File is still present'
		);

		// after unpublish
		$file->doUnpublish();
		$this->assertTrue(
			$this->getAssetStore()->exists($tuple['Filename'], $tuple['Hash']),
			'File is still present after unpublish'
		);

		// after delete
		$file->delete();
		$this->assertFalse(
			$this->getAssetStore()->exists($tuple['Filename'], $tuple['Hash']),
			'File is deleted after unpublish and delete'
		);
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
		$this->assertEquals(
			$newTitle . "/",
			$folder->Filename,
			"Folder Filename updated after rename of Title"
		);

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
		$file = $this->objFromFixture('Image', 'gif');

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


	public function testJoinPaths() {
		$this->assertEquals('name/file.jpg', File::join_paths('/name', 'file.jpg'));
		$this->assertEquals('name/file.jpg', File::join_paths('name', 'file.jpg'));
		$this->assertEquals('name/file.jpg', File::join_paths('/name', '/file.jpg'));
		$this->assertEquals('name/file.jpg', File::join_paths('name/', '/', 'file.jpg'));
		$this->assertEquals('file.jpg', File::join_paths('/', '/', 'file.jpg'));
		$this->assertEquals('', File::join_paths('/', '/'));
	}

	/**
	 * @return AssetStore
	 */
	protected function getAssetStore() {
		return Injector::inst()->get('AssetStore');
	}

}

class FileTest_MyCustomFile extends File implements TestOnly {

}
