<?php

use Filesystem as SS_Filesystem;
use SilverStripe\ORM\DataExtension;


/**
 * Ensures that File dataobjects can be safely migrated from 3.x
 */
class FileMigrationHelperTest extends SapphireTest {

	protected static $fixture_file = 'FileMigrationHelperTest.yml';

	protected $requiredExtensions = array(
		"File" => array(
			"FileMigrationHelperTest_Extension"
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
		Config::inst()->update('File', 'migrate_legacy_file', false);
		parent::setUp();

		// Set backend root to /FileMigrationHelperTest/assets
		AssetStoreTest_SpyStore::activate('FileMigrationHelperTest/assets');

		// Ensure that each file has a local record file in this new assets base
		$from = FRAMEWORK_PATH . '/tests/model/testimages/test-image-low-quality.jpg';
		foreach(File::get()->exclude('ClassName', 'Folder') as $file) {
			$dest = AssetStoreTest_SpyStore::base_path() . '/' . $file->generateFilename();
			SS_Filesystem::makeFolder(dirname($dest));
			copy($from, $dest);
		}
	}

	public function tearDown() {
		AssetStoreTest_SpyStore::reset();
		SS_Filesystem::removeFolder($this->getBasePath());
		parent::tearDown();
		Config::unnest();
	}

	/**
	 * Test file migration
	 */
	public function testMigration() {
		// Prior to migration, check that each file has empty Filename / Hash properties
		foreach(File::get()->exclude('ClassName', 'Folder') as $file) {
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
		foreach(File::get()->exclude('ClassName', 'Folder') as $file) {
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

/**
 * @property File $owner
 */
class FileMigrationHelperTest_Extension extends DataExtension implements TestOnly {
	/**
	 * Ensure that File dataobject has the legacy "Filename" field
	 */
	private static $db = array(
		"Filename" => "Text",
	);

	public function onBeforeWrite() {
		// Ensure underlying filename field is written to the database
		$this->owner->setField('Filename', 'assets/' . $this->owner->generateFilename());
	}
}
