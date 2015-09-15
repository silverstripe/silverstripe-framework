<?php

use Filesystem as SS_Filesystem;

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
			$dest = $this->getBasePath() . '/assets/' . $file->getFilename();
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
			$filename = $file->getFilename();
			$this->assertNotEmpty($filename, "File {$file->Name} has a filename");
			$this->assertEmpty($file->File->getFilename(), "File {$file->Name} has no DBFile filename");
			$this->assertEmpty($file->File->getHash(), "File {$file->Name} has no hash");
			$this->assertFalse($file->exists(), "File with name {$file->Name} does not yet exist");
		}

		// Do migration
		$helper = new FileMigrationHelper();
		$result = $helper->run($this->getBasePath());
		$this->assertEquals(5, $result);

		// Test that each file exists
		foreach(File::get()->exclude('ClassName', 'Folder') as $file) {
			$filename = $file->File->getFilename();
			$this->assertNotEmpty($filename, "File {$file->Name} has a Filename");
			$this->assertEquals(
				'33be1b95cba0358fe54e8b13532162d52f97421c',
				$file->File->getHash(),
				"File with name {$filename} has the correct hash"
			);
			$this->assertTrue($file->exists(), "File with name {$filename} exists");
		}
	}

}

class FileMigrationHelperTest_Extension extends DataExtension implements TestOnly {
	/**
	 * Ensure that File dataobject has the legacy "Filename" field
	 */
	private static $db = array(
		"Filename" => "Text",
	);

	public function onBeforeWrite() {
		// Ensure underlying filename field is written to the database
		$this->owner->setField('Filename', 'assets/' . $this->owner->getFilename());
	}
}