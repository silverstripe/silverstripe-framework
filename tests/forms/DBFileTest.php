<?php

use Filesystem as SS_Filesystem;
use League\Flysystem\Filesystem;
use SilverStripe\Filesystem\Flysystem\AssetAdapter;
use SilverStripe\Filesystem\Flysystem\FlysystemAssetStore;
use SilverStripe\Filesystem\Flysystem\FlysystemUrlPlugin;

/**
 * Description of DBFileTest
 *
 * @author dmooyman
 */
class DBFileTest extends SapphireTest {

	protected $extraDataObjects = array(
		'DBFileTest_Object',
		'DBFileTest_Subclass'
	);
	
	protected $usesDatabase = true;

	public function setUp() {
		parent::setUp();

		// Set backend
		$adapter = new AssetAdapter(ASSETS_PATH . '/DBFileTest');
		$filesystem = new Filesystem($adapter);
		$filesystem->addPlugin(new FlysystemUrlPlugin());
		$backend = new AssetStoreTest_SpyStore();
		$backend->setFilesystem($filesystem);
		Injector::inst()->registerService($backend, 'AssetStore');

		// Disable legacy
		Config::inst()->remove(get_class(new FlysystemAssetStore()), 'legacy_filenames');

		// Update base url
		Config::inst()->update('Director', 'alternate_base_url', '/mysite/');
	}

	public function tearDown() {
		SS_Filesystem::removeFolder(ASSETS_PATH . '/DBFileTest');
		parent::tearDown();
	}

	/**
	 * Test that images in a DBFile are rendered properly
	 */
	public function testRender() {
		$obj = new DBFileTest_Object();

		// Test image tag
		$fish = realpath(__DIR__ .'/../model/testimages/test_image_high-quality.jpg');
		$this->assertFileExists($fish);
		$obj->MyFile->setFromLocalFile($fish, 'awesome-fish.jpg');
		$this->assertEquals(
			'<img src="/mysite/assets/DBFileTest/a870de278b/awesome-fish.jpg" alt="awesome-fish.jpg" />',
			trim($obj->MyFile->forTemplate())
		);

		// Test download tag
		$obj->MyFile->setFromString('puppies', 'subdir/puppy-document.txt');
		$this->assertEquals(
			'<a href="/mysite/assets/DBFileTest/subdir/2a17a9cb4b/puppy-document.txt" title="puppy-document.txt" download="puppy-document.txt"/>',
			trim($obj->MyFile->forTemplate())
		);
	}

}

/**
 * @property DBFile $MyFile
 */
class DBFileTest_Object extends DataObject implements TestOnly {
	private static $db = array(
		'MyFile' => 'DBFile'
	);
}


class DBFileTest_Subclass extends DBFileTest_Object implements TestOnly {
	private static $db = array(
		'AnotherFile' => 'DBFile'
	);
}



