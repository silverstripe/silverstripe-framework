<?php

namespace SilverStripe\Assets\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Assets\FileMigrationHelper;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Tests\FileMigrationHelperTest\Extension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;

/**
 * Ensures that File dataobjects can be safely migrated from 3.x
 */
class FileMigrationHelperTest extends SapphireTest {

	protected static $fixture_file = 'FileMigrationHelperTest.yml';

	protected $requiredExtensions = array(
		File::class => array(
			Extension::class
		)
	);

	/**
	 * get the BASE_PATH for this test
	 *
	 * @return string
	 */
	protected function getBasePath() {
		// Note that the actual filesystem base is the 'assets' subdirectory within this
		return ASSETS_PATH . '/FileMigrationHelperTest';
	}


	public function setUp() {
		Config::nest(); // additional nesting here necessary
		Config::inst()->update(File::class, 'migrate_legacy_file', false);
		parent::setUp();

		// Set backend root to /FileMigrationHelperTest/assets
		TestAssetStore::activate('FileMigrationHelperTest/assets');

		// Ensure that each file has a local record file in this new assets base
		$from = FRAMEWORK_PATH . '/tests/php/ORM/ImageTest/test-image-low-quality.jpg';
		foreach(File::get()->exclude('ClassName', Folder::class) as $file) {
			$dest = TestAssetStore::base_path() . '/' . $file->generateFilename();
			Filesystem::makeFolder(dirname($dest));
			copy($from, $dest);
		}
	}

	public function tearDown() {
		TestAssetStore::reset();
		Filesystem::removeFolder($this->getBasePath());
		parent::tearDown();
		Config::unnest();
	}

	/**
	 * Test file migration
	 */
	public function testMigration() {
		// Prior to migration, check that each file has empty Filename / Hash properties
		foreach(File::get()->exclude('ClassName', Folder::class) as $file) {
			$filename = $file->generateFilename();
			$this->assertNotEmpty($filename, "File {$file->Name} has a filename");
			$this->assertEmpty($file->File->getFilename(), "File {$file->Name} has no DBFile filename");
			$this->assertEmpty($file->File->getHash(), "File {$file->Name} has no hash");
			$this->assertFalse($file->exists(), "File with name {$file->Name} does not yet exist");
			$this->assertFalse($file->isPublished(), "File is not published yet");
		}

		// Do migration
		$helper = new FileMigrationHelper();
		$result = $helper->run($this->getBasePath());
		$this->assertEquals(5, $result);

		// Test that each file exists
		foreach(File::get()->exclude('ClassName', Folder::class) as $file) {
			$expectedFilename = $file->generateFilename();
			$filename = $file->File->getFilename();
			$this->assertTrue($file->exists(), "File with name {$filename} exists");
			$this->assertNotEmpty($filename, "File {$file->Name} has a Filename");
			$this->assertEquals($expectedFilename, $filename, "File {$file->Name} has retained its Filename value");
			$this->assertEquals(
				'33be1b95cba0358fe54e8b13532162d52f97421c',
				$file->File->getHash(),
				"File with name {$filename} has the correct hash"
			);
			$this->assertTrue($file->isPublished(), "File is published after migration");
		}
	}

}
