<?php

use Filesystem as SS_Filesystem;
use League\Flysystem\Filesystem;
use League\Flysystem\Util;
use SilverStripe\Filesystem\Flysystem\AssetAdapter;
use SilverStripe\Filesystem\Flysystem\FlysystemAssetStore;
use SilverStripe\Filesystem\Flysystem\FlysystemUrlPlugin;
use SilverStripe\Filesystem\Storage\AssetStore;

class AssetStoreTest extends SapphireTest {

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
		AssetStoreTest_SpyStore::$seekable_override = null;
		
		// Update base url
		Config::inst()->update('Director', 'alternate_base_url', '/mysite/');
	}

	public function tearDown() {
		SS_Filesystem::removeFolder(ASSETS_PATH . '/DBFileTest');
		AssetStoreTest_SpyStore::$seekable_override = null;
		parent::tearDown();
	}

	/**
	 * @return AssetStore
	 */
	protected function getBackend() {
		return Injector::inst()->get('AssetStore');
	}

	/**
	 * Test different storage methods
	 */
	public function testStorageMethods() {
		$backend = $this->getBackend();

		// Test setFromContent
		$puppies1 = 'puppies';
		$puppies1Tuple = $backend->setFromString($puppies1, 'pets/my-puppy.txt');
		$this->assertEquals(
			array (
				'Hash' => '2a17a9cb4be918774e73ba83bd1c1e7d000fdd53',
				'Filename' => 'pets/my-puppy.txt',
				'Variant' => '',
			),
			$puppies1Tuple
		);

		// Test setFromStream (seekable)
		$fish1 = realpath(__DIR__ .'/../model/testimages/test_image_high-quality.jpg');
		$fish1Stream = fopen($fish1, 'r');
		$fish1Tuple = $backend->setFromStream($fish1Stream, 'parent/awesome-fish.jpg');
		fclose($fish1Stream);
		$this->assertEquals(
			array (
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'parent/awesome-fish.jpg',
				'Variant' => '',
			),
			$fish1Tuple
		);

		// Test with non-seekable streams
		AssetStoreTest_SpyStore::$seekable_override = false;
		$fish2 = realpath(__DIR__ .'/../model/testimages/test_image_low-quality.jpg');
		$fish2Stream = fopen($fish2, 'r');
		$fish2Tuple = $backend->setFromStream($fish2Stream, 'parent/mediocre-fish.jpg');
		fclose($fish2Stream);

		$this->assertEquals(
			array (
				'Hash' => '33be1b95cba0358fe54e8b13532162d52f97421c',
				'Filename' => 'parent/mediocre-fish.jpg',
				'Variant' => '',
			),
			$fish2Tuple
		);
		AssetStoreTest_SpyStore::$seekable_override = null;
	}

	/**
	 * Test that the backend correctly resolves conflicts
	 */
	public function testConflictResolution() {
		$backend = $this->getBackend();

		// Put a file in
		$fish1 = realpath(__DIR__ .'/../model/testimages/test_image_high-quality.jpg');
		$this->assertFileExists($fish1);
		$fish1Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish.jpg');
		$this->assertEquals(
			array(
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'directory/lovely-fish.jpg',
				'Variant' => '',
			),
			$fish1Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/a870de278b/lovely-fish.jpg',
			$backend->getAsURL($fish1Tuple['Hash'], $fish1Tuple['Filename'])
		);

		// Write a different file with same name. Should not detect duplicates since sha are different
		$fish2 = realpath(__DIR__ .'/../model/testimages/test_image_low-quality.jpg');
		try {
			$fish2Tuple = $backend->setFromLocalFile($fish2, 'directory/lovely-fish.jpg', AssetStore::CONFLICT_EXCEPTION);
		} catch(Exception $ex) {
			return $this->fail('Writing file with different sha to same location failed with exception');
		}
		$this->assertEquals(
			array(
				'Hash' => '33be1b95cba0358fe54e8b13532162d52f97421c',
				'Filename' => 'directory/lovely-fish.jpg',
				'Variant' => '',
			),
			$fish2Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/33be1b95cb/lovely-fish.jpg',
			$backend->getAsURL($fish2Tuple['Hash'], $fish2Tuple['Filename'])
		);

		// Write original file back with rename
		$this->assertFileExists($fish1);
		$fish3Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish.jpg', AssetStore::CONFLICT_RENAME);
		$this->assertEquals(
			array(
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'directory/lovely-fish-v2.jpg',
				'Variant' => '',
			),
			$fish3Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/a870de278b/lovely-fish-v2.jpg',
			$backend->getAsURL($fish3Tuple['Hash'], $fish3Tuple['Filename'])
		);

		// Write another file should increment to -v3
		$fish4Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish-v2.jpg', AssetStore::CONFLICT_RENAME);
		$this->assertEquals(
			array(
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'directory/lovely-fish-v3.jpg',
				'Variant' => '',
			),
			$fish4Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/a870de278b/lovely-fish-v3.jpg',
			$backend->getAsURL($fish4Tuple['Hash'], $fish4Tuple['Filename'])
		);

		// Test conflict use existing file
		$fish5Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish.jpg', AssetStore::CONFLICT_USE_EXISTING);
		$this->assertEquals(
			array(
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'directory/lovely-fish.jpg',
				'Variant' => '',
			),
			$fish5Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/a870de278b/lovely-fish.jpg',
			$backend->getAsURL($fish5Tuple['Hash'], $fish5Tuple['Filename'])
		);

		// Test conflict use existing file
		$fish6Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish.jpg', AssetStore::CONFLICT_OVERWRITE);
		$this->assertEquals(
			array(
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'directory/lovely-fish.jpg',
				'Variant' => '',
			),
			$fish6Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/a870de278b/lovely-fish.jpg',
			$backend->getAsURL($fish6Tuple['Hash'], $fish6Tuple['Filename'])
		);
	}

	/**
	 * Test that flysystem can regenerate the original filename from fileID
	 */
	public function testGetOriginalFilename() {
		$store = new AssetStoreTest_SpyStore();
		$this->assertEquals(
			'directory/lovely-fish.jpg',
			$store->getOriginalFilename('directory/a870de278b/lovely-fish.jpg', $variant)
		);
		$this->assertEmpty($variant);
		$this->assertEquals(
			'directory/lovely-fish.jpg',
			$store->getOriginalFilename('directory/a870de278b/lovely-fish__variant.jpg', $variant)
		);
		$this->assertEquals('variant', $variant);
		$this->assertEquals(
			'directory/lovely_fish.jpg',
			$store->getOriginalFilename('directory/a870de278b/lovely_fish__vari_ant.jpg', $variant)
		);
		$this->assertEquals('vari_ant', $variant);
		$this->assertEquals(
			'directory/lovely_fish.jpg',
			$store->getOriginalFilename('directory/a870de278b/lovely_fish.jpg', $variant)
		);
		$this->assertEmpty($variant);
		$this->assertEquals(
			'lovely-fish.jpg',
			$store->getOriginalFilename('a870de278b/lovely-fish.jpg', $variant)
		);
		$this->assertEmpty($variant);
		$this->assertEquals(
			'lovely-fish.jpg',
			$store->getOriginalFilename('a870de278b/lovely-fish__variant.jpg', $variant)
		);
		$this->assertEquals('variant', $variant);
		$this->assertEquals(
			'lovely_fish.jpg',
			$store->getOriginalFilename('a870de278b/lovely_fish__vari__ant.jpg', $variant)
		);
		$this->assertEquals('vari__ant', $variant);
		$this->assertEquals(
			'lovely_fish.jpg',
			$store->getOriginalFilename('a870de278b/lovely_fish.jpg', $variant)
		);
		$this->assertEmpty($variant);
	}

	/**
	 * Test internal file Id generation
	 */
	public function testGetFileID() {
		$store = new AssetStoreTest_SpyStore();
		$this->assertEquals(
			'directory/2a17a9cb4b/file.jpg',
			$store->getFileID(sha1('puppies'), 'directory/file.jpg')
		);
		$this->assertEquals(
			'2a17a9cb4b/file.jpg',
			$store->getFileID(sha1('puppies'), 'file.jpg')
		);
		$this->assertEquals(
			'dir_ectory/2a17a9cb4b/fil_e.jpg',
			$store->getFileID(sha1('puppies'), 'dir__ectory/fil__e.jpg')
		);
		$this->assertEquals(
			'directory/2a17a9cb4b/file_variant.jpg',
			$store->getFileID(sha1('puppies'), 'directory/file__variant.jpg', null)
		);
		$this->assertEquals(
			'directory/2a17a9cb4b/file__variant.jpg',
			$store->getFileID(sha1('puppies'), 'directory/file.jpg', 'variant')
		);
		$this->assertEquals(
			'2a17a9cb4b/file__var__iant.jpg',
			$store->getFileID(sha1('puppies'), 'file.jpg', 'var__iant')
		);
	}

	public function testGetMetadata() {
		$backend = $this->getBackend();

		// jpg
		$fish = realpath(__DIR__ .'/../model/testimages/test_image_high-quality.jpg');
		$fishTuple = $backend->setFromLocalFile($fish, 'parent/awesome-fish.jpg');
		$this->assertEquals(
			'image/jpeg',
			$backend->getMimeType($fishTuple['Hash'], $fishTuple['Filename'])
		);
		$fishMeta = $backend->getMetadata($fishTuple['Hash'], $fishTuple['Filename']);
		$this->assertEquals(151889, $fishMeta['size']);
		$this->assertEquals('file', $fishMeta['type']);
		$this->assertNotEmpty($fishMeta['timestamp']);


		// text
		$puppies = 'puppies';
		$puppiesTuple = $backend->setFromString($puppies, 'pets/my-puppy.txt');
		$this->assertEquals(
			'text/plain',
			$backend->getMimeType($puppiesTuple['Hash'], $puppiesTuple['Filename'])
		);
		$puppiesMeta = $backend->getMetadata($puppiesTuple['Hash'], $puppiesTuple['Filename']);
		$this->assertEquals(7, $puppiesMeta['size']);
		$this->assertEquals('file', $puppiesMeta['type']);
		$this->assertNotEmpty($puppiesMeta['timestamp']);
	}

	/**
	 * Test that legacy filenames work as expected
	 */
	public function testLegacyFilenames() {
		Config::inst()->update(get_class(new FlysystemAssetStore()), 'legacy_filenames', true);

		$backend = $this->getBackend();

		// Put a file in
		$fish1 = realpath(__DIR__ .'/../model/testimages/test_image_high-quality.jpg');
		$this->assertFileExists($fish1);
		$fish1Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish.jpg');
		$this->assertEquals(
			array(
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'directory/lovely-fish.jpg',
				'Variant' => '',
			),
			$fish1Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/lovely-fish.jpg',
			$backend->getAsURL($fish1Tuple['Hash'], $fish1Tuple['Filename'])
		);

		// Write a different file with same name.
		// Since we are using legacy filenames, this should generate a new filename
		$fish2 = realpath(__DIR__ .'/../model/testimages/test_image_low-quality.jpg');
		try {
			$backend->setFromLocalFile($fish2, 'directory/lovely-fish.jpg', AssetStore::CONFLICT_EXCEPTION);
			return $this->fail('Writing file with different sha to same location should throw exception');
		} catch(Exception $ex) {
			// Success
		}

		// Re-attempt this file write with conflict_rename
		$fish3Tuple = $backend->setFromLocalFile($fish2, 'directory/lovely-fish.jpg', AssetStore::CONFLICT_RENAME);
		$this->assertEquals(
			array(
				'Hash' => '33be1b95cba0358fe54e8b13532162d52f97421c',
				'Filename' => 'directory/lovely-fish-v2.jpg',
				'Variant' => '',
			),
			$fish3Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/lovely-fish-v2.jpg',
			$backend->getAsURL($fish3Tuple['Hash'], $fish3Tuple['Filename'])
		);

		// Write back original file, but with CONFLICT_EXISTING. The file should not change
		$fish4Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish-v2.jpg', AssetStore::CONFLICT_USE_EXISTING);
		$this->assertEquals(
			array(
				'Hash' => '33be1b95cba0358fe54e8b13532162d52f97421c',
				'Filename' => 'directory/lovely-fish-v2.jpg',
				'Variant' => '',
			),
			$fish4Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/lovely-fish-v2.jpg',
			$backend->getAsURL($fish4Tuple['Hash'], $fish4Tuple['Filename'])
		);

		// Write back original file with CONFLICT_OVERWRITE. The file sha should now be updated
		$fish5Tuple = $backend->setFromLocalFile($fish1, 'directory/lovely-fish-v2.jpg', AssetStore::CONFLICT_OVERWRITE);
		$this->assertEquals(
			array(
				'Hash' => 'a870de278b475cb75f5d9f451439b2d378e13af1',
				'Filename' => 'directory/lovely-fish-v2.jpg',
				'Variant' => '',
			),
			$fish5Tuple
		);
		$this->assertEquals(
			'/mysite/assets/DBFileTest/directory/lovely-fish-v2.jpg',
			$backend->getAsURL($fish5Tuple['Hash'], $fish5Tuple['Filename'])
		);
	}
}

/**
 * Spy!
 */
class AssetStoreTest_SpyStore extends FlysystemAssetStore {

	/**
	 * Set to true|false to override all isSeekableStream calls
	 * 
	 * @var null|bool
	 */
	public static $seekable_override = null;

	public function cleanFilename($filename) {
		return parent::cleanFilename($filename);
	}

	public function getFileID($hash, $filename, $variant = null) {
		return parent::getFileID($hash, $filename, $variant);
	}

	public function getOriginalFilename($fileID, &$variant = '') {
		return parent::getOriginalFilename($fileID, $variant);
	}

	protected function isSeekableStream($stream) {
		if(isset(self::$seekable_override)) {
			return self::$seekable_override;
		}
		return parent::isSeekableStream($stream);
	}
}
